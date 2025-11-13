<?php

declare(strict_types=1);

namespace App\SyncModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use OurCRM\BaseModel;

/**
 * @property int $user_id ID польз у нас
 * @property int $channel_id ID канала у нас
 * @property string $mess_user_id ID польз в мессенджере
 * @property string $mess_channel ID канала в мессенджере
 * @property json $src src
 * @property int $account_id account_id
 */
final class ChannelUser extends BaseModel
{
    use HasFactory;
    use SoftDeletes;

    // public $guarded = ['id'];
    protected $table = 'messenger.channel_users';
    protected $connection = 'two';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id', // ID польз у нас
        'channel_id', // ID канала у нас
        'mess_user_id', // ID польз в мессенджере
        'mess_channel', // ID канала в мессенджере
        'src', // src
        'account_id', // account_id
    ];

    /**
     * Атрибуты, которые должны быть преобразованы в дату
     *
     * @var array
     */
    protected $dates = ['deleted_at'];
}
