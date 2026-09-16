<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['order_statuses', 'order_sources', 'order_cancel_statuses', 'logistic_companies', 'shipment_statuses'] as $tableName) {
            Schema::create('wms.'.$tableName, function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('shortname')->nullable();
                $table->string('code')->nullable();
                $table->smallInteger('status')->default(1);
                $table->uuid('tenant_id')->nullable();
                $table->jsonb('src')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->index(['tenant_id', 'status']);
                $table->unique(['tenant_id', 'code']);
            });
        }

        Schema::create('wms.orders', function (Blueprint $table): void {
            $table->id();
            $table->string('code');
            $table->string('number', 64)->nullable();
            $table->foreignId('client_id')->nullable()->constrained('clients.clients')->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('wms.warehouses')->nullOnDelete();
            $table->foreignId('delivery_service_id')->nullable()->constrained('wms.delivery_services')->nullOnDelete();
            $table->foreignId('order_status_id')->nullable()->constrained('wms.order_statuses')->nullOnDelete();
            $table->foreignId('order_source_id')->nullable()->constrained('wms.order_sources')->nullOnDelete();
            $table->foreignId('order_cancel_status_id')->nullable()->constrained('wms.order_cancel_statuses')->nullOnDelete();
            $table->foreignId('integration_id')->nullable()->constrained('integration.webhooks')->nullOnDelete();
            $table->string('delivery_track', 128)->nullable();
            $table->date('delivery_date')->nullable();
            $table->dateTime('created_date')->nullable();
            $table->string('currency', 24)->nullable();
            $table->decimal('goods_total_price', 15, 2)->nullable();
            $table->integer('goods_count')->nullable();
            $table->text('comment_partner')->nullable();
            $table->text('comment_internal')->nullable();
            $table->jsonb('custom')->nullable();
            $table->boolean('need_imei')->default(false);
            $table->boolean('need_uin')->default(false);
            $table->boolean('need_gtin')->default(false);
            $table->boolean('need_sgtin')->default(false);
            $table->boolean('need_expiration')->default(false);
            $table->boolean('need_gtd')->default(false);
            $table->boolean('is_b2b')->default(false);
            $table->boolean('is_crossborder')->default(false);
            $table->string('wb_supply_id', 64)->nullable();
            $table->unsignedBigInteger('crm_party_id')->nullable();
            $table->unsignedBigInteger('crm_party_address_id')->nullable();
            $table->smallInteger('status')->default(1);
            $table->uuid('tenant_id')->nullable();
            $table->jsonb('src')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'client_id']);
            $table->index(['tenant_id', 'created_date']);
        });

        Schema::create('wms.order_goods', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained('wms.orders')->cascadeOnDelete();
            $table->foreignId('good_id')->nullable()->constrained('goods.goods')->nullOnDelete();
            $table->string('code');
            $table->integer('count')->default(0);
            $table->decimal('price', 15, 2)->nullable();
            $table->string('barcode_from_integration', 128)->nullable();
            $table->boolean('need_marking')->default(false);
            $table->uuid('tenant_id')->nullable();
            $table->jsonb('src')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'code']);
        });

        Schema::create('wms.order_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained('wms.orders')->cascadeOnDelete();
            $table->string('code');
            $table->string('action', 128)->nullable();
            $table->string('good_code', 64)->nullable();
            $table->text('value_old')->nullable();
            $table->text('value_new')->nullable();
            $table->dateTime('event_date')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->uuid('tenant_id')->nullable();
            $table->jsonb('src')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'code']);
        });

        Schema::create('wms.order_delivery_details', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained('wms.orders')->cascadeOnDelete();
            $table->string('code')->nullable();
            $table->string('delivery_track', 128)->nullable();
            $table->string('address')->nullable();
            $table->jsonb('data')->nullable();
            $table->uuid('tenant_id')->nullable();
            $table->jsonb('src')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('wms.shipments', function (Blueprint $table): void {
            $table->id();
            $table->string('code');
            $table->foreignId('order_id')->nullable()->constrained('wms.orders')->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients.clients')->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('wms.warehouses')->nullOnDelete();
            $table->foreignId('shipment_status_id')->nullable()->constrained('wms.shipment_statuses')->nullOnDelete();
            $table->dateTime('created_date')->nullable();
            $table->dateTime('checked_at')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->uuid('tenant_id')->nullable();
            $table->jsonb('src')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'code']);
        });
    }

    public function down(): void
    {
        foreach (['shipments', 'order_delivery_details', 'order_histories', 'order_goods', 'orders', 'shipment_statuses', 'logistic_companies', 'order_cancel_statuses', 'order_sources', 'order_statuses'] as $table) {
            Schema::dropIfExists('wms.'.$table);
        }
    }
};
