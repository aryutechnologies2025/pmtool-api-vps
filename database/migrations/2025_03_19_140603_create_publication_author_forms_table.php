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
        Schema::create('publication_author_forms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id')->index();
            $table->foreign('project_id')->references('id')->on('entry_processes')->onDelete('cascade');
            
            $table->string('initial')->nullable();
            
            
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            
            $table->unsignedBigInteger('profession_id')->index();
            $table->foreign('profession_id')->references('id')->on('profession_types')->onDelete('cascade');
            
            $table->unsignedBigInteger('department_id')->index();
            $table->foreign('department_id')->references('id')->on('department_types')->onDelete('cascade'); 
                       
            $table->unsignedBigInteger('institute_id')->index();
            $table->foreign('institute_id')->references('id')->on('institutions')->onDelete('cascade'); 
            
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            
            $table->integer('created_by');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('publication_author_forms');
    }
};