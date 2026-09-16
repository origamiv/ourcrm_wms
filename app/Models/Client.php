<?php

declare(strict_types=1);

namespace App\Models;

final class Client extends BaseModel
{
    protected $table = 'clients.clients';

    protected $guarded = ['*'];

    protected $casts = ['status' => 'integer', 'src' => 'array'];
}
