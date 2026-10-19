<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('periods', 'session_type')) {
            Schema::table('periods', function (Blueprint $table) {
                $table->dropColumn('session_type');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('periods', 'session_type')) {
            Schema::table('periods', function (Blueprint $table) {
                $table->string('session_type', 10)->default('AM');
            });
        }
    }
};
