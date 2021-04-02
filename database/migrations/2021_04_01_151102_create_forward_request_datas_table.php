<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateForwardRequestDatasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('forward_request_datas', function (Blueprint $table) {
            $table->id();
            $table->bigInteger("request_data_id")->default(0)->index()->comment("This field related to table request datas");
            $table->tinyInteger("types")->default(0)->comment("0 = creator; 1 = approver; 2 = reponder;");
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
        Schema::dropIfExists('forward_request_datas');
    }
}
