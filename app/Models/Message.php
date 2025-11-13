<?php

declare(strict_types=1);

namespace App\Models;

use App\Observers\SyncMessageObserver;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use OurCRM\BaseModel;

/**
 * @property int $account_id Аккаунт
 * @property int $bot_id Бот
 * @property int $messenger_id Мессенджер
 * @property string $message_id ИД сообщения
 * @property int $channel_id Канал
 * @property string $channel ИД канала в мессенджере
 * @property string $channel_name Название канала
 * @property int $thread_id ID темы
 * @property string $thread Тема в мессенджере
 * @property text $message Сообщение
 * @property json $files Сообщение
 * @property date $msg_date Время сообщения
 * @property int $napr Направление
 * @property string $from_id Отправитель
 * @property string $from_name Имя отправителя
 * @property jsonb $src Исходник
 * @property string $type_msg Тип сообщения
 * @property string $user Псевдоним
 * @property string $comment Примечание
 * @property int $resend_status Статус пересылки
 * @property string $phone Телефон
 * @property int $channel_id_our ID канала
 * @property date $date_view Время чтения сообщений
 * @property string $parent_message_id Связанное сообщение
 * @property string $messenger_user_id Пользователь мессенджера
 * @property int $is_hidden_for_user Скрытое
 * @property int $status Статус
 * @property int $is_media Есть вложения
 * @property int $comments_cnt кол-во комментариев
 * @property int $replies_cnt кол-во ответов
 * @property int $type_msg_id тип сообщения
 * @property int $is_read Сообщение прочитано или нет
 * @property json $parent_message_src parent_message_src
 * @property int $is_tagged is_tagged
 * @property json $reaction reaction
 * @property int $edited_status edited_status
 * @property int $edited_cnt edited_cnt
 */
class Message extends BaseModel
{
    use HasFactory;
    use SoftDeletes;

    const DIRECTION_IN=1;
    const DIRECTION_OUT=2;

    public $guarded = ['id'];
    protected $table = 'messenger.messages';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
//    protected $fillable = [
//        'id',
//        'account_id', // Аккаунт
//        'bot_id', // Бот
//        'messenger_id', // Мессенджер
//        'message_id', // ИД сообщения
//        'channel_id', // Канал
//        'channel', // ИД канала в мессенджере
//        'channel_name', // Название канала
//        'thread_id', // ID темы
//        'thread', // Тема в мессенджере
//        'message', // Сообщение
//        'files', // Сообщение
//        'msg_date', // Время сообщения
//        'napr', // Направление
//        'from_id', // Отправитель
//        'from_name', // Имя отправителя
//        'src', // Исходник
//        'type_msg', // Тип сообщения
//        'user', // Псевдоним
//        'comment', // Примечание
//        'resend_status', // Статус пересылки
//        'phone', // Телефон
//        'channel_id_our', // ID канала
//        'date_view', // Время чтения сообщений
//        'parent_message_id', // Связанное сообщение
//        'messenger_user_id', // Пользователь мессенджера
//        'is_hidden_for_user', // Скрытое
//        'status', // Статус
//        'is_media', // Есть вложения
//        'comments_cnt', // кол-во комментариев
//        'replies_cnt', // кол-во ответов
//        'type_msg_id', // тип сообщения
//        'is_read', // Сообщение прочитано или нет
//        'parent_message_src', // parent_message_src
//        'is_tagged', // is_tagged
//        'reaction', // reaction
//        'edited_status', // edited_status
//        'edited_cnt', // edited_cnt
//    ];

    /**
     * Атрибуты, которые должны быть преобразованы в дату
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

//    protected function parentMessageSrc(): ?Attribute
//    {
//        return Attribute::make(
//            get: fn (string $value) => json_decode($value, true) ?? null,
//        );
//    }
//    protected function Src(): Attribute
//    {
//        return Attribute::make(
//            get: fn (string $value) => json_decode($value, true),
//        );
//    }
}
