<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReversalTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reversal', function (Blueprint $table) {
            $table->id();
            $table->integer('lead_id');
            $table->float('reversal_amount');
            $table->date('reversal_date');
            $table->foreignId('reversal_user_id')->constrained('users','id')->onDelete('cascade');
            $table->enum('reversal_type',["Partial","Full"]);
            $table->foreignId('merchant_id')->constrained('merchant','id')->onDelete('cascade');
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
        Schema::dropIfExists('reversal');
    }
}
