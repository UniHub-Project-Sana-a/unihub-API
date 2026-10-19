<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('integration_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id')->nullable()->index();

            $table->string('method', 10);
            $table->string('path', 255);
            $table->string('ip', 45)->nullable();
            $table->string('mac', 32)->nullable();

            $table->unsignedSmallInteger('http_status')->nullable();
            $table->json('summary')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();

            // سجل إلحاقي فقط (لا updated_at)
            $table->timestamp('created_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_logs');
    }
};
