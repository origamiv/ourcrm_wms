<?php

declare(strict_types=1);

namespace App\Models;

final class Icon extends BaseModel
{
    protected $table = 'main.icons';

    protected $guarded = ['*'];

    protected $casts = ['status' => 'integer', 'size' => 'integer', 'company_id' => 'integer', 'user_id' => 'integer'];

    public function company(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
