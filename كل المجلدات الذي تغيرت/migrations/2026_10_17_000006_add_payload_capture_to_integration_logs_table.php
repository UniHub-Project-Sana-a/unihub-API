<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('integration_logs', function (Blueprint $table) {
            // التقاط الحمولة (مقتطعة بحدّ أقصى) + أحجام الطلب/الرد
            $table->mediumText('request_payload')->nullable()->after('mac');
            $table->unsignedInteger('request_bytes')->nullable()->after('request_payload');
            $table->unsignedInteger('response_bytes')->nullable()->after('duration_ms');
        });
    }

    public function down(): void
    {
        Schema::table('integration_logs', function (Blueprint $table) {
            $table->dropColumn(['request_payload', 'request_bytes', 'response_bytes']);
        });
    }
};
