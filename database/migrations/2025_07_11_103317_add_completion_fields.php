<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Jobs table updates
        Schema::table('jobs', function (Blueprint $table) {
            $table->timestamp('completed_at')->nullable();
        });

        // Change status column using raw SQL to avoid ENUM error
        DB::statement("ALTER TABLE jobs MODIFY COLUMN status ENUM('draft', 'published', 'completed') DEFAULT 'draft'");

        // Applications table updates
        Schema::table('applications', function (Blueprint $table) {
            $table->text('completion_notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedTinyInteger('rating')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users');
        });

        // Honor points table updates
        Schema::table('honor_points', function (Blueprint $table) {
            $table->foreignId('awarded_by')->constrained('users');
            $table->text('notes')->nullable();
        });

        // Users table updates
        Schema::table('users', function (Blueprint $table) {
            $table->integer('total_points')->default(0);
        });
    }

    public function down()
    {
        // Reverse jobs table changes
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropColumn('completed_at');
        });
        DB::statement("ALTER TABLE jobs MODIFY COLUMN status ENUM('draft', 'published') DEFAULT 'draft'");

        // Reverse applications table changes
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn(['completion_notes', 'completed_at', 'rating', 'completed_by']);
        });

        // Reverse honor_points table changes
        Schema::table('honor_points', function (Blueprint $table) {
            $table->dropForeign(['awarded_by']);
            $table->dropColumn(['awarded_by', 'notes']);
        });

        // Reverse users table changes
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('total_points');
        });
    }
};
