<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInboxsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection("siap")->create('inboxs', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('ref_id')->default(0)->index()->comment("This field related to table reference type");
            $table->string('ref_type')->default('')->index();
            $table->bigInteger('forward_to')->default(0)->index()->comment("This field related to table users");
            $table->string('user_type')->default('')->index();
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
        Schema::connection("siap")->dropIfExists('inboxs');
    }
}
