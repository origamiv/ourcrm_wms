<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use OurCRM\BaseModel;

/**
 * @property string $name Название
 * @property string $shortname Короткое
 * @property date $date_create Дата и время
 * @property date $cron Расписание
 * @property int $account_id Аккаунт
 * @property int $channel_id Канал
 * @property mediumtext $prompt Промт
 * @property jsonb $options Настройки
 * @property int $status Статус
 */
final class Creator extends BaseModel
{
    use HasFactory;
    use SoftDeletes;

    // public $guarded = ['id'];
    protected $table = 'messenger.creators';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name', // Название
        'shortname', // Короткое
        'date_create', // Дата и время
        'cron', // Расписание
        'account_id', // Аккаунт
        'channel_id', // Канал
        'prompt', // Промт
        'options', // Настройки
        'status', // Статус
    ];

    /**
     * Атрибуты, которые должны быть преобразованы в дату
     *
     * @var array
     */
    protected $dates = ['deleted_at'];
}
