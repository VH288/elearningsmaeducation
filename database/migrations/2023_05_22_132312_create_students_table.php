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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->string("pet_name")->nullable();
            $table->string("gender")->nullable();
            $table->string("birth_place")->nullable();
            $table->date("birth_date")->nullable();
            $table->string("religion")->nullable();
            $table->string("address")->nullable();
            $table->string("nis")->unique();
            $table->string("photo")->nullable();
            $table->foreignId("user_id")->constraint();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
