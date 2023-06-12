<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
        /**
         * php artisan storage:link
         * before seeding, run
         * php artisan passport:install
         * then run 
         * php artisan passport:keys
         * then run 
         * php artisan db:seed
        */
        DB::table('user_roles')->insert([
            'name' => 'admin'
        ]);
        DB::table('user_roles')->insert([
            'name' => 'teacher'
        ]);
        DB::table('user_roles')->insert([
            'name' => 'student'
        ]);
        DB::table('users')->insert([
            'username' => 'admin',
            'email' => 'admin288@gmail.com',
            'password' => Hash::make('password123'),
            'user_role_id' => '1',
        ]);
        DB::table('teacher_positions')->insert([
            'name' => 'Principal'
        ]);
        DB::table('teacher_positions')->insert([
            'name' => 'Admin'
        ]);
        DB::table('teacher_positions')->insert([
            'name' => 'English Teacher'
        ]);
        DB::table('teacher_positions')->insert([
            'name' => 'Math Teacher'
        ]);
        DB::table('teacher_positions')->insert([
            'name' => 'Science Teacher'
        ]);
        DB::table('teachers')->insert([
            'name' => 'admin',
            'nik' => '190710346',
            'teacher_position_id' => '2',
            'user_id' => '1',
            'phone_number' => '082288779931'
        ]);
        DB::table('guardian_types')->insert([
            'name' => 'Father',
        ]);
        DB::table('guardian_types')->insert([
            'name' => 'Mother',
        ]);
        DB::table('guardian_types')->insert([
            'name' => 'Grandfather',
        ]);
        DB::table('guardian_types')->insert([
            'name' => 'Grandmother',
        ]);
        DB::table('guardian_types')->insert([
            'name' => 'Uncle',
        ]);
        DB::table('guardian_types')->insert([
            'name' => 'Aunt',
        ]);
        DB::table('guardian_types')->insert([
            'name' => 'Other',
        ]);
        DB::table('class_levels')->insert([
            'class_level' => 1,
            'major' => 'IPA'
        ]);
        DB::table('class_levels')->insert([
            'class_level' => 2,
            'major' => 'IPA'
        ]);
        DB::table('class_levels')->insert([
            'class_level' => 3,
            'major' => 'IPA'
        ]);
        DB::table('class_levels')->insert([
            'class_level' => 1,
            'major' => 'IPS'
        ]);
        DB::table('class_levels')->insert([
            'class_level' => 2,
            'major' => 'IPS'
        ]);
        DB::table('class_levels')->insert([
            'class_level' => 3,
            'major' => 'IPS'
        ]);
        DB::table('task_material_types')->insert([
            'name' => "Material",
        ]);
        DB::table('task_material_types')->insert([
            'name' => "Upload Task",
        ]);
        DB::table('task_material_types')->insert([
            'name' => "Question Task",
        ]);
        DB::table('question_types')->insert([
            'name' => "5 Options",
        ]);
        DB::table('question_types')->insert([
            'name' => "Question and Answer",
        ]);
        DB::table('question_types')->insert([
            'name' => "Essay",
        ]);
        DB::table('score_categories')->insert([
            'name' => "Quarter Exam",
        ]);
        DB::table('score_categories')->insert([
            'name' => "Unit Test",
        ]);
        DB::table('score_categories')->insert([
            'name' => "Class Standing",
        ]);
        DB::table('score_categories')->insert([
            'name' => "Exercise",
        ]);
    }
}
