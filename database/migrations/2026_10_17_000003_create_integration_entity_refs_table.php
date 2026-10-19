<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('integration_entity_refs', function (Blueprint $table) {
            $table->id();

            // العميل الخارجي الذي أرسل السجل (نظام الجامعة)
            $table->unsignedBigInteger('client_id')->nullable()->index();

            // اسم المورد (colleges, departments, students ...)
            $table->string('entity', 100);

            // معرّف السجل عند النظام الخارجي (اختياري — يُستخدم إن أرسلوه)
            $table->string('external_id', 191)->nullable();

            // مفتاح السجل عندنا + الكود الطبيعي له
            $table->unsignedBigInteger('internal_id');
            $table->string('internal_key', 255)->nullable();

            $table->timestamps();

            $table->unique(
                ['client_id', 'entity', 'external_id'],
                'integration_refs_client_entity_ext_unique'
            );
            $table->index(['entity', 'internal_id'], 'integration_refs_entity_internal_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_entity_refs');
    }
};
