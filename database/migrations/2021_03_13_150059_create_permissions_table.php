<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePermissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection("account")->create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string("name")->default("");
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
        Schema::connection("account")->dropIfExists('permissions');
    }
}
