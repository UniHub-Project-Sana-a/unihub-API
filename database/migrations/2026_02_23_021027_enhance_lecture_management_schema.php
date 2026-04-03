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
        // -------------------------------------------------------------
        // 1. إنشاء جدول مرفقات المحاضرة (Lecture Attachments)
        // -------------------------------------------------------------
        if (!Schema::hasTable('lecture_attachments')) {
            Schema::create('lecture_attachments', function (Blueprint $table) {
                $table->increments('attachment_id');
                $table->unsignedInteger('session_id'); // ربط بالجلسة
                
                $table->enum('type', ['video', 'file', 'link']);
                $table->string('title', 200);
                $table->text('url'); // رابط الملف أو الفيديو
                $table->string('file_size', 50)->nullable(); // حجم الملف للعرض (مثلاً 5MB)
                
                $table->timestamps(); // created_at, updated_at

                // الربط والحذف التلقائي
                $table->foreign('session_id')
                      ->references('session_id')->on('lecture_sessions')
                      ->onDelete('cascade');
            });
        }

        // -------------------------------------------------------------
        // 2. تعديل جدول الجلسات (Lecture Sessions) - إضافة الإنهاء والموقع
        // -------------------------------------------------------------
        Schema::table('lecture_sessions', function (Blueprint $table) {
            // وقت الإنهاء الفعلي (يختلف عن end_time المجدول)
            if (!Schema::hasColumn('lecture_sessions', 'actual_end_time')) {
                $table->timestamp('actual_end_time')->nullable()->after('end_time');
            }

            // إحداثيات موقع الإنهاء (للتأكد من التواجد في القاعة)
            if (!Schema::hasColumn('lecture_sessions', 'end_latitude')) {
                $table->decimal('end_latitude', 10, 7)->nullable()->after('actual_end_time');
            }
            if (!Schema::hasColumn('lecture_sessions', 'end_longitude')) {
                $table->decimal('end_longitude', 10, 7)->nullable()->after('end_latitude');
            }

            // علامة: هل تم الإنهاء عن بعد (خارج نطاق القاعة)؟
            if (!Schema::hasColumn('lecture_sessions', 'is_ended_remotely')) {
                $table->boolean('is_ended_remotely')->default(false)->after('end_longitude');
            }
        });

        // -------------------------------------------------------------
        // 3. تعديل جدول حضور الطلاب (Student Attendance) - إضافة Solved
        // -------------------------------------------------------------
        Schema::table('student_attendance', function (Blueprint $table) {
            // عمود نصي لتخزين ملاحظات أو حلول أو حالة خاصة بالحضور
            if (!Schema::hasColumn('student_attendance', 'solved')) {
                $table->text('solved')->nullable()->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. حذف جدول المرفقات
        Schema::dropIfExists('lecture_attachments');

        // 2. حذف أعمدة الجلسات
        Schema::table('lecture_sessions', function (Blueprint $table) {
            $table->dropColumn(['actual_end_time', 'end_latitude', 'end_longitude', 'is_ended_remotely']);
        });

        // 3. حذف عمود solved
        Schema::table('student_attendance', function (Blueprint $table) {
            $table->dropColumn('solved');
        });
    }
};