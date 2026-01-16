<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ip_restrictions', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // 'whitelist' أو 'blacklist'
            $table->string('ip_address'); // IP مثل 192.168.1.1 أو CIDR
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ip_restrictions');
    }
};