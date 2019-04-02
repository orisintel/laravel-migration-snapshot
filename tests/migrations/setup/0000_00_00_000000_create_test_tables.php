<?php

class CreateTestTables extends \Illuminate\Database\Migrations\Migration
{
    public function up()
    {
        Schema::create('test_ms', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->increments('id');
            $table->string('name', 100);
            $table->timestamps();
        });

        \DB::table('test_ms')->insert(['name' => 'one']);
    }

    public function down()
    {
        Schema::drop('test_ms');
    }
}
