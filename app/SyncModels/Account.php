<?php

declare(strict_types=1);

namespace App\SyncModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use OurCRM\BaseModel;

/**
 * @property string $name Название
 * @property string $shortname Короткое
 * @property int $user_id Пользователь
 * @property int $messenger_id Мессенджер
 * @property string $login Логин
 * @property string $code Код
 * @property string $password Пароль
 * @property int $is_2fa Включена 2ФА
 * @property string $pass2fa Пароль 2FA
 * @property string $telegram_id ID в Телеграмм
 * @property text $tas_session_status Статус сессии
 * @property string $tas_session_expires Дата истечения сессии
 * @property string $first_name Имя
 * @property string $last_name Фамилия
 * @property string $username Псевдоним
 * @property jsonb $src Инфо об аккаунте
 * @property jsonb $options Опции
 * @property int $slot Слот
 * @property date $last_used_at Время последнего использования
 * @property date $new_messages_last_check Время последней проверки сообщений
 * @property string $phone_code_hash phone_code_hash
 * @property int $cnt Количество сообщений
 * @property int $status Статус
 * @property int $status_messenger Статус в мессенджере
 * @property int $cnt_people cnt_people
 * @property string $tas_port Port для сессии
 * @property int $port port
 * @property string $fn_avatar fn_avatar
 * @property string $icon icon
 * @property date $tagged_at tagged_at
 * @property string $mode mode
 */
final class Account extends BaseModel
{
    use HasFactory;
    use SoftDeletes;

    // public $guarded = ['id'];
    protected $table = 'messenger.accounts';
    protected $connection = 'two';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name', // Название
        'shortname', // Короткое
        'user_id', // Пользователь
        'messenger_id', // Мессенджер
        'login', // Логин
        'code', // Код
        'password', // Пароль
        'is_2fa', // Включена 2ФА
        'pass2fa', // Пароль 2FA
        'telegram_id', // ID в Телеграмм
        'tas_session_status', // Статус сессии
        'tas_session_expires', // Дата истечения сессии
        'first_name', // Имя
        'last_name', // Фамилия
        'username', // Псевдоним
        'src', // Инфо об аккаунте
        'options', // Опции
        'slot', // Слот
        'last_used_at', // Время последнего использования
        'new_messages_last_check', // Время последней проверки сообщений
        'phone_code_hash', // phone_code_hash
        'cnt', // Количество сообщений
        'status', // Статус
        'status_messenger', // Статус в мессенджере
        'cnt_people', // cnt_people
        'tas_port', // Port для сессии
        'port', // port
        'fn_avatar', // fn_avatar
        'icon', // icon
        'tagged_at', // tagged_at
        'mode', // mode
    ];

    /**
     * Атрибуты, которые должны быть преобразованы в дату
     *
     * @var array
     */
    protected $dates = ['deleted_at'];
}
