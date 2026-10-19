<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('integration_clients', function (Blueprint $table) {
            // حدّ الطلبات لكل دقيقة لهذا العميل (NULL = الافتراضي العام)
            $table->unsignedInteger('rate_limit_per_minute')->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('integration_clients', function (Blueprint $table) {
            $table->dropColumn('rate_limit_per_minute');
        });
    }
};
