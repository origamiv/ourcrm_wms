<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use OurCRM\BaseModel;

/**
 * @property string $name Название
 * @property string $shortname Короткое название
 * @property int $service_id Сервис
 * @property string $login Логин
 * @property string $password Пароль
 * @property string $token Токен
 * @property json $options Параметры
 * @property int $status Статус
 * @property float $balance Баланс
 * @property int $cnt Количество
 */
final class ServiceAccount extends BaseModel
{
    use HasFactory;
    use SoftDeletes;

    // public $guarded = ['id'];
    protected $table = 'messenger.service_accounts';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name', // Название
        'shortname', // Короткое название
        'service_id', // Сервис
        'login', // Логин
        'password', // Пароль
        'token', // Токен
        'options', // Параметры
        'status', // Статус
        'balance', // Баланс
        'cnt', // Количество
    ];

    /**
     * Атрибуты, которые должны быть преобразованы в дату
     *
     * @var array
     */
    protected $dates = ['deleted_at'];
}
