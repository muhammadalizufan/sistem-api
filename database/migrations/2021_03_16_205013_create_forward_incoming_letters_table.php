<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateForwardIncomingLettersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection("siap")->create('forward_incoming_letters', function (Blueprint $table) {
            $table->id();
            $table->bigInteger("incoming_letter_id")->default(0)->index()->comment("This field related to table incoming_letters");
            $table->bigInteger("user_id")->default(0)->index()->comment("This field related to table users");
            $table->tinyInteger("types")->default(0)->comment("0 = creator; 1 = decision; 2 = reponder;");
            $table->text("comment")->nullable();
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
        Schema::connection("siap")->dropIfExists('forward_incoming_letters');
    }
}
