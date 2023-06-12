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
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->date("start_date")->nullable();
            $table->date("birth_date")->nullable();
            $table->string("address")->nullable();
            $table->string("last_education")->nullable();
            $table->string("institute_name")->nullable();
            $table->string("phone_number")->nullable();
            $table->string("nik")->unique();
            $table->string("photo")->nullable();
            $table->foreignId("teacher_position_id")->constraint();
            $table->foreignId("user_id")->constraint();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teachers');
    }
};
