<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use OurCRM\BaseModel;

/**
 * @property string $name Название
 * @property string $shortname Короткое название
 * @property int $account_id Аккаунт
 * @property int $channel_id Канал
 * @property int $message_id Сообщение
 * @property int $cnt Кол-во скачиваний
 * @property string $path Путь
 * @property int $status Статус
 * @property json $src исходник сообщения
 */
final class File extends BaseModel
{
    use HasFactory;
    use SoftDeletes;

    // public $guarded = ['id'];
    protected $table = 'messenger.files';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'name', // Название
        'shortname', // Короткое название
        'account_id', // Аккаунт
        'channel_id', // Канал
        'message_id', // Сообщение
        'cnt', // Кол-во скачиваний
        'path', // Путь
        'status', // Статус
        'src', // исходник сообщения
    ];

    /**
     * Атрибуты, которые должны быть преобразованы в дату
     *
     * @var array
     */
    protected $dates = ['deleted_at'];
}
