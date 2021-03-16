<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGroupPermissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection("account")->create('group_permissions', function (Blueprint $table) {
            $table->id();
            $table->bigInteger("group_id")->default(0)->index()->comment("This field related to table groups");
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
        Schema::connection("account")->dropIfExists('group_permissions');
    }
}
