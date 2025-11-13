<?php

declare(strict_types=1);

namespace App\SyncModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use OurCRM\BaseModel;

/**
 * @property int $bot_id bot_id
 * @property int $account_id account_id
 * @property string $name name
 * @property int $messenger_id messenger_id
 * @property string $peer_id peer_id
 * @property string $first_name first_name
 * @property string $last_name last_name
 * @property string $photo_id photo_id
 * @property string $username username
 * @property json $src src
 * @property string $status Статус
 * @property int $activity Активность
 */
final class MessUser extends BaseModel
{
    use HasFactory;
    use SoftDeletes;

    // public $guarded = ['id'];
    protected $table = 'messenger.users';
    protected $connection = 'two';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'bot_id', // bot_id
        'account_id', // account_id
        'name', // name
        'messenger_id', // messenger_id
        'peer_id', // peer_id
        'first_name', // first_name
        'last_name', // last_name
        'photo_id', // photo_id
        'username', // username
        'src', // src
        'status', // Статус
        'activity', // Активность
    ];

    /**
     * Атрибуты, которые должны быть преобразованы в дату
     *
     * @var array
     */
    protected $dates = ['deleted_at'];
}
