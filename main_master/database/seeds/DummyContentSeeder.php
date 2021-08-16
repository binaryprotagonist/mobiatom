<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Model\Organisation;
use App\User;

class DummyContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
    	$faker = Faker\Factory::create();
        for ($a=0; $a < 5; $a++) {
            Organisation::create([
				'org_name' => $faker->name,
				'org_company_id' => rand(1,99),
				'org_tax_id' => rand(1,99),
				'org_street1' => $faker->address,
				'org_city' => $faker->city,
				'org_state' => $faker->state,
				'org_country_id' => rand(1,99),
				'org_postal' => $faker->postcode,
				'org_phone' => $faker->e164PhoneNumber,
				'org_contact_person' => $faker->name,
				'org_contact_person_number' => $faker->e164PhoneNumber,
				'org_currency' => 'USD',
				'org_fasical_year' => '2020'
            ]);
        }

        for ($b=0; $b < 25; $b++) {
            User::create([
				'organisation_id' => rand(1, 5) ,
				'usertype' 		=> 1 ,
				'firstname' => $faker->firstName ,
				'lastname' => $faker->lastName ,
				'email' => $faker->email ,
				'password' => \Hash::make('123456') ,
				'api_token' => Str::random(35) ,
				'email_verified_at' => date('Y-m-d H:i:s') ,
				'mobile' => $faker->e164PhoneNumber ,
				'country_id' => rand(1,99) ,
            ]);
        }
    }
}