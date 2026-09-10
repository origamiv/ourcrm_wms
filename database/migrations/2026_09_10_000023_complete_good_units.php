<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            DB::statement("SET LOCAL lock_timeout = '5s'");
            DB::statement('LOCK TABLE goods.unit_goods IN SHARE ROW EXCLUSIVE MODE');

            $units = [
                'Минута' => 'мин.',
                'Час' => 'час',
                'Смена' => 'смена',
                'Сутки' => 'сутки',
                'Неделя' => 'нед.',
                'Месяц' => 'мес.',
                'Квартал' => 'квартал',
                'Год' => 'год',
                'Миллиметр' => 'мм.',
                'Сантиметр' => 'см.',
                'Метр' => 'м.',
                'Грамм' => 'гр.',
                'Килограмм' => 'кг.',
                'Кубический сантиметр' => 'см3',
                'Кубический метр' => 'м3',
                'Литр' => 'литр',
                'Полка' => 'полка',
                'Стеллаж' => 'стеллаж',
                'Палета' => 'паллета',
                'Квадратный метр' => 'м2',
                'Штука' => 'шт.',
                'Набор' => 'набор',
                'Упаковка' => 'упак.',
                'Короб' => 'короб',
                'Километр' => 'км.',
                'Рубль' => 'руб.',
                'Доллар' => '$',
                'Белорусский рубль' => 'бел руб.',
                'Тенге' => 'тенге',
                'Армянский драм' => 'драм',
                'Турецкая лира' => 'лир',
                'Узбекский сум' => 'сум',
                'Азербайджанский манат' => 'манат',
                'Грузинский лари' => 'лари',
                'Евро' => 'евро',
                'Дирхам ОАЭ' => 'дирхам',
                'Украинская гривна' => 'грвн.',
                'Китайский юань' => 'юань',
            ];

            foreach ($units as $name => $shortname) {
                $existing = DB::table('goods.unit_goods')
                    ->whereNull('tenant_id')
                    ->whereNull('deleted_at')
                    ->whereRaw('lower(btrim(name)) = lower(?)', [$name]);

                if ($existing->exists()) {
                    $existing->whereRaw("coalesce(btrim(shortname), '') = ''")
                        ->update(['shortname' => $shortname, 'updated_at' => now()]);
                } else {
                    DB::table('goods.unit_goods')->insert([
                        'name' => $name,
                        'shortname' => $shortname,
                        'status' => 1,
                        'tenant_id' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Дополнение единиц измерения необратимо: записи могут использоваться товарами, прежние сокращения не сохранены.');
    }
};
