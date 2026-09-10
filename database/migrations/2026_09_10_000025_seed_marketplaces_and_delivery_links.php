<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("SET LOCAL lock_timeout = '5s'");
        DB::transaction(function (): void {
            DB::table('wms.marketplaces')->lockForUpdate()->get();
            DB::table('wms.delivery_services')->lockForUpdate()->get();

            $marketplaces = [
                ['name' => 'Wildberries', 'shortname' => 'WB'],
                ['name' => 'OZON', 'shortname' => 'OZ'],
                ['name' => 'СберМегаМаркет', 'shortname' => 'SMM'],
                ['name' => 'ЛеруаМерлен', 'shortname' => 'LM'],
                ['name' => 'Yandex Market', 'shortname' => 'YM'],
                ['name' => 'AliExpress', 'shortname' => 'Ali'],
                ['name' => 'МВидео', 'shortname' => 'MVideo'],
                ['name' => 'Сайт', 'shortname' => 'SITE'],
            ];

            $marketplaceIds = [];
            foreach ($marketplaces as $marketplace) {
                $row = DB::table('wms.marketplaces')->where('name', $marketplace['name'])->first();
                if ($row === null) {
                    $id = DB::table('wms.marketplaces')->insertGetId([
                        ...$marketplace,
                        'status' => 1,
                        'tenant_id' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    $id = $row->id;
                    DB::table('wms.marketplaces')->where('id', $id)->whereNull('shortname')->update([
                        'shortname' => $marketplace['shortname'],
                        'updated_at' => now(),
                    ]);
                }
                $marketplaceIds[$marketplace['name']] = $id;
            }

            $shortnames = [
                'Wildberries FBS' => 'WB', 'OZON FBS' => 'OZ', 'СберМегаМаркет' => 'SMM',
                'ЛеруаМерлен' => 'LM', 'Курьер' => 'COURIER', 'Wildberries FBO' => 'WB',
                'OZON FBO' => 'OZ', 'OZON Real FBS' => 'OZ', 'YandexMarket FBS' => 'YM',
                'YandexMarket DBS' => 'YM', 'OZON FBP' => 'OZ', 'AliExpress' => 'Ali',
                'МВидео' => 'MVideo', 'Yandex FBP' => 'YM', 'Сайт' => 'SITE',
                'Далли' => 'DALLI', 'СДЭК' => 'CDEK', 'Почта РФ' => 'POSTRF',
                'Пятёрочка' => 'PYAT', 'dubaiexpress.ru' => 'DUBAI',
            ];
            $links = [
                'Wildberries FBS' => 'Wildberries', 'Wildberries FBO' => 'Wildberries',
                'OZON FBS' => 'OZON', 'OZON FBO' => 'OZON', 'OZON Real FBS' => 'OZON', 'OZON FBP' => 'OZON',
                'СберМегаМаркет' => 'СберМегаМаркет', 'ЛеруаМерлен' => 'ЛеруаМерлен',
                'YandexMarket FBS' => 'Yandex Market', 'YandexMarket DBS' => 'Yandex Market', 'Yandex FBP' => 'Yandex Market',
                'AliExpress' => 'AliExpress', 'МВидео' => 'МВидео', 'Сайт' => 'Сайт',
            ];
            foreach ($shortnames as $name => $shortname) {
                $query = DB::table('wms.delivery_services')->where('name', $name)->whereNull('tenant_id');
                $values = ['shortname' => $shortname, 'updated_at' => now()];
                if (isset($links[$name])) {
                    $values['marketplace_id'] = $marketplaceIds[$links[$name]];
                }
                $query->update($values);
            }
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Откат заполнения маркетплейсов и связей запрещён: записи могут использоваться.');
    }
};
