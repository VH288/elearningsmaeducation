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
        Schema::create('class_room_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId("subject_id")->constraint();
            $table->foreignId("teacher_id")->constraint();
            $table->foreignId("class_room_id")->constraint();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_room_subjects');
    }
};
