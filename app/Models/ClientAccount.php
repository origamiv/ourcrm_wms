<?php

declare(strict_types=1);

namespace App\Models;

final class ClientAccount extends BaseModel
{
    protected $table = 'clients.accounts';

    protected $guarded = ['*'];

    protected $casts = ['status' => 'integer', 'client_id' => 'integer', 'service_id' => 'integer', 'group_id' => 'integer', 'server_id' => 'integer', 'src' => 'array'];

    public function client(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function service(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ClientService::class, 'service_id');
    }
}
