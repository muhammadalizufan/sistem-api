<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRequestDatasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('request_datas', function (Blueprint $table) {
            $table->id();
            $table->bigInteger("user_id")->default(0)->index()->comment("This field related to table users");
            $table->bigInteger("cat_id")->default(0)->index()->comment("This field related to table categories");
            $table->string("code")->default("");
            $table->string("requested_data")->default("");
            $table->string("requester")->default("");
            $table->string("agency")->default("");
            $table->string("email")->default("");
            $table->string("phone")->default("");
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
        Schema::dropIfExists('request_datas');
    }
}
