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
        Schema::create('taog_gu_ba_replies', function (Blueprint $table) {
            $table->id();
            $table->string('reply_id');
            $table->string('user_name');
            $table->string('date');
            $table->string('from');
            $table->string('from_url');
            $table->string('content');
            $table->string('url');
            $table->string('images');
            $table->text('original');
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
        Schema::dropIfExists('taog_gu_ba_replies');
    }
};
