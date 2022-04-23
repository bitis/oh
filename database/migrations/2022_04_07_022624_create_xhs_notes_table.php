<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateXhsNotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('xhs_notes', function (Blueprint $table) {
            $table->id();
            $table->string('x_id');
            $table->string('title')->nullable();
            $table->text('desc')->nullable();
            $table->boolean('isLiked');
            $table->string('type');
            $table->string('time');
            $table->boolean('notified');
            $table->string('created_at');
            $table->string('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('xhs_notes');
    }
}
