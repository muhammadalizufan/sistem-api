<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRefreshTokensTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection("account")->create('refresh_tokens', function (Blueprint $table) {
            $table->id();
            $table->bigInteger("user_id")->default(0)->index()->comment("This field related to table users");
            $table->string("refresh_token")->default("");
            $table->string("user_agent")->default("");
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
        Schema::connection("account")->dropIfExists('refresh_tokens');
    }
}
