<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('integration_idempotency', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id')->index();

            // مفتاح Idempotency-Key القادم من العميل
            $table->string('idempotency_key', 191);
            // بصمة الطلب (method + path + body) لاكتشاف إعادة الاستخدام بجسم مختلف
            $table->string('request_hash', 64);

            $table->unsignedSmallInteger('http_status');
            // رد الطلب المخزَّن لإعادته حرفياً عند التكرار
            $table->json('response')->nullable();

            $table->timestamp('created_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();

            $table->unique(['client_id', 'idempotency_key'], 'uniq_integration_idem_client_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_idempotency');
    }
};
