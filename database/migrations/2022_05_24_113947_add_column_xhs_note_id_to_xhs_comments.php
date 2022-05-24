<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnXhsNoteIdToXhsComments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('xhs_comments', function (Blueprint $table) {
            $table->integer('xhs_note_id')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('xhs_comments', function (Blueprint $table) {
            $table->dropColumn('xhs_note_id');
        });
    }
}
