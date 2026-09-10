<?php

declare(strict_types=1);

namespace App\Models;

final class ClientIndividual extends BaseModel
{
    protected $table = 'clients.individuals';

    protected $guarded = ['*'];

    protected $casts = ['status' => 'integer', 'client_id' => 'integer', 'user_id' => 'integer', 'manager_id' => 'integer', 'birthday' => 'date:Y-m-d', 'passport_date' => 'date:Y-m-d', 'vodud_date' => 'date:Y-m-d'];

    public function client(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function manager(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }
}
