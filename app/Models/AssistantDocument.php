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
final class AssistantDocument extends BaseModel
{
    use HasFactory;
    use SoftDeletes;

    // public $guarded = ['id'];
    protected $table = 'messenger.assistants_documents';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'assistant_id',
        'name',
        'title',
        'content',
        'embedding',
        'data_model',
        'status',
    ];

    protected $casts = [
        'embedding' => 'array', // удобно при сохранении
    ];
}
