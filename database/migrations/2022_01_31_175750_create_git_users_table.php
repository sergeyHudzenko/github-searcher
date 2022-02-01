<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGitUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('git_users', function (Blueprint $table) {
            $table->id()->unique();
            $table->string('login')->unique();
            $table->string('avatar_url');
            $table->integer('total_repos')->default(0);
            $table->string('email')->nullable();
            $table->string('location')->nullable();
            $table->date('created_at');
            $table->date('updated_at');
            $table->integer('followers')->default(0); 
            $table->integer('following')->default(0);
            $table->integer('popularity')->default(0);
            $table->integer('popularity_by_date')->default(0);
            $table->date('popularity_date')->nullable();; 
            $table->text('bio')->nullable();
            $table->boolean('full_loaded')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('git_users');
    }
}
