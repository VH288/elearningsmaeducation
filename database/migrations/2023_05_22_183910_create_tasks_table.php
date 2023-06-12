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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->datetime("do_date");
            $table->string("description")->nullable();
            $table->string("file_path")->nullable();
            $table->integer("student_score")->nullable();
            $table->integer("check");
            $table->foreignId("task_material_id")->constraint();
            $table->foreignId("student_id")->constraint();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
