<?php

declare(strict_types=1);

namespace App\Models;

final class Instruction extends BaseModel
{
    public const SECTIONS = ['main', 'clients', 'goods', 'integration', 'fulfillment', 'maintenance'];

    public const CONTENT_TYPES = ['pdf', 'markdown', 'html', 'video'];

    protected $table = 'wms.instructions';

    protected $guarded = ['id'];

    protected $casts = [
        'size' => 'integer',
        'sort_order' => 'integer',
        'status' => 'integer',
    ];
}
