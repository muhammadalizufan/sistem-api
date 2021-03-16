<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIncomingLettersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection("siap")->create('incoming_letters', function (Blueprint $table) {
            $table->id();
            $table->bigInteger("user_id")->default(0)->index()->comment("This field related to table users");
            $table->string("code");
            $table->string("title");
            $table->string("from");
            $table->timestamp("date");
            $table->timestamp("dateline");
            $table->text("file");
            $table->text("desc");
            $table->text("note");
            $table->tinyInteger("status")->default(0)->comment("0 = Procces; 1 = Success; 2 = Failed;");
            $table->boolean("is_archive")->default(0);
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
        Schema::connection("siap")->dropIfExists('incoming_letters');
    }
}
