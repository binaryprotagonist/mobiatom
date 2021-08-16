<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Model\CombinationMaster;

class CreateCombinationMastersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('combination_masters', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('sequence_num');
            $table->timestamps();
        });

        $list = [
            'Country',
            'Region',
            'Area',
            'Route',
            'Sales Organisation',
            'Channel',
            'Customer Category',
            'Customer',
            'Major Category',
            'Item Group',
            'Material'
        ];
        foreach ($list as $key => $value) {
            $com = new CombinationMaster;
            $com->name = $value;
            $com->sequence_num = ($key+1);
            $com->save();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('combination_masters');
    }
}
