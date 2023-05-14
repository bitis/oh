<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
        Schema::create('nga_replies', function (Blueprint $table) {
            $table->id();
            $table->string('reply_id');
            $table->text('content');
            $table->string('author');
            $table->string('authorid');
            $table->string('subject');
            $table->string('subject_id');
            $table->dateTime('postdate');
            $table->boolean('notified')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('nga_replies');
    }
};
