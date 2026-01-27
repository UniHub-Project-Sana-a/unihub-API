<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        /*
        |--------------------------------------------------
        | إنشاء جدول notification_reads (إذا لم يكن موجودًا)
        |--------------------------------------------------
        */
        if (!Schema::hasTable('notification_reads')) {
            Schema::create('notification_reads', function (Blueprint $table) {
                $table->increments('read_id');
                $table->unsignedInteger('user_id');
                $table->unsignedInteger('notification_id');
                $table->timestamp('read_at')->nullable()->useCurrent();
    
                $table->unique(['user_id', 'notification_id'], 'unique_user_notification');
    
                $table->foreign('user_id', 'fk_read_user')
                      ->references('user_id')
                      ->on('users')
                      ->onDelete('cascade');
    
                $table->foreign('notification_id', 'fk_read_notification')
                      ->references('notification_id')
                      ->on('lecturer_group_notifications')
                      ->onDelete('cascade');
            });
        }
    
        /*
        |--------------------------------------------------
        | إضافة عمود excuse_image إذا لم يكن موجودًا
        |--------------------------------------------------
        */
        Schema::table('student_excuse_submissions', function (Blueprint $table) {
            if (!Schema::hasColumn('student_excuse_submissions', 'excuse_image')) {
                $table->string('excuse_image')->nullable();
            }
        });
    }


    public function down(): void
    {
        // لا نحذف الجدول لأنه كان موجودًا مسبقًا
        if (Schema::hasColumn('student_excuse_submissions', 'excuse_image')) {
            Schema::table('student_excuse_submissions', function (Blueprint $table) {
                $table->dropColumn('excuse_image');
            });
        }
    }
};
