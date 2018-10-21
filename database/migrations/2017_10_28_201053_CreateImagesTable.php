<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateImagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('images', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code')->length(20);
            $table->string('filename');
            $table->string('extension')->length(10);
            $table->string('image');
        });

//        \DB::statement("ALTER TABLE images ADD image LONGBLOB");

        Schema::table('images', function(Blueprint $table) {
            $table->integer('size')->default(0);
            $table->string('ip');
            $table->boolean('active')->default(true);
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
        Schema::drop('images');
    }
}
