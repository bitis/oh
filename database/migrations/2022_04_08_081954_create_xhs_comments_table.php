<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateXhsCommentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('xhs_comments', function (Blueprint $table) {
            $table->id();
            $table->string('x_id');
            $table->integer('parent_id');
            $table->string('nickname');
            $table->string('user_id');
            $table->boolean('isSubComment')->default(false);
            $table->text('content');
            $table->integer('likes');
            $table->boolean('isLiked');
            $table->string('time');
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
        Schema::dropIfExists('xhs_comments');
    }
}
