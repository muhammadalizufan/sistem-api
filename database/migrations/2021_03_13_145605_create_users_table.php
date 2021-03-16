<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection("account")->create('users', function (Blueprint $table) {
            $table->id();
            $table->string("name")->default("");
            $table->string("username")->default("");
            $table->string("email")->default("");
            $table->string("password")->default("");
            $table->string("pin")->default("");
            $table->string("access_token")->default("");
            $table->boolean("use_twofa")->default(0)->comment("0 = InActive; 1 = Active;");
            $table->tinyInteger("status")->default(0)->comment("0 = InActive; 1 = Active; 2 = Locked; 3 = Blocked;");
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
        Schema::connection("account")->dropIfExists('users');
    }
}
