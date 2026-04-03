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
        // 1. تعديل جدول الأجهزة (User Devices)
        Schema::table('user_devices', function (Blueprint $table) {
            if (!Schema::hasColumn('user_devices', 'device_identifier')) {
                $table->string('device_identifier', 255)->nullable()->after('mac_address');
            }
            if (!Schema::hasColumn('user_devices', 'installation_path')) {
                $table->string('installation_path', 255)->nullable()->after('os_type');
            }
        });

        // 2. تعديل جدول التحقق (OTP Verifications)
        Schema::table('otp_device_verifications', function (Blueprint $table) {
            if (!Schema::hasColumn('otp_device_verifications', 'device_identifier')) {
                $table->string('device_identifier', 255)->nullable()->after('mac_address');
            }
            if (!Schema::hasColumn('otp_device_verifications', 'installation_path')) {
                $table->string('installation_path', 255)->nullable()->after('os_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_devices', function (Blueprint $table) {
            $table->dropColumn(['device_identifier', 'installation_path']);
        });

        Schema::table('otp_device_verifications', function (Blueprint $table) {
            $table->dropColumn(['device_identifier', 'installation_path']);
        });
    }
};