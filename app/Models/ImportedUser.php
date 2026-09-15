<?php
declare(strict_types=1); namespace App\Models;
final class ImportedUser extends BaseModel { protected $table='wms.users'; protected $guarded=['*']; protected $casts=['status'=>'integer','src'=>'array']; }
