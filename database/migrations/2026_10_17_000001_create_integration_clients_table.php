<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('integration_clients', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();

            // التوكن يُخزَّن كـ hash (sha256) وليس كنص صريح
            $table->string('token_hash', 64)->unique();

            // قوائم العناوين والأجهزة المسموحة (يدعم صيغ CIDR مثل 10.0.0.0/24)
            $table->json('allowed_ips')->nullable();
            $table->json('allowed_macs')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_clients');
    }
};
