<?php

use App\Model\SurveyQuestion;
use Illuminate\Database\Seeder;

class SurveyQuestionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker\Factory::create();
        $questionType = array("select", "text", "checkbox", "dropdown", "radio", "textarea");

        for ($i = 1; $i < 6; $i++) {

            $survey_question = new SurveyQuestion;
            $survey_question->organisation_id = 61;
            $survey_question->survey_id = 122;
            $survey_question->question = $faker->sentence(5);
            $survey_question->question_type = $questionType[array_rand($questionType)];
            $survey_question->save();
        }

        for ($i = 1; $i < 6; $i++) {

            $survey_question = new SurveyQuestion;
            $survey_question->organisation_id = 61;
            $survey_question->survey_id = 129;
            $survey_question->question = $faker->sentence(5);
            $survey_question->question_type = $questionType[array_rand($questionType)];
            $survey_question->save();
        }

        for ($i = 1; $i < 6; $i++) {

            $survey_question = new SurveyQuestion;
            $survey_question->organisation_id = 61;
            $survey_question->survey_id = 138;
            $survey_question->question = $faker->sentence(5);
            $survey_question->question_type = $questionType[array_rand($questionType)];
            $survey_question->save();
        }

        for ($i = 1; $i < 6; $i++) {

            $survey_question = new SurveyQuestion;
            $survey_question->organisation_id = 61;
            $survey_question->survey_id = 141;
            $survey_question->question = $faker->sentence(5);
            $survey_question->question_type = $questionType[array_rand($questionType)];
            $survey_question->save();
        }

        for ($i = 1; $i < 6; $i++) {

            $survey_question = new SurveyQuestion;
            $survey_question->organisation_id = 61;
            $survey_question->survey_id = 142;
            $survey_question->question = $faker->sentence(5);
            $survey_question->question_type = $questionType[array_rand($questionType)];
            $survey_question->save();
        }
    }
}
