<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Models\Worktime;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class WorktimeService
{
    public function state(User $user): array
    {
        $last = Worktime::query()->where('user_id', $user->id)->latest('event_at')->first();
        $state = $this->stateFrom($last?->event_type);

        return ['state' => $state, 'can_start' => $state === 'not_started', 'can_pause' => in_array($state, ['working', 'paused'], true), 'can_finish' => $state === 'working'];
    }

    public function event(User $user, string $action): array
    {
        return DB::transaction(function () use ($user, $action): array {
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $last = Worktime::query()->where('user_id', $user->id)->latest('event_at')->lockForUpdate()->first();
            $state = $this->stateFrom($last?->event_type);
            $type = match ($action) {
                'start' => $state === 'not_started' ? 'start' : null,
                'pause' => $state === 'working' ? 'pause_start' : ($state === 'paused' ? 'pause_end' : null),
                'finish' => $state === 'working' ? 'finish' : null,
                default => null,
            };
            if ($type === null) {
                throw ValidationException::withMessages(['worktime' => 'Действие недоступно в текущем состоянии рабочего дня.']);
            }
            $now = CarbonImmutable::now();
            Worktime::query()->forceCreate(['user_id' => $user->id, 'tenant_id' => $user->tenant_id, 'work_date' => $now->toDateString(), 'event_type' => $type, 'event_at' => $now]);

            return $this->state($user);
        });
    }

    public function calendar(User $user, string $from, string $to): array
    {
        $start = CarbonImmutable::parse($from)->startOfDay();
        $end = CarbonImmutable::parse($to)->endOfDay();
        $users = app(AccessService::class)->isAdmin($user) ? User::query()->where('tenant_id', $user->tenant_id)->where('status', 1)->whereNull('deleted_at')->orderBy('last_name')->orderBy('name')->get() : collect([$user]);
        $events = Worktime::query()->whereIn('user_id', $users->pluck('id'))->whereBetween('event_at', [$start, $end])->orderBy('user_id')->orderBy('event_at')->get()->groupBy('user_id');
        $days = [];
        for ($date = $start; $date->lte($end); $date = $date->addDay()) $days[] = $date->toDateString();
        $rows = $users->map(fn (User $item) => ['user' => ['id' => (string) $item->id, 'name' => $item->name, 'last_name' => $item->last_name, 'middle_name' => $item->middle_name, 'email' => $item->email], 'days' => $this->daily($events->get($item->id, collect()), $days)])->values();

        return ['from' => $from, 'to' => $to, 'days' => $days, 'rows' => $rows];
    }

    private function daily($events, array $days): array
    {
        $result = array_fill_keys($days, ['state' => 'empty', 'worked_minutes' => 0, 'pause_minutes' => 0, 'started_at' => null, 'finished_at' => null]);
        $open = null; $pause = null;
        foreach ($events as $event) {
            $date = $event->event_at->setTimezone(config('app.timezone'))->toDateString();
            if (! isset($result[$date])) continue;
            $at = $event->event_at;
            if ($event->event_type === 'vacation' || $event->event_type === 'sick') { $result[$date]['state'] = $event->event_type; $open = null; $pause = null; }
            elseif ($event->event_type === 'start') { $open = $at; $result[$date]['started_at'] = $at->toIso8601String(); $result[$date]['state'] = 'working'; }
            elseif ($event->event_type === 'pause_start' && $open) { $pause = $at; $result[$date]['state'] = 'paused'; }
            elseif ($event->event_type === 'pause_end' && $pause) { $result[$date]['pause_minutes'] += $pause->diffInMinutes($at); $pause = null; $result[$date]['state'] = 'working'; }
            elseif ($event->event_type === 'finish' && $open) { $result[$date]['worked_minutes'] += max(0, $open->diffInMinutes($at) - $result[$date]['pause_minutes']); $result[$date]['finished_at'] = $at->toIso8601String(); $open = null; $result[$date]['state'] = 'finished'; }
        }
        return $result;
    }

    private function stateFrom(?string $type): string
    { return match ($type) { 'start', 'pause_end' => 'working', 'pause_start' => 'paused', 'finish', null => 'not_started', default => 'not_started' }; }
}
