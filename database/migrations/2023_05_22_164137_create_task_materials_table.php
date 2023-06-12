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
        Schema::create('task_materials', function (Blueprint $table) {
            $table->id();
            $table->string("title");
            $table->string("description");
            $table->date("distribute_date");
            $table->datetime("start_date")->nullable();
            $table->datetime("deadline")->nullable();
            $table->string("file_path")->nullable();
            $table->foreignId("class_room_id")->constraint();
            $table->foreignId("task_material_type_id")->constraint();
            $table->foreignId("subject_id")->constraint();
            $table->foreignId("question_bank_id")->nullable()->constraint();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_materials');
    }
};
