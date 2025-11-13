<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use OurCRM\BaseModel;

final class Migration extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];
}
