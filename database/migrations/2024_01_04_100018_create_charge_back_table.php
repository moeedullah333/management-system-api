<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChargeBackTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('charge_back', function (Blueprint $table) {
            $table->id();
            $table->integer('lead_id');
            $table->float('chargeback_amount');
            $table->foreignId('chargeback_user_id')->constrained('users','id')->onDelete('cascade');
            $table->enum('chargeback_type',["Partial","Full"]);
            $table->foreignId('merchant_id')->constrained('merchant','id')->onDelete('cascade');
            $table->enum('reason',["Fraudulent","Product Unacceptable","Product Not Received","Credit Not Processed","Other"]);
            $table->longText('description');
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
        Schema::dropIfExists('charge_back');
    }
}
