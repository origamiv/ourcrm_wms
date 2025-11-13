<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use OurCRM\BaseModel;

/**
 * @property string $name Название
 * @property string $shortname Короткое
 * @property int $assistant_id Ассистент
 * @property int $account_id Аккаунт
 * @property int $channel_id Канал
 * @property text $query Запрос
 * @property text $answer Ответ
 * @property string $direction направление
 * @property string $ext_run_id ID запуска
 * @property jsonb $ext_run_data Инфо запуска
 * @property int $total_tokens Потрачено токенов
 * @property float $price Цена
 * @property int $status Статус
 */
final class AssistantDialog extends BaseModel
{
    use HasFactory;
    use SoftDeletes;

    const STATUS_OK=1;
    const STATUS_ERROR=2;
    const STATUS_IN_PROCESS=3;





    // public $guarded = ['id'];
    protected $table = 'messenger.assistant_dialogs';

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
        'query', // Запрос
        'answer', // Ответ
        'direction', // направление
        'ext_run_id', // ID запуска
        'ext_run_data', // Инфо запуска
        'total_tokens', // Потрачено токенов
        'price', // Цена
        'status', // Статус
        'assistant_chat_id',
    ];

    /**
     * Атрибуты, которые должны быть преобразованы в дату
     *
     * @var array
     */
    protected $dates = ['deleted_at'];
}
