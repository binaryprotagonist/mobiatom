<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDebitNoteListingfeeShelfrentRebatediscountDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('debit_note_listingfee_shelfrent_rebatediscount_details')) {
            Schema::create('debit_note_listingfee_shelfrent_rebatediscount_details', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid');
                $table->unsignedBigInteger('debit_note_id');
                $table->unsignedBigInteger('customer_id')->comment('comes from users table');
                $table->date('date');
                $table->decimal('amount', 15, 2)->default('0.00')->nullable();
                $table->string('item_name');
                $table->enum('type', ['listing_fees', 'shelf_rent', 'rebate_discount'])->default('listing_fees');
                $table->decimal('total_gross', 18, 2)->default('0.00');
                $table->decimal('total_discount_amount', 18, 2)->default('0.00');
                $table->decimal('total_net', 18, 2)->default('0.00')->comment('total_gross - total_discount_amount');
                $table->decimal('total_vat', 18, 2)->default('0.00');
                $table->decimal('total_excise', 18, 2)->default('0.00');
                $table->decimal('grand_total', 18, 2)->default('0.00')->comment(' total_net + total_vat');

                $table->foreign('debit_note_id', 'dlsr_debit_note')->references('id')->on('debit_notes');
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('debit_note_listingfee_shelfrent_rebatediscount_details');
    }
}
