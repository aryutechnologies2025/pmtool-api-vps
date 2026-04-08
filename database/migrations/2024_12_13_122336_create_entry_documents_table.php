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
        Schema::create('entry_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('entry_process_model_id'); // Foreign key to entry_processes
            $table->string('select_document')->nullable();
            $table->string('file')->nullable();
            $table->timestamps();
        
            $table->foreign('entry_process_model_id')->references('id')->on('entry_processes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entry_documents');
    }
};
