<?php

declare(strict_types=1);

namespace App\SyncModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use OurCRM\BaseModel;

/**
 * @property int $account_id account_id
 * @property int $channel_id channel_id
 * @property text $message Сообщение
 * @property json $files Файл
 * @property json $response Ответ после отправки
 * @property int $status 0 - new, 1 - sent, 2 - blocked, 3 - in progress
 */
final class SendMessage extends BaseModel
{
    use HasFactory;
    use SoftDeletes;

    // public $guarded = ['id'];
    protected $table = 'messenger.send_messages';
    protected $connection = 'two';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'account_id', // account_id
        'channel_id', // channel_id
        'message', // Сообщение
        'files', // Файл
        'response', // Ответ после отправки
        'status', // 0 - new, 1 - sent, 2 - blocked, 3 - in progress
    ];

    /**
     * Атрибуты, которые должны быть преобразованы в дату
     *
     * @var array
     */
    protected $dates = ['deleted_at'];
}
