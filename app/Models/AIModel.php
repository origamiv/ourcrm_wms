<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use OurCRM\BaseModel;

/**
 * @property string $name Название
 * @property string $shortname Короткое
 * @property float $price_prompt Цена промпта за 1000 токенов
 * @property float $price_complete Цена завершения за 1000 токенов
 * @property jsonb $options Опции
 * @property int $status Статус
 */
final class AIModel extends BaseModel
{
    use HasFactory;
    use SoftDeletes;

    // public $guarded = ['id'];
    protected $table = 'messenger.ai_models';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name', // Название
        'shortname', // Короткое
        'price_prompt', // Цена промпта за 1000 токенов
        'price_complete', // Цена завершения за 1000 токенов
        'options', // Опции
        'status', // Статус
    ];

    /**
     * Атрибуты, которые должны быть преобразованы в дату
     *
     * @var array
     */
    protected $dates = ['deleted_at'];
}
