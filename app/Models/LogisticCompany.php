<?php
declare(strict_types=1);
namespace App\Models;
final class LogisticCompany extends BaseModel { protected $table = 'wms.logistic_companies'; protected $guarded = ['id']; protected $casts = ['src' => 'array', 'status' => 'integer']; }
