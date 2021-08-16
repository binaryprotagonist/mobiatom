<?php

use App\Model\Channel;
use Illuminate\Database\Seeder;

class ChannelsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker\Factory::create();

        $channel = new Channel;
        $channel->organisation_id = 2;
        $channel->sales_organisation_id = 1;
        $channel->code = '1';
        $channel->name = $faker->name;
        $channel->status = 1;
        $channel->save();

        $channel = new Channel;
        $channel->organisation_id = 2;
        $channel->sales_organisation_id = 1;
        $channel->code = '1';
        $channel->name = $faker->name;
        $channel->status = 1;
        $channel->save();
        
    }
}