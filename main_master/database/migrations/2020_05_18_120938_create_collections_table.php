<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCollectionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('collections', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->unsignedBigInteger('organisation_id');
            $table->unsignedBigInteger('invoice_id');
            $table->unsignedBigInteger('customer_id')->comment('comes from users table');
            $table->unsignedBigInteger('salesman_id')->comment('comes from users table');
            $table->enum('collection_type', [1,2,3])->default(1)->comment('1:Manual Entry, 2:Auto Apply payment, 3:FIFO');
            $table->string('collection_number');
            $table->enum('payemnt_type',[1,2,3])->default(2)->comment('1: Cash, 2:Cheque, 3:NEFT');
            $table->decimal('invoice_amount', 18, 2)->default('0.00');
            $table->string('cheque_number')->nullable()->comment('if payment type 2:Cheque');
            $table->date('cheque_date')->nullable();
            $table->string('bank_info')->nullable();
            $table->string('transaction_number')->nullable()->comment('if payment type 3:NEFT');

            $table->integer('source')->comment('Order placed from. like 1:Mobile, 2:Backend, 3:Frontend');
            $table->enum('current_stage',['Pending','Approved','Rejected','In-Process','Completed'])->default('Pending');
            $table->text('current_stage_comment')->nullable();

            $table->boolean('status')->default(1);
            $table->unsignedBigInteger('lob_id')->nullable();

            $table->foreign('organisation_id')->references('id')->on('organisations')->onDelete('cascade');
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('cascade');
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
        Schema::dropIfExists('collections');
    }
}
