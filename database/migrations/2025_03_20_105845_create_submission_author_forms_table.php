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
        Schema::create('submission_author_forms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id')->index();
            $table->foreign('project_id')->references('id')->on('entry_processes')->onDelete('cascade');
            $table->string('journal_name')->nullable();
            $table->string('type_of_article')->nullable();
            $table->string('article_id')->nullable();
            $table->string('review')->nullable();
            $table->string('date_of_submission')->nullable();
            $table->string('journal_fee')->nullable();
            $table->integer('created_by');

            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('submission_author_forms');
    }
};