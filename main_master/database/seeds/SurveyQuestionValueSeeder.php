<?php

use App\Model\SurveyQuestionValue;
use Illuminate\Database\Seeder;

class SurveyQuestionValueSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker\Factory::create();
        for ($i = 1; $i < 6; $i++) {
            $survey_q_v = new SurveyQuestionValue;
            $survey_q_v->survey_id = 138;
            $survey_q_v->survey_question_id = 130;
            $survey_q_v->question_value = $faker->firstName;
            $survey_q_v->save();
        }
    }
}
