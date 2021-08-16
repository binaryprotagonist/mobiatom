<?php

use App\Model\ComplaintFeedback;
use Illuminate\Database\Seeder;

class ComplaintFeedbackSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker\Factory::create();
        $salesman = array(338,359,376);
        $customer = array(329,344,346,349,351,352,353,354,355,356,360,361,362,363);
        $items = array(74,76,86);
        
        for ($i = 1; $i <= 10000; $i++) {
            $complaint_feedbacks = new ComplaintFeedback;
            $complaint_feedbacks->organisation_id = 61;
            $complaint_feedbacks->route_id = 59;
            $complaint_feedbacks->salesman_id = $salesman[array_rand($salesman)];
            $complaint_feedbacks->customer_id = $customer[array_rand($customer)];
            $complaint_feedbacks->complaint_id = $faker->lastName;
            $complaint_feedbacks->title = $faker->name;
            $complaint_feedbacks->item_id = $items[array_rand($items)];
            $complaint_feedbacks->description = $faker->text;
            $complaint_feedbacks->status = 1;
            $complaint_feedbacks->save();
        }
    }
}
