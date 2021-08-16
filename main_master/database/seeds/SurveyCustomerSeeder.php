<?php

use App\Model\SurveyCustomer;
use Illuminate\Database\Seeder;

class SurveyCustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker\Factory::create();
        $customer = array(329, 344, 346, 349, 351, 352, 353, 354, 355, 356, 360, 361, 362, 363);
        $survey = array(102,105,113,115,119,120,122,123,129,138,141);
        
        $survey_customer = new SurveyCustomer;
        $survey_customer->survey_id = $survey[array_rand($survey)];
        $survey_customer->survey_type_id = 2;
        $survey_customer->customer_id = $customer[array_rand($customer)];
        $survey_customer->save();
    }
}
