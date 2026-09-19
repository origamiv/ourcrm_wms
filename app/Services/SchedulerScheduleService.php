<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

final class SchedulerScheduleService
{
    public const TIMEZONE = 'Europe/Moscow';

    public function normalize(array $schedule): array
    {
        $kind = (string) ($schedule['kind'] ?? 'interval');
        if (! in_array($kind, ['interval', 'weekly', 'monthly'], true)) throw new InvalidArgumentException('Недопустимый тип расписания.');
        $result = $schedule + ['kind' => $kind, 'start_date' => now(self::TIMEZONE)->format('Y-m-d'), 'time' => '00:00'];
        if (! preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', (string) $result['time'])) throw new InvalidArgumentException('Недопустимое время.');
        if ($kind === 'interval') {
            $result['interval'] = max(1, (int) ($result['interval'] ?? 1));
            if (! in_array($result['unit'] ?? 'hour', ['minute', 'hour', 'day'], true)) throw new InvalidArgumentException('Недопустимая единица интервала.');
        } elseif ($kind === 'weekly') {
            $result['week_interval'] = in_array((int) ($result['week_interval'] ?? 1), [1, 2], true) ? (int) $result['week_interval'] : 1;
            $result['weekdays'] = array_values(array_unique(array_filter(array_map('intval', (array) ($result['weekdays'] ?? [])), fn (int $day): bool => $day >= 1 && $day <= 7)));
            if ($result['weekdays'] === []) throw new InvalidArgumentException('Выберите дни недели.');
        } else {
            $result['month_days'] = array_values(array_unique(array_filter(array_map('intval', (array) ($result['month_days'] ?? [])), fn (int $day): bool => $day >= 1 && $day <= 31)));
            if ($result['month_days'] === []) throw new InvalidArgumentException('Выберите дни месяца.');
        }
        return $result;
    }

    public function next(array $schedule, ?CarbonImmutable $after = null): ?CarbonImmutable
    {
        $s = $this->normalize($schedule); $after ??= CarbonImmutable::now(self::TIMEZONE);
        $start = CarbonImmutable::parse(($s['start_date'] ?? $after->format('Y-m-d')).' '.($s['time'] ?? '00:00'), self::TIMEZONE);
        if (($s['kind'] ?? 'interval') === 'interval') {
            $step = match ($s['unit']) { 'minute' => 'minute', 'day' => 'day', default => 'hour' };
            $candidate = $start;
            while ($candidate <= $after) $candidate = $candidate->add((int) $s['interval'], $step);
            return $this->withinEnd($candidate, $s);
        }
        $candidate = $start->startOfDay()->setTimeFromTimeString($s['time']);
        for ($i = 0; $i < 370; $i++) {
            $weekNumber = intdiv((int) $candidate->diffInDays($start), 7);
            if ($candidate > $after && (($s['kind'] === 'monthly' && in_array($candidate->day, $s['month_days'], true)) || ($s['kind'] === 'weekly' && in_array($candidate->dayOfWeekIso, $s['weekdays'], true) && $weekNumber % $s['week_interval'] === 0))) return $this->withinEnd($candidate, $s);
            $candidate = $candidate->addDay();
        }
        return null;
    }

    public function label(array $s): string
    {
        return match ($s['kind'] ?? 'interval') { 'weekly' => 'По дням недели, '.($s['time'] ?? ''), 'monthly' => 'По числам месяца, '.($s['time'] ?? ''), default => 'Каждые '.($s['interval'] ?? 1).' '.($s['unit'] === 'minute' ? 'мин.' : ($s['unit'] === 'day' ? 'дн.' : 'ч.')) };
    }

    private function withinEnd(CarbonImmutable $candidate, array $s): ?CarbonImmutable
    {
        return ! empty($s['end_date']) && $candidate->toDateString() > $s['end_date'] ? null : $candidate;
    }
}
