<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOutgoingLettersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection("siap")->create('outgoing_letters', function (Blueprint $table) {
            $table->id();
            $table->bigInteger("user_id")->default(0)->index()->comment("This field related to table users");
            $table->bigInteger("cat_id")->default(0)->index()->comment("This field related to table categories");
            $table->string("code")->default("");
            $table->string("title")->default("");
            $table->string("to")->default("");
            $table->text("address")->nullable();
            $table->string("agency")->default("");
            $table->text("note")->nullable();
            $table->text("original_letter")->nullable();
            $table->text("validated_letter")->nullable();
            $table->tinyInteger("status")->default(0)->comment("0 = Process; 1 = Approved; 2 = Rejected;");
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
        Schema::connection("siap")->dropIfExists('outgoing_letters');
    }
}
