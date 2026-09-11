<?php

declare(strict_types=1);

namespace App\Models;

final class TypeAcceptance extends BaseModel
{
    protected $table = 'wms.type_acceptance';
    protected $guarded = ['*'];
    protected $casts = ['status' => 'integer'];
}
