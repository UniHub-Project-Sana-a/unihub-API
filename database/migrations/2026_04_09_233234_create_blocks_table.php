<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('blocks', function (Blueprint $table) {
            // المفتاح الأساسي للبلوك
            $table->id(); 
            
            $table->string('block_name');
            $table->integer('block_number');
            $table->decimal('weight', 5, 2)->default(0);
            $table->decimal('credit_hours', 5, 2)->default(0);
            $table->integer('weeks')->default(1);
            $table->enum('type', ['compulsory', 'elective'])->default('compulsory');
            
            // يجب استخدام unsignedInteger لتطابق increments في الجداول الأخرى
            $table->unsignedInteger('program_id');
            $table->unsignedInteger('level_id')->nullable(); 
    
            // الربط بجدول البرامج
            $table->foreign('program_id')
                  ->references('program_id') // العمود المرجع في جدول programs
                  ->on('programs')
                  ->onDelete('cascade');
    
            // الربط بجدول المستويات
            $table->foreign('level_id')
                  ->references('level_id') // العمود المرجع في جدول levels
                  ->on('levels')
                  ->onDelete('set null');
            
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('blocks');
    }
};
