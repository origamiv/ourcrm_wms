<?php

declare(strict_types=1);

namespace App\Models;

final class Marketplace extends BaseModel
{
    protected $table = 'wms.marketplaces';

    protected $guarded = ['*'];

    protected $casts = ['status' => 'integer'];
}
