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
        // 1️⃣ تعديل جدول courses
        Schema::table('courses', function (Blueprint $table) {
            // ✅ أجزاء المقرر (JSON)
            $table->json('course_parts')->nullable()->after('credit_hours')
                  ->comment('أجزاء المقرر: نظري، عملي، تمارين، سريري');
            
            // ✅ وزن المقرر
            $table->decimal('weight', 5, 2)->default(0)->after('course_parts')
                  ->comment('وزن المقرر % من مخرجات البرنامج');
            
            // ✅ تصنيف المقرر (ENUM)
            $table->enum('category', [
                'متطلب جامعة',
                'متطلب كلية',
                'متطلب تخصص إجباري',
                'متطلب تخصص اختياري'
            ])->default('متطلب تخصص إجباري')->after('weight');
            
            // ✅ لغة التدريس
            $table->enum('teaching_language', ['العربية', 'الإنجليزية', 'ثنائي اللغة'])
                  ->default('العربية')->after('category');
            
            // ✅ ربط بالبلوك
            $table->unsignedBigInteger('block_id')->nullable()->after('semester_id');
            
            // تعديل semester_id ليكون nullable
            $table->unsignedInteger('semester_id')->nullable()->change();
            
            // Foreign Key
            $table->foreign('block_id')->references('id')->on('blocks')->onDelete('set null');
        });

        // 2️⃣ جدول المتطلبات السابقة/المصاحبة (علاقات many-to-many)
        Schema::create('course_prerequisites', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('course_id')->comment('المقرر الحالي');
            $table->unsignedInteger('prerequisite_course_id')->comment('المقرر المطلوب');
            $table->enum('type', ['prerequisite', 'corequisite'])->default('prerequisite')
                  ->comment('prerequisite=سابق، corequisite=مصاحب');
            $table->timestamps();
            
            $table->foreign('course_id')->references('course_id')->on('courses')->onDelete('cascade');
            $table->foreign('prerequisite_course_id')->references('course_id')->on('courses')->onDelete('cascade');
            
            // منع التكرار
            $table->unique(['course_id', 'prerequisite_course_id', 'type'], 'course_prereq_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_prerequisites');
        
        Schema::table('courses', function (Blueprint $table) {
            $table->dropForeign(['block_id']);
            $table->dropColumn([
                'course_parts',
                'weight',
                'category',
                'teaching_language',
                'block_id'
            ]);
        });
    }
};