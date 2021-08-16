<?php

use App\Model\SurveyType;
use Illuminate\Database\Seeder;

class SurveyTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $types = array('Distribution Survey', 'Consumer Survey', 'Sensory Evaluation Survey', 'Asset Survey');
        foreach ($types as $type) {
            $survey_type = new SurveyType;
            $survey_type->survey_name = $type;
            $survey_type->status = 1;
            $survey_type->save();
        }
    }
}
