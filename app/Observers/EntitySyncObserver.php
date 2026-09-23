<?php

declare(strict_types=1);

namespace App\Observers;

use App\Services\EntityChangeRecorder;
use Illuminate\Database\Eloquent\Model;

final class EntitySyncObserver
{
    public function __construct(private readonly EntityChangeRecorder $recorder) {}

    public function created(Model $model): void
    {
        if ($this->enabled($model)) {
            $this->recorder->recordCreated($model);
        }
    }

    public function updated(Model $model): void
    {
        if ($this->enabled($model)) {
            $this->recorder->recordUpdated($model);
        }
    }

    public function deleted(Model $model): void
    {
        if ($this->enabled($model)) {
            $this->recorder->recordDeleted($model);
        }
    }

    private function enabled(Model $model): bool
    {
        return ! $this->recorder->hasDatabaseTrigger($model->getTable());
    }
}
