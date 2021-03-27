<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateActivitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection("extension")->create('activities', function (Blueprint $table) {
            $table->id();
            $table->bigInteger("user_id")->default(0)->index()->comment("This field related to table users");
            $table->tinyInteger("ref_type")->default(0);
            $table->bigInteger("ref_id")->default(0)->index()->comment("These field are related by reference type");
            $table->string("action")->default("");
            $table->string("message_id")->default("");
            $table->string("message_en")->default("");
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
        Schema::connection("extension")->dropIfExists('activities');
    }
}
