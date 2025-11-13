<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use OurCRM\BaseModel;

/**
 * @property string $command command
 * @property string $shortname shortname
 * @property int $account_id account_id
 * @property text $command_arguments command_arguments
 * @property date $command_date command_date
 * @property int $status 0 - новая, 1 - выполнена, 2 - возникла проблема, 3 - в процессе выполнения
 * @property json $result result
 */
final class Command extends BaseModel
{
    use HasFactory;
    use SoftDeletes;

    // public $guarded = ['id'];
    protected $table = 'messenger.commands';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'command', // command
        'shortname', // shortname
        'account_id', // account_id
        'command_arguments', // command_arguments
        'command_date', // command_date
        'status', // 0 - новая, 1 - выполнена, 2 - возникла проблема, 3 - в процессе выполнения
        'result', // result
    ];

    /**
     * Атрибуты, которые должны быть преобразованы в дату
     *
     * @var array
     */
    protected $dates = ['deleted_at'];
}
