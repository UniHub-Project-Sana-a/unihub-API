<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('block_relations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('block_id')->constrained('blocks')->onDelete('cascade');
            $table->foreignId('related_block_id')->constrained('blocks')->onDelete('cascade');
            // نوع العلاقة: prerequisite (متطلب), concurrent (مجاور), next (تالي)
            $table->enum('relation_type', ['prerequisite', 'concurrent', 'next']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('block_relations');
    }
};
