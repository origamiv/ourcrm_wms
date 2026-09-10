<?php

declare(strict_types=1);

namespace App\Models;

final class ClientCompany extends BaseModel
{
    protected $table = 'clients.companies';

    protected $guarded = ['*'];

    protected $casts = ['status' => 'integer', 'client_id' => 'integer', 'src' => 'array'];

    public function client(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
