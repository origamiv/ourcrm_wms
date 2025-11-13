<?php

declare(strict_types=1);

namespace App\SyncModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use OurCRM\BaseModel;

/**
 * @property string $name Название
 * @property string $shortname Короткое
 * @property date $date_send Дата и время отправки
 * @property int $account_id Аккаунт
 * @property int $channel_id Канал
 * @property int $message_id ID сообщения после отправки
 * @property mediumtext $message Сообщение
 * @property int $status Статус
 */
final class Posting extends BaseModel
{
    use HasFactory;
    use SoftDeletes;

    // public $guarded = ['id'];
    protected $table = 'messenger.postings';
    protected $connection = 'two';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name', // Название
        'shortname', // Короткое
        'date_send', // Дата и время отправки
        'account_id', // Аккаунт
        'channel_id', // Канал
        'message_id', // ID сообщения после отправки
        'message', // Сообщение
        'status', // Статус
    ];

    /**
     * Атрибуты, которые должны быть преобразованы в дату
     *
     * @var array
     */
    protected $dates = ['deleted_at'];
}
