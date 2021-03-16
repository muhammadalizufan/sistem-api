<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserPermissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection("account")->create('user_permissions', function (Blueprint $table) {
            $table->id();
            $table->bigInteger("user_id")->default(0)->index()->comment("This field related to table users");
            $table->bigInteger("group_id")->default(0)->index()->comment("This field related to table groups");
            $table->bigInteger("role_id")->default(0)->index()->comment("This field related to table roles");
            $table->bigInteger("permission_id")->default(0)->index()->comment("This field related to table permissions");
            $table->boolean("is_active")->default(false)->comment("0 = In-Active; 1 = Active;");
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
        Schema::connection("account")->dropIfExists('user_permissions');
    }
}
