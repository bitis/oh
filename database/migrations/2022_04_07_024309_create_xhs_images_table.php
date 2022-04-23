<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateXhsImagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('xhs_images', function (Blueprint $table) {
            $table->id();
            $table->integer('xhs_note_id');
            $table->string('fileId');
            $table->integer('height');
            $table->integer('width');
            $table->string('url');
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
        Schema::dropIfExists('xhs_images');
    }
}
