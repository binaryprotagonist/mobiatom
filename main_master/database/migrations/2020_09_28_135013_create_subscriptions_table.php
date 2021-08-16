<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubscriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organisation_id');
            $table->unsignedBigInteger('software_id');
            $table->unsignedBigInteger('plan_id');
            $table->unsignedBigInteger('offer_id')->nullable();
            $table->string('stripe_subscription_id')->nullable();
            $table->date('trial_period_start_date')->nullable();
            $table->date('trial_period_end_date')->nullable();
            $table->boolean('subscribe_after_trial');
            $table->date('offer_start_date')->nullable();
            $table->date('offer_end_date')->nullable();
            $table->date('date_subscribed');
            $table->date('valid_to');
            $table->date('date_unsubscribed')->nullable();

            $table->foreign('organisation_id')->references('id')->on('organisations')->onDelete('cascade');
            $table->foreign('software_id')->references('id')->on('softwares');
            $table->foreign('plan_id')->references('id')->on('plans')->onDelete('cascade');
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
        Schema::dropIfExists('subscriptions');
    }
}
