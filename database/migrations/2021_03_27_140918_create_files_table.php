<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection("extension")->create('files', function (Blueprint $table) {
            $table->id();
            $table->string("name")->default("");
            $table->string("fullname")->default("");
            $table->tinyInteger("ref_type")->default(0);
            $table->bigInteger("ref_id")->default(0)->index()->comment("These field are related by reference type");
            $table->string("ext")->default("");
            $table->string("path")->default("");
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
        Schema::connection("extension")->dropIfExists('files');
    }
}
