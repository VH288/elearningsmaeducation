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
        Schema::create('score_sub_categories', function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->string("short_name");
            $table->foreignId("score_category_id")->constraint();
            $table->foreignId("score_id")->constraint();
            $table->foreignId("task_material_id")->nullable()->constraint();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('score_sub_categories');
    }
};
