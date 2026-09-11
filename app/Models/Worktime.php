<?php

declare(strict_types=1);

namespace App\Models;

final class Worktime extends BaseModel
{
    protected $table = 'main.worktime';

    protected $guarded = ['*'];

    protected $casts = ['work_date' => 'date:Y-m-d', 'event_at' => 'immutable_datetime'];
}
