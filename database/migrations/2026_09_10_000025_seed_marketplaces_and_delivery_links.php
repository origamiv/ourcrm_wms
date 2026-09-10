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
                ['name' => 'Wildberries', 'shortname' => 'wb'],
                ['name' => 'OZON', 'shortname' => 'oz'],
                ['name' => 'СберМегаМаркет', 'shortname' => 'smm'],
                ['name' => 'ЛеруаМерлен', 'shortname' => 'lm'],
                ['name' => 'Yandex Market', 'shortname' => 'ym'],
                ['name' => 'AliExpress', 'shortname' => 'ali'],
                ['name' => 'МВидео', 'shortname' => 'mvideo'],
                ['name' => 'Сайт', 'shortname' => 'site'],
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
                'Wildberries FBS' => 'wb_fbs', 'OZON FBS' => 'oz_fbs', 'СберМегаМаркет' => 'smm',
                'ЛеруаМерлен' => 'lm', 'Курьер' => 'courier', 'Wildberries FBO' => 'wb_fbo',
                'OZON FBO' => 'oz_fbo', 'OZON Real FBS' => 'oz_real_fbs', 'YandexMarket FBS' => 'ym_fbs',
                'YandexMarket DBS' => 'ym_dbs', 'OZON FBP' => 'oz_fbp', 'AliExpress' => 'ali',
                'МВидео' => 'mvideo', 'Yandex FBP' => 'ym_fbp', 'Сайт' => 'site',
                'Далли' => 'dalli', 'СДЭК' => 'cdek', 'Почта РФ' => 'postrf',
                'Пятёрочка' => 'pyat', 'dubaiexpress.ru' => 'dubai',
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
