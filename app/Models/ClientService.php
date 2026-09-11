<?php

declare(strict_types=1);

namespace App\Models;

final class ClientService extends BaseModel
{
    protected $table = 'clients.services';

    protected $guarded = ['*'];

    protected $casts = ['status' => 'integer'];
}
