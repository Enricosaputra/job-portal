<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE applications MODIFY COLUMN status 
            ENUM('pending', 'reviewed', 'hired', 'rejected', 'completed') 
            NOT NULL DEFAULT 'pending'");
    }

    public function down()
    {
        DB::statement("ALTER TABLE applications MODIFY COLUMN status 
            ENUM('pending', 'reviewed', 'hired', 'rejected') 
            NOT NULL DEFAULT 'pending'");
    }
};
