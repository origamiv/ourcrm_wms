<?php

declare(strict_types=1);

namespace App\Models;

final class File extends BaseModel
{
    protected $table = 'main.files';

    protected $guarded = ['*'];

    protected $casts = ['status' => 'integer', 'size' => 'integer', 'company_id' => 'integer', 'user_id' => 'integer', 'is_s3' => 'integer'];

    public function company(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
