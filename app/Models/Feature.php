<?php

declare(strict_types=1);

namespace App\Models;

final class Feature extends BaseModel
{
    protected $table = 'main.features';

    protected $guarded = ['*'];

    protected $casts = ['status' => 'integer', 'is_resource' => 'integer', 'module_id' => 'integer'];

    public function module(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Module::class);
    }
}
