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
        Schema::create('comments_list', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id')->index();
            $table->foreign('project_id')->references('id')->on('entry_processes')->onDelete('cascade');
            $table->unsignedBigInteger('comment_id'); // This stores the model id ('App\Models\Activity' or 'App\Models\ActivityReply')
            $table->string('commend_type'); // This stores the model type ('App\Models\Activity' or 'App\Models\ActivityReply')
            $table->integer('created_by');
            $table->boolean('is_read')->default(0);
            $table->string('created_date');
            $table->timestamps();
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comments_list');
    }
};
