<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTagIncomingLettersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection("siap")->create('tag_incoming_letters', function (Blueprint $table) {
            $table->id();
            $table->bigInteger("incoming_letter_id")->default(0)->index()->comment("This field related to table incoming_letters");
            $table->bigInteger("tag_id")->default(0)->index()->comment("This field related to table tags");
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection("siap")->dropIfExists('tag_incoming_letters');
    }
}
