<?php

// use Illuminate\Support\Facades\Schema;
// use Illuminate\Database\Schema\Blueprint;
use Iluminate\Support\Facades\Schema;
use Jenssegers\Mongodb\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHistoricalDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */

   protected $connection = "mongodb";

   public function up()
   {
      Schema::connection($this->connection)->create('historical_data2', function (Blueprint $table) {
         //$table->increments('id');
         $table->string('server');
         $table->string('object_name');
         $table->string('ip_initial');
         $table->string('ip_last');
         $table->string('type');
         $table->string('class');
         $table->integer('status');
         $table->timestamps();
         $table->index(['server', 'object_name', 'ip_initial', 'ip_last']);
      });
   }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
