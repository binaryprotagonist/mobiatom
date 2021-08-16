<?php

namespace App\Imports;

use App\User;
use App\Model\JourneyPlan;
use App\Model\JourneyPlanWeek;
use App\Model\JourneyPlanDay;
use App\Model\Route;
use App\Model\CustomerInfo;
use App\Model\JourneyPlanCustomer;
use Maatwebsite\Excel\Concerns\ToModel;

use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Validators\Failure;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithStartRow;

//class JourneyPlanImports implements ToModel
//{
//    /**
//    * @param array $row
//    *
//    * @return \Illuminate\Database\Eloquent\Model|null
//    */
//    public function model(array $row)
//    {
//		if(isset($row[0]) && $row[0]!='Name'){
//			$route = Route::where('route_name',$row[7])->first();
//			$JourneyPlanExist = JourneyPlan::where('name',$row[0])->first();
//			if(is_object($JourneyPlanExist)){
//				$journey_plan_id = $JourneyPlanExist->id;
//				$plan_type = $JourneyPlanExist->plan_type;
//				$journey_plans = $JourneyPlanExist;
//			}else{
//				$journey_plans = new JourneyPlan;
//				$journey_plans->route_id = (is_object($route))?$route->id:0;
//				$journey_plans->name = $row[0];
//				$journey_plans->description = $row[1];
//				$journey_plans->start_date = date('Y-m-d',strtotime($row[2]));
//				$journey_plans->no_end_date = $row[3];
//
//				if ($row[3] == 0) {
//					$journey_plans->end_date = date('Y-m-d',strtotime($row[4]));
//				}
//
//				$journey_plans->start_time = date('H:i:s',strtotime($row[5]));
//				$journey_plans->end_time = date('H:i:s',strtotime($row[6]));
//				$journey_plans->start_day_of_the_week = $row[14];
//				$journey_plans->plan_type = $row[8];
//
//				if ($row[8] == 2) {
//					$journey_plans->week_1 = $row[9];
//					$journey_plans->week_2 = $row[10];
//					$journey_plans->week_3 = $row[11];
//					$journey_plans->week_4 = $row[12];
//					$journey_plans->week_5 = $row[13];
//				}
//				$journey_plans->status = 1;
//				$journey_plans->current_stage = 'Approved';
//				$journey_plans->save();
//				$journey_plan_id = $journey_plans->id;
//				$plan_type = $journey_plans->plan_type;
//			}
//
//            if($plan_type == 2){
//				$journey_plans_weeks_exist = JourneyPlanWeek::where('journey_plan_id',$journey_plan_id)
//				->where('week_number',$row[15])->first();
//				if(is_object($journey_plans_weeks_exist)){
//					$journey_plan_week_id = $journey_plans_weeks_exist->id;
//				}else{
//					$journey_plans_weeks = new JourneyPlanWeek;
//					$journey_plans_weeks->journey_plan_id = $journey_plan_id;
//					$journey_plans_weeks->week_number = $row[15];
//					$journey_plans_weeks->save();
//					$journey_plan_week_id = $journey_plans_weeks->id;
//				}
//
//				$journey_plans_days_exist = JourneyPlanDay::where('journey_plan_id',$journey_plan_id)
//				->where('journey_plan_week_id',$journey_plan_week_id)
//				->where('day_name',$row[16])->first();
//				if(is_object($journey_plans_days_exist)){
//					$journey_plan_day_id = $journey_plans_days_exist->id;
//				}else{
//                    $journey_plans_days = new JourneyPlanDay;
//                    $journey_plans_days->journey_plan_id = $journey_plan_id;
//                    $journey_plans_days->journey_plan_week_id = $journey_plan_week_id;
//                    $journey_plans_days->day_name = $row[16];
//                    $journey_plans_days->day_number = $row[17];
//                    $journey_plans_days->save();
//					$journey_plan_day_id = $journey_plans_days->id;
//				}
//
//				$customer = User::where('email',$row[18])->first();
//                $journey_plans_customers = new JourneyPlanCustomer;
//                $journey_plans_customers->journey_plan_id = $journey_plan_id;
//                $journey_plans_customers->journey_plan_day_id = $journey_plan_day_id;
//                $journey_plans_customers->customer_id = (is_object($customer))?$customer->id:0;
//                $journey_plans_customers->day_customer_sequence = $row[19];
//                $journey_plans_customers->day_start_time = date('H:i:s',strtotime($row[20]));
//                $journey_plans_customers->day_end_time = date('H:i:s',strtotime($row[21]));
//                $journey_plans_customers->save();
//
//
//            }else{
//				$journey_plans_days_exist = JourneyPlanDay::where('journey_plan_id',$journey_plan_id)
//				->where('journey_plan_week_id',$journey_plan_week_id)
//				->where('day_name',$row[16])->first();
//				if(is_object($journey_plans_days_exist)){
//					$journey_plan_day_id = $journey_plans_days_exist->id;
//				}else{
//					$journey_plans_days = new JourneyPlanDay;
//					$journey_plans_days->journey_plan_id = $journey_plan_id;
//					$journey_plans_days->day_name = $row[16];
//					$journey_plans_days->day_number = $row[17];
//					$journey_plans_days->save();
//					$journey_plan_day_id = $journey_plans_days->id;
//				}
//
//				$journey_plans_customers = new JourneyPlanCustomer;
//				$journey_plans_customers->journey_plan_id = $journey_plan_id;
//				$journey_plans_customers->journey_plan_day_id = $journey_plan_day_id;
//				$journey_plans_customers->customer_id = (is_object($customer))?$customer->id:0;
//				$journey_plans_customers->day_customer_sequence = $row[19];
//				$journey_plans_customers->day_start_time = date('H:i:s',strtotime($row[20]));
//				$journey_plans_customers->day_end_time = date('H:i:s',strtotime($row[21]));
//				$journey_plans_customers->save();
//            }
//			return $journey_plans;
//		}
//    }
//}

class JourneyPlanImport implements ToModel, WithValidation, SkipsOnFailure, SkipsOnError, WithMapping, WithStartRow
//, WithHeadingRow
//class UsersImport implements ToModel, WithMapping, WithValidation, SkipsOnFailure
{
    use Importable, SkipsErrors, SkipsFailures;
    protected $skipduplicate;
    protected $map_key_value_array;
    private $rowsrecords = array();
    private $rows = 0;

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function __construct(String $skipduplicate, array $map_key_value_array, array $heading_array)
    {
        $this->skipduplicate = $skipduplicate;
        $this->map_key_value_array = $map_key_value_array;
        $this->heading_array = $heading_array;
    }

    public function startRow(): int
    {
        return 3;
    }

    final public function map($row): array
    {
        $heading_array = $this->heading_array;
        $map_key_value_array = $this->map_key_value_array;
//        print_r($heading_array);
//        print_r($map_key_value_array);
        $Journey_Name = 0;
        $Desc = 1;
        $Start_Date = 2;
        $End_Date = 3;
        $Start_Time = 4;
        $End_Time = 5;
        $Day_Wise = 6;
        $Week_Wise = 7;
        $First_Day_Of_Week = 8;
        $Enforce_Flag = 9;
        $Merchandiser = 10;
        $Customer = 11;
        $Week1_Sunday = 12;
        $Week1_Sunday_Start_Time = 13;
        $Week1_Sunday_End_Time = 14;
        $Week1_Monday = 15;
        $Week1_Monday_Start_Time = 16;
        $Week1_Monday_End_Time = 17;
        $Week1_Tuesday = 18;
        $Week1_Tuesday_Start_Time = 19;
        $Week1_Tuesday_End_Time = 20;
        $Week1_Wednesday = 21;
        $Week1_Wednesday_Start_Time = 22;
        $Week1_Wednesday_End_Time = 23;
        $Week1_Thrusday = 24;
        $Week1_Thrusday_Start_Time = 25;
        $Week1_Thrusday_End_Time = 26;
        $Week1_Friday = 27;
        $Week1_Friday_Start_Time = 28;
        $Week1_Friday_End_Time = 29;
        $Week1_Saturday = 30;
        $Week1_Saturday_Start_Time = 31;
        $Week1_Saturday_End_Time = 32;
        $Week2_Sunday = 33;
        $Week2_Sunday_Start_Time = 34;
        $Week2_Sunday_End_Time = 35;
        $Week2_Monday = 36;
        $Week2_Monday_Start_Time = 37;
        $Week2_Monday_End_Time = 38;
        $Week2_Tuesday = 39;
        $Week2_Tuesday_Start_Time = 40;
        $Week2_Tuesday_End_Time = 41;
        $Week2_Wednesday = 42;
        $Week2_Wednesday_Start_Time = 43;
        $Week2_Wednesday_End_Time = 44;
        $Week2_Thrusday = 45;
        $Week2_Thrusday_Start_Time = 46;
        $Week2_Thrusday_End_Time = 47;
        $Week2_Friday = 48;
        $Week2_Friday_Start_Time = 49;
        $Week2_Friday_End_Time = 50;
        $Week2_Saturday = 51;
        $Week2_Saturday_Start_Time = 52;
        $Week2_Saturday_End_Time = 53;
        $Week3_Sunday = 54;
        $Week3_Sunday_Start_Time = 55;
        $Week3_Sunday_End_Time = 56;
        $Week3_Monday = 57;
        $Week3_Monday_Start_Time = 58;
        $Week3_Monday_End_Time = 59;
        $Week3_Tuesday = 60;
        $Week3_Tuesday_Start_Time = 61;
        $Week3_Tuesday_End_Time = 62;
        $Week3_Wednesday = 63;
        $Week3_Wednesday_Start_Time = 64;
        $Week3_Wednesday_End_Time = 65;
        $Week3_Thrusday = 66;
        $Week3_Thrusday_Start_Time = 67;
        $Week3_Thrusday_End_Time = 68;
        $Week3_Friday = 69;
        $Week3_Friday_Start_Time = 70;
        $Week3_Friday_End_Time = 71;
        $Week3_Saturday = 72;
        $Week3_Saturday_Start_Time = 73;
        $Week3_Saturday_End_Time = 74;
        $Week4_Sunday = 75;
        $Week4_Sunday_Start_Time = 76;
        $Week4_Sunday_End_Time = 77;
        $Week4_Monday = 78;
        $Week4_Monday_Start_Time = 79;
        $Week4_Monday_End_Time = 80;
        $Week4_Tuesday = 81;
        $Week4_Tuesday_Start_Time = 82;
        $Week4_Tuesday_End_Time = 83;
        $Week4_Wednesday = 84;
        $Week4_Wednesday_Start_Time = 85;
        $Week4_Wednesday_End_Time = 86;
        $Week4_Thrusday = 87;
        $Week4_Thrusday_Start_Time = 88;
        $Week4_Thrusday_End_Time = 89;
        $Week4_Friday = 90;
        $Week4_Friday_Start_Time = 91;
        $Week4_Friday_End_Time = 92;
        $Week4_Saturday = 93;
        $Week4_Saturday_Start_Time = 94;
        $Week4_Saturday_End_Time = 95;
        $Week5_Sunday = 96;
        $Week5_Sunday_Start_Time = 97;
        $Week5_Sunday_End_Time = 98;
        $Week5_Monday = 99;
        $Week5_Monday_Start_Time = 100;
        $Week5_Monday_End_Time = 101;
        $Week5_Tuesday = 102;
        $Week5_Tuesday_Start_Time = 103;
        $Week5_Tuesday_End_Time = 104;
        $Week5_Wednesday = 105;
        $Week5_Wednesday_Start_Time = 106;
        $Week5_Wednesday_End_Time = 107;
        $Week5_Thrusday = 108;
        $Week5_Thrusday_Start_Time = 109;
        $Week5_Thrusday_End_Time = 110;
        $Week5_Friday = 111;
        $Week5_Friday_Start_Time = 112;
        $Week5_Friday_End_Time = 113;
        $Week5_Saturday = 114;
        $Week5_Saturday_Start_Time = 115;
        $Week5_Saturday_End_Time = 116;
        $couter = 0;
        foreach ($map_key_value_array as $map_key_value_array_key => $map_key_value_array_value) {
            //$map_key_value_array_key.'--'.$map_key_value_array_value;
            //array_search($map_key_value_array_value,$heading_array,true);
            if ($couter == 0) {
                $Journey_Name = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 1) {
                $Desc = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 2) {
                $Start_Date = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 3) {
                $End_Date = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 4) {
                $Start_Time = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 5) {
                $End_Time = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 6) {
                $Day_Wise = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 7) {
                $Week_Wise = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 8) {
                $First_Day_Of_Week = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 9) {
                $Enforce_Flag = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 10) {
                $Merchandiser = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 11) {
                $Customer = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 12) {
                $Week1_Sunday = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 13) {
                $Week1_Sunday_Start_Time = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 14) {
                $Week1_Sunday_End_Time = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 15) {
                $Week1_Monday = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 16) {
                $Week1_Monday_Start_Time = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 17) {
                $Week1_Monday_End_Time = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 18) {
                $Week1_Tuesday = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 19) {
                $Week1_Tuesday_Start_Time = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 20) {
                $Week1_Tuesday_End_Time = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 21) {
                $Week1_Wednesday = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 22) {
                $Week1_Wednesday_Start_Time = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 23) {
                $Week1_Wednesday_End_Time = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 24) {
                $Week1_Thrusday = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 25) {
                $Week1_Thrusday_Start_Time = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 26) {
                $Week1_Thrusday_End_Time = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 27) {
                $Week1_Friday = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 28) {
                $Week1_Friday_Start_Time = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 29) {
                $Week1_Friday_End_Time = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 30) {
                $Week1_Saturday = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 31) {
                $Week1_Saturday_Start_Time = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 32) {
                $Week1_Saturday_End_Time = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 33) {
                $Week2_Sunday = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 34) {
                $Week2_Sunday_Start_Time = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 35) {
                $Week2_Sunday_End_Time = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 36) {
                $Week2_Monday = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 37) {
                $Week2_Monday_Start_Time = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 38) {
                $Week2_Monday_End_Time = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 39) {
                $Week2_Tuesday = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 40) {
                $Week2_Tuesday_Start_Time = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 41) {
                $Week2_Tuesday_End_Time = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 42) {
                $Week2_Wednesday = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 43) {
                $Week2_Wednesday_Start_Time = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 44) {
                $Week2_Wednesday_End_Time = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 45) {
                $Week2_Thrusday = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 46) {
                $Week2_Thrusday_Start_Time = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 47) {
                $Week2_Thrusday_End_Time = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 48) {
                $Week2_Friday = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 49) {
                $Week2_Friday_Start_Time = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 50) {
                $Week2_Friday_End_Time = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 51) {
                $Week2_Saturday = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 52) {
                $Week2_Saturday_Start_Time = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 53) {
                $Week2_Saturday_End_Time = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 54) {
                $Week3_Sunday = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 55) {
                $Week3_Sunday_Start_Time = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 56) {
                $Week3_Sunday_End_Time = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 57) {
                $Week3_Monday = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 58) {
                $Week3_Monday_Start_Time = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 59) {
                $Week3_Monday_End_Time = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 60) {
                $Week3_Tuesday = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 61) {
                $Week3_Tuesday_Start_Time = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 62) {
                $Week3_Tuesday_End_Time = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 63) {
                $Week3_Wednesday = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 64) {
                $Week3_Wednesday_Start_Time = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 65) {
                $Week3_Wednesday_End_Time = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 66) {
                $Week3_Thrusday = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 67) {
                $Week3_Thrusday_Start_Time = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 68) {
                $Week3_Thrusday_End_Time = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 69) {
                $Week3_Friday = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 70) {
                $Week3_Friday_Start_Time = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 71) {
                $Week3_Friday_End_Time = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 72) {
                $Week3_Saturday = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 73) {
                $Week3_Saturday_Start_Time = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 74) {
                $Week3_Saturday_End_Time = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 75) {
                $Week4_Sunday = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 76) {
                $Week4_Sunday_Start_Time = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 77) {
                $Week4_Sunday_End_Time = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 78) {
                $Week4_Monday = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 79) {
                $Week4_Monday_Start_Time = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 80) {
                $Week4_Monday_End_Time = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 81) {
                $Week4_Tuesday = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 82) {
                $Week4_Tuesday_Start_Time = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 83) {
                $Week4_Tuesday_End_Time = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 84) {
                $Week4_Wednesday = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 85) {
                $Week4_Wednesday_Start_Time = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 86) {
                $Week4_Wednesday_End_Time = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 87) {
                $Week4_Thrusday = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 88) {
                $Week4_Thrusday_Start_Time = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 89) {
                $Week4_Thrusday_End_Time = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 90) {
                $Week4_Friday = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 91) {
                $Week4_Friday_Start_Time = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 92) {
                $Week4_Friday_End_Time = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 93) {
                $Week4_Saturday = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 94) {
                $Week4_Saturday_Start_Time = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 95) {
                $Week4_Saturday_End_Time = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 96) {
                $Week5_Sunday = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 97) {
                $Week5_Sunday_Start_Time = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 98) {
                $Week5_Sunday_End_Time = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 99) {
                $Week5_Monday = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 100) {
                $Week5_Monday_Start_Time = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 101) {
                $Week5_Monday_End_Time = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 102) {
                $Week5_Tuesday = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 103) {
                $Week5_Tuesday_Start_Time = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 104) {
                $Week5_Tuesday_End_Time = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 105) {
                $Week5_Wednesday = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 106) {
                $Week5_Wednesday_Start_Time = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 107) {
                $Week5_Wednesday_End_Time = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 108) {
                $Week5_Thrusday = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 109) {
                $Week5_Thrusday_Start_Time = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 110) {
                $Week5_Thrusday_End_Time = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 111) {
                $Week5_Friday = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 112) {
                $Week5_Friday_Start_Time = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 113) {
                $Week5_Friday_End_Time = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 114) {
                $Week5_Saturday = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 115) {
                $Week5_Saturday_Start_Time = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 116) {
                $Week5_Saturday_End_Time = array_search($map_key_value_array_value, $heading_array, true);
            }
            $couter++;
        }
        //echo $Last_Name_key.'<br>';
        $map = [
            '0' => isset($row[$Journey_Name]) ? $row[$Journey_Name] : "",
            '1' => isset($row[$Desc]) ? $row[$Desc] : "",
            '2' => isset($row[$Start_Date]) ? $row[$Start_Date] : "",
            '3' => isset($row[$End_Date]) ? $row[$End_Date] : "",
            '4' => isset($row[$Start_Time]) ? $row[$Start_Time] : "",
            '5' => isset($row[$End_Time]) ? $row[$End_Time] : "",
            '6' => isset($row[$Day_Wise]) ? $row[$Day_Wise] : "",
            '7' => isset($row[$Week_Wise]) ? $row[$Week_Wise] : "",
            '8' => isset($row[$First_Day_Of_Week]) ? $row[$First_Day_Of_Week] : "",
            '9' => isset($row[$Enforce_Flag]) ? $row[$Enforce_Flag] : "",
            '10' => isset($row[$Merchandiser]) ? $row[$Merchandiser] : "",
            '11' => isset($row[$Customer]) ? $row[$Customer] : "",
            '12' => isset($row[$Week1_Sunday]) ? $row[$Week1_Sunday] : "",
            '13' => isset($row[$Week1_Sunday_Start_Time]) ? $row[$Week1_Sunday_Start_Time] : "",
            '14' => isset($row[$Week1_Sunday_End_Time]) ? $row[$Week1_Sunday_End_Time] : "",
            '15' => isset($row[$Week1_Monday]) ? $row[$Week1_Monday] : "",
            '16' => isset($row[$Week1_Monday_Start_Time]) ? $row[$Week1_Monday_Start_Time] : "",
            '17' => isset($row[$Week1_Monday_End_Time]) ? $row[$Week1_Monday_End_Time] : "",
            '18' => isset($row[$Week1_Tuesday]) ? $row[$Week1_Tuesday] : "",
            '19' => isset($row[$Week1_Tuesday_Start_Time]) ? $row[$Week1_Tuesday_Start_Time] : "",
            '20' => isset($row[$Week1_Tuesday_End_Time]) ? $row[$Week1_Tuesday_End_Time] : "",
            '21' => isset($row[$Week1_Wednesday]) ? $row[$Week1_Wednesday] : "",
            '22' => isset($row[$Week1_Wednesday_Start_Time]) ? $row[$Week1_Wednesday_Start_Time] : "",
            '23' => isset($row[$Week1_Wednesday_End_Time]) ? $row[$Week1_Wednesday_End_Time] : "",
            '24' => isset($row[$Week1_Thrusday]) ? $row[$Week1_Thrusday] : "",
            '25' => isset($row[$Week1_Thrusday_Start_Time]) ? $row[$Week1_Thrusday_Start_Time] : "",
            '26' => isset($row[$Week1_Thrusday_End_Time]) ? $row[$Week1_Thrusday_End_Time] : "",
            '27' => isset($row[$Week1_Friday]) ? $row[$Week1_Friday] : "",
            '28' => isset($row[$Week1_Friday_Start_Time]) ? $row[$Week1_Friday_Start_Time] : "",
            '29' => isset($row[$Week1_Friday_End_Time]) ? $row[$Week1_Friday_End_Time] : "",
            '30' => isset($row[$Week1_Saturday]) ? $row[$Week1_Saturday] : "",
            '31' => isset($row[$Week1_Saturday_Start_Time]) ? $row[$Week1_Saturday_Start_Time] : "",
            '32' => isset($row[$Week1_Saturday_End_Time]) ? $row[$Week1_Saturday_End_Time] : "",
            '33' => isset($row[$Week2_Sunday]) ? $row[$Week2_Sunday] : "",
            '34' => isset($row[$Week2_Sunday_Start_Time]) ? $row[$Week2_Sunday_Start_Time] : "",
            '35' => isset($row[$Week2_Sunday_End_Time]) ? $row[$Week2_Sunday_End_Time] : "",
            '36' => isset($row[$Week2_Monday]) ? $row[$Week2_Monday] : "",
            '37' => isset($row[$Week2_Monday_Start_Time]) ? $row[$Week2_Monday_Start_Time] : "",
            '38' => isset($row[$Week2_Monday_End_Time]) ? $row[$Week2_Monday_End_Time] : "",
            '39' => isset($row[$Week2_Tuesday]) ? $row[$Week2_Tuesday] : "",
            '40' => isset($row[$Week2_Tuesday_Start_Time]) ? $row[$Week2_Tuesday_Start_Time] : "",
            '41' => isset($row[$Week2_Tuesday_End_Time]) ? $row[$Week2_Tuesday_End_Time] : "",
            '42' => isset($row[$Week2_Wednesday]) ? $row[$Week2_Wednesday] : "",
            '43' => isset($row[$Week2_Wednesday_Start_Time]) ? $row[$Week2_Wednesday_Start_Time] : "",
            '44' => isset($row[$Week2_Wednesday_End_Time]) ? $row[$Week2_Wednesday_End_Time] : "",
            '45' => isset($row[$Week2_Thrusday]) ? $row[$Week2_Thrusday] : "",
            '46' => isset($row[$Week2_Thrusday_Start_Time]) ? $row[$Week2_Thrusday_Start_Time] : "",
            '47' => isset($row[$Week2_Thrusday_End_Time]) ? $row[$Week2_Thrusday_End_Time] : "",
            '48' => isset($row[$Week2_Friday]) ? $row[$Week2_Friday] : "",
            '49' => isset($row[$Week2_Friday_Start_Time]) ? $row[$Week2_Friday_Start_Time] : "",
            '50' => isset($row[$Week2_Friday_End_Time]) ? $row[$Week2_Friday_End_Time] : "",
            '51' => isset($row[$Week2_Saturday]) ? $row[$Week2_Saturday] : "",
            '52' => isset($row[$Week2_Saturday_Start_Time]) ? $row[$Week2_Saturday_Start_Time] : "",
            '53' => isset($row[$Week2_Saturday_End_Time]) ? $row[$Week2_Saturday_End_Time] : "",
            '54' => isset($row[$Week3_Sunday]) ? $row[$Week3_Sunday] : "",
            '55' => isset($row[$Week3_Sunday_Start_Time]) ? $row[$Week3_Sunday_Start_Time] : "",
            '56' => isset($row[$Week3_Sunday_End_Time]) ? $row[$Week3_Sunday_End_Time] : "",
            '57' => isset($row[$Week3_Monday]) ? $row[$Week3_Monday] : "",
            '58' => isset($row[$Week3_Monday_Start_Time]) ? $row[$Week3_Monday_Start_Time] : "",
            '59' => isset($row[$Week3_Monday_End_Time]) ? $row[$Week3_Monday_End_Time] : "",
            '60' => isset($row[$Week3_Tuesday]) ? $row[$Week3_Tuesday] : "",
            '61' => isset($row[$Week3_Tuesday_Start_Time]) ? $row[$Week3_Tuesday_Start_Time] : "",
            '62' => isset($row[$Week3_Tuesday_End_Time]) ? $row[$Week3_Tuesday_End_Time] : "",
            '63' => isset($row[$Week3_Wednesday]) ? $row[$Week3_Wednesday] : "",
            '64' => isset($row[$Week3_Wednesday_Start_Time]) ? $row[$Week3_Wednesday_Start_Time] : "",
            '65' => isset($row[$Week3_Wednesday_End_Time]) ? $row[$Week3_Wednesday_End_Time] : "",
            '66' => isset($row[$Week3_Thrusday]) ? $row[$Week3_Thrusday] : "",
            '67' => isset($row[$Week3_Thrusday_Start_Time]) ? $row[$Week3_Thrusday_Start_Time] : "",
            '68' => isset($row[$Week3_Thrusday_End_Time]) ? $row[$Week3_Thrusday_End_Time] : "",
            '69' => isset($row[$Week3_Friday]) ? $row[$Week3_Friday] : "",
            '70' => isset($row[$Week3_Friday_Start_Time]) ? $row[$Week3_Friday_Start_Time] : "",
            '71' => isset($row[$Week3_Friday_End_Time]) ? $row[$Week3_Friday_End_Time] : "",
            '72' => isset($row[$Week3_Saturday]) ? $row[$Week3_Saturday] : "",
            '73' => isset($row[$Week3_Saturday_Start_Time]) ? $row[$Week3_Saturday_Start_Time] : "",
            '74' => isset($row[$Week3_Saturday_End_Time]) ? $row[$Week3_Saturday_End_Time] : "",
            '75' => isset($row[$Week4_Sunday]) ? $row[$Week4_Sunday] : "",
            '76' => isset($row[$Week4_Sunday_Start_Time]) ? $row[$Week4_Sunday_Start_Time] : "",
            '77' => isset($row[$Week4_Sunday_End_Time]) ? $row[$Week4_Sunday_End_Time] : "",
            '78' => isset($row[$Week4_Monday]) ? $row[$Week4_Monday] : "",
            '79' => isset($row[$Week4_Monday_Start_Time]) ? $row[$Week4_Monday_Start_Time] : "",
            '80' => isset($row[$Week4_Monday_End_Time]) ? $row[$Week4_Monday_End_Time] : "",
            '81' => isset($row[$Week4_Tuesday]) ? $row[$Week4_Tuesday] : "",
            '82' => isset($row[$Week4_Tuesday_Start_Time]) ? $row[$Week4_Tuesday_Start_Time] : "",
            '83' => isset($row[$Week4_Tuesday_End_Time]) ? $row[$Week4_Tuesday_End_Time] : "",
            '84' => isset($row[$Week4_Wednesday]) ? $row[$Week4_Wednesday] : "",
            '85' => isset($row[$Week4_Wednesday_Start_Time]) ? $row[$Week4_Wednesday_Start_Time] : "",
            '86' => isset($row[$Week4_Wednesday_End_Time]) ? $row[$Week4_Wednesday_End_Time] : "",
            '87' => isset($row[$Week4_Thrusday]) ? $row[$Week4_Thrusday] : "",
            '88' => isset($row[$Week4_Thrusday_Start_Time]) ? $row[$Week4_Thrusday_Start_Time] : "",
            '89' => isset($row[$Week4_Thrusday_End_Time]) ? $row[$Week4_Thrusday_End_Time] : "",
            '90' => isset($row[$Week4_Friday]) ? $row[$Week4_Friday] : "",
            '91' => isset($row[$Week4_Friday_Start_Time]) ? $row[$Week4_Friday_Start_Time] : "",
            '92' => isset($row[$Week4_Friday_End_Time]) ? $row[$Week4_Friday_End_Time] : "",
            '93' => isset($row[$Week4_Saturday]) ? $row[$Week4_Saturday] : "",
            '94' => isset($row[$Week4_Saturday_Start_Time]) ? $row[$Week4_Saturday_Start_Time] : "",
            '95' => isset($row[$Week4_Saturday_End_Time]) ? $row[$Week4_Saturday_End_Time] : "",
            '96' => isset($row[$Week5_Sunday]) ? $row[$Week5_Sunday] : "",
            '97' => isset($row[$Week5_Sunday_Start_Time]) ? $row[$Week5_Sunday_Start_Time] : "",
            '98' => isset($row[$Week5_Sunday_End_Time]) ? $row[$Week5_Sunday_End_Time] : "",
            '99' => isset($row[$Week5_Monday]) ? $row[$Week5_Monday] : "",
            '100' => isset($row[$Week5_Monday_Start_Time]) ? $row[$Week5_Monday_Start_Time] : "",
            '101' => isset($row[$Week5_Monday_End_Time]) ? $row[$Week5_Monday_End_Time] : "",
            '102' => isset($row[$Week5_Tuesday]) ? $row[$Week5_Tuesday] : "",
            '103' => isset($row[$Week5_Tuesday_Start_Time]) ? $row[$Week5_Tuesday_Start_Time] : "",
            '104' => isset($row[$Week5_Tuesday_End_Time]) ? $row[$Week5_Tuesday_End_Time] : "",
            '105' => isset($row[$Week5_Wednesday]) ? $row[$Week5_Wednesday] : "",
            '106' => isset($row[$Week5_Wednesday_Start_Time]) ? $row[$Week5_Wednesday_Start_Time] : "",
            '107' => isset($row[$Week5_Wednesday_End_Time]) ? $row[$Week5_Wednesday_End_Time] : "",
            '108' => isset($row[$Week5_Thrusday]) ? $row[$Week5_Thrusday] : "",
            '109' => isset($row[$Week5_Thrusday_Start_Time]) ? $row[$Week5_Thrusday_Start_Time] : "",
            '110' => isset($row[$Week5_Thrusday_End_Time]) ? $row[$Week5_Thrusday_End_Time] : "",
            '111' => isset($row[$Week5_Friday]) ? $row[$Week5_Friday] : "",
            '112' => isset($row[$Week5_Friday_Start_Time]) ? $row[$Week5_Friday_Start_Time] : "",
            '113' => isset($row[$Week5_Friday_End_Time]) ? $row[$Week5_Friday_End_Time] : "",
            '114' => isset($row[$Week5_Saturday]) ? $row[$Week5_Saturday] : "",
            '115' => isset($row[$Week5_Saturday_Start_Time]) ? $row[$Week5_Saturday_Start_Time] : "",
            '116' => isset($row[$Week5_Saturday_End_Time]) ? $row[$Week5_Saturday_End_Time] : ""
        ];
        //print_r($map);
        return $map;
    }

    public function model(array $row)
    {
        ++$this->rows;
        //print_r($row);
        $skipduplicate = $this->skipduplicate;
        $this->rowsrecords[] = $row;
    }

    public function rules(): array
    {
        $skipduplicate = $this->skipduplicate;
        if ($skipduplicate == 0) {
            return [
                '0' => 'required',
                '1' => 'required',
                '2' => 'required',
//                '3' => '',
                '4' => 'required',
                '5' => 'required',
                '6' => 'required',
                '7' => 'required',
                '8' => 'required_if:Day_Wise,==,Yes',
                '9' => 'required',
                '10' => 'required|exists:customer_infos,customer_code',
                '11' => 'required|exists:customer_infos,customer_code',
                '12' => 'required',
//                '13' => '',
//                '14' => '',
                '15' => 'required',
//                '16' => '',
//                '17' => '',
                '18' => 'required',
//                '19' => '',
//                '20' => '',
                '21' => 'required',
//                '22' => '',
//                '23' => '',
                '24' => 'required',
//                '25' => '',
//                '26' => '',
                '27' => 'required',
//                '28' => '',
//                '29' => '',
                '30' => 'required',
//                '31' => '',
//                '32' => ''
            ];
        } else {
            return [
                '0' => 'required|unique:journey_plans,name',
                '1' => 'required',
                '2' => 'required',
//                '3' => '',
                '4' => 'required',
                '5' => 'required',
                '6' => 'required',
                '7' => 'required',
                '8' => 'required_if:Day_Wise,==,Yes',
                '9' => 'required',
                '10' => 'required|exists:customer_infos,customer_code',
                '11' => 'required|exists:customer_infos,customer_code',
                '12' => 'required',
//                '13' => '',
//                '14' => '',
                '15' => 'required',
//                '16' => '',
//                '17' => '',
                '18' => 'required',
//                '19' => '',
//                '20' => '',
                '21' => 'required',
//                '22' => '',
//                '23' => '',
                '24' => 'required',
//                '25' => '',
//                '26' => '',
                '27' => 'required',
//                '28' => '',
//                '29' => '',
                '30' => 'required',
//                '31' => '',
//                '32' => ''
            ];
        }

    }

    public function customValidationMessages()
    {
        return [
            '0.required' => 'name required',
            '0.unique' => 'name already_exists',
            '1.required' => 'Desc required',
            '2.required' => 'Start Date Description required',
//            '3.' => '',
            '4.required' => 'Start Time required',
            '5.required' => 'End Time required',
            '6.required' => 'Day Wise required',
            '7.required' => 'Week Wise required',
            '8.required_if' => 'end date required',
            '9.required' => 'Enforce Flag required',
            '10.required' => 'Merchandiser required',
            '10.exists' => 'Merchandiser not exists',
            '11.required' => 'Customer required',
            '11.exists' => 'Customer not exists',
            '12.required' => 'Sunday required',
//            '13.' => '',
//            '14.' => '',
            '15.required' => 'Monday required',
//            '16.' => '',
//            '17.' => '',
            '18.required' => 'Tuesday required',
//            '19.' => '',
//            '20.' => '',
            '21.required' => 'Wednesday required',
//            '22.' => '',
//            '23.' => '',
            '24.required' => 'Thursday required',
//            '25.' => '',
//            '26.' => '',
            '27.required' => 'Friday required',
//            '28.' => '',
//            '29.' => '',
            '30.required' => 'Saturday required',
//            '31.' => '',
//            '32.' => ''
        ];
    }

    /* public function onFailure(Failure ...$failures)
    {
        // Handle the failures how you'd like.
    } */
    public function createWorkFlowObject($work_flow_rule_id, $module_name, $row, $raw_id)
    {
        $createObj = new WorkFlowObject;
        $createObj->work_flow_rule_id = $work_flow_rule_id;
        $createObj->module_name = $module_name;
        $createObj->raw_id = $raw_id;
        $createObj->request_object = $row;
        $createObj->save();
    }

    public function getRowCount(): int
    {
        return $this->rows;
    }

    public function successAllRecords()
    {
        return $this->rowsrecords;
    }
}
