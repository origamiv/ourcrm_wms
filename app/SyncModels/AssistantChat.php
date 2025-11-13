<?php

declare(strict_types=1);

namespace App\SyncModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use OurCRM\BaseModel;

/**
 * @property string $name Название
 * @property string $shortname Короткое
 * @property int $assistant_id Ассистент
 * @property int $account_id Аккаунт
 * @property int $channel_id Канал
 * @property string $ext_thread_id Тред ассистента
 * @property string $direction Направление
 * @property jsonb $options Настройки
 * @property int $status Статус
 */
final class AssistantChat extends BaseModel
{
    use HasFactory;
    use SoftDeletes;

    // public $guarded = ['id'];
    protected $table = 'messenger.assistant_chats';
    protected $connection = 'two';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name', // Название
        'shortname', // Короткое
        'assistant_id', // Ассистент
        'account_id', // Аккаунт
        'channel_id', // Канал
        'ext_thread_id', // Тред ассистента
        'direction', // Направление
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
