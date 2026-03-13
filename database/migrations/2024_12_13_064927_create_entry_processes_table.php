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
        Schema::create('entry_processes', function (Blueprint $table) {
            $table->id();
            $table->string('entry_date')->nullable();
            $table->string('title')->nullable();
            $table->string('project_id')->unique();
            $table->string('type_of_work')->nullable();
            $table->string('others')->nullable();
            $table->string('select_document')->nullable();
            $table->string('file')->nullable();
            $table->string('client_name')->nullable();
            $table->string('email')->nullable();
            $table->string('contact_number')->nullable();   
            $table->string('institute')->nullable();    
            $table->string('department')->nullable();
            $table->string('profession')->nullable();
            $table->string('budget')->nullable();
            $table->string('hierarchy_level')->nullable();
            $table->string('comment_box')->nullable();
            //writer
            $table->integer('writer')->nullable();
            $table->date('writer_assigned_date')->nullable();
            $table->string('writer_status')->nullable();
            $table->date('writer_status_date')->nullable();
            //reviewer
            $table->integer('reviewer')->nullable();
            $table->date('reviewer_assigned_date')->nullable();
            $table->string('reviewer_status')->nullable();
            $table->date('reviewer_status_date')->nullable();
            //statistican
            $table->integer('statistican')->nullable();
            $table->date('statistican_assigned_date')->nullable();
            $table->string('statistican_status')->nullable();
            $table->date('statistican_status_date')->nullable();
            $table->string('writer_project_duration')->nullable();
            $table->string('reviewer_project_duration')->nullable();
            $table->boolean('status')->default(1);
            $table->boolean('is_deleted')->default(0);
            $table->integer('created_by');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entry_processes');
    }
};
