<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use OurCRM\BaseModel;

/**
 * @property string $name Название
 * @property string $shortname Короткое
 * @property text $prompt Промпт
 * @property text $data Данные
 * @property text $func Функция
 * @property string $ext_id ID ассистента
 * @property string $ext_model Модель
 * @property jsonb $options Опции
 * @property int $status Статус
 */
final class Assistant extends BaseModel
{
    use HasFactory;
    use SoftDeletes;

    // public $guarded = ['id'];
    protected $table = 'messenger.assistants';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name', // Название
        'shortname', // Короткое
        'prompt', // Промпт
        'data', // Данные
        'func', // Функция
        'ext_id', // ID ассистента
        'ext_model', // Модель
        'options', // Опции
        'status', // Статус
    ];

    /**
     * Атрибуты, которые должны быть преобразованы в дату
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    protected function data(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => json_decode($value, true),
            set: fn (array $value) => json_encode($value, JSON_UNESCAPED_UNICODE),
        );
    }
}
