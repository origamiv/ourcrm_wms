<?php

declare(strict_types=1);

namespace App\SyncModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use OurCRM\BaseModel;

/**
 * @property int $account_id Аккаунт
 * @property int $bot_id Бот
 * @property string $name Название
 * @property string $shortname Короткое
 * @property string $channel Название в мессенджере
 * @property string $username Псевдоним
 * @property string $phone Телефон
 * @property string $fn_avatar Аватар
 * @property int $type_channel Тип канала
 * @property int $cnt Количество сообщений
 * @property int $cnt_parsed Количество полученных сообщений
 * @property int $cnt_people Количество участников
 * @property int $cnt_people_parsed Количество полученных участников
 * @property int $can_view_participants Можно просматривать участников
 * @property date $date_last_message Время последнего сообщения
 * @property date $date_last_check Время последней проверки сообщений
 * @property int $frequency Частота сообщений
 * @property int $status Статус
 * @property jsonb $srcDialog Исходник
 * @property jsonb $src Исходник
 * @property int $last_message_id Последнее сообщение
 * @property int $cnt_unread Число непрочитанных сообщений
 * @property date $date_last_read Время прочтения сообщений
 * @property json $last_message_src last_message_src
 * @property date $tagged_at tagged_at
 */
final class Channel extends BaseModel
{
    use HasFactory;
    use SoftDeletes;

    // public $guarded = ['id'];
    protected $table = 'messenger.channels';
    protected $connection = 'two';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'account_id', // Аккаунт
        'bot_id', // Бот
        'name', // Название
        'shortname', // Короткое
        'channel', // Название в мессенджере
        'username', // Псевдоним
        'phone', // Телефон
        'fn_avatar', // Аватар
        'type_channel', // Тип канала
        'cnt', // Количество сообщений
        'cnt_parsed', // Количество полученных сообщений
        'cnt_people', // Количество участников
        'cnt_people_parsed', // Количество полученных участников
        'can_view_participants', // Можно просматривать участников
        'date_last_message', // Время последнего сообщения
        'date_last_check', // Время последней проверки сообщений
        'frequency', // Частота сообщений
        'status', // Статус
        'srcDialog', // Исходник
        'src', // Исходник
        'last_message_id', // Последнее сообщение
        'cnt_unread', // Число непрочитанных сообщений
        'date_last_read', // Время прочтения сообщений
        'last_message_src', // last_message_src
        'tagged_at', // tagged_at
    ];

    /**
     * Атрибуты, которые должны быть преобразованы в дату
     *
     * @var array
     */
    protected $dates = ['deleted_at'];
}
