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
        Schema::create('profile', function (Blueprint $table) {
            $table->id();
       
            $table->string('full_name');
            $table->string('profile_image');
            $table->string('email');
            $table->string('password');
            $table->date('dob');
            $table->string('permanent_address');
            $table->string('present_address');  
            $table->string('city');
            $table->string('post_code');
            $table->string('country');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profile');
    }
};
