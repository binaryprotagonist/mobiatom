<?php

namespace App\Imports;

use App\User;
use App\Model\CustomerInfo;
use App\Model\Country;
use App\Model\Region;
use App\Model\CustomerGroup;
use App\Model\SalesOrganisation;
use App\Model\Channel;
use App\Model\CustomerCategory;
use App\Model\CustomerType;
use App\Model\PaymentTerm;
use App\Model\Route;
use App\Model\WorkFlowObject;
use App\Model\WorkFlowObjectAction;
use App\Model\WorkFlowRuleApprovalRole;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Validators\Failure;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Throwable;


class UsersImport implements ToModel, WithValidation, SkipsOnFailure, SkipsOnError, WithMapping
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
    public function __construct(String  $skipduplicate, array $map_key_value_array, array $heading_array)
    {
        $this->skipduplicate = $skipduplicate;
        $this->map_key_value_array = $map_key_value_array;
        $this->heading_array = $heading_array;
    }
    public function startRow(): int
    {
        return 2;
    }

    final public function map($row): array
    {
        $heading_array = $this->heading_array;
        $map_key_value_array = $this->map_key_value_array;
        //print_r($heading_array);
        //print_r($map_key_value_array);
        $First_Name_key = '0';
        $Last_Name_key = '1';
        $Email_key = '2';
        $Password_key = '3';
        $Mobile_key = '4';
        $Country_key = '5';
        $Status_key = '6';
        $Region_key = '7';
//        $Customer_group_key = '8';
        $Sales_Organisation_key = '8';
        $Route_key = '9';
        $Channel_key = '10';
        $Customer_Category_key = '11';
        $Customer_Code_key = '12';
        $Customer_Type_key  = '13';
        $Office_Address_key  = '14';
        $Home_Address_key  = '15';
        $City_key = '16';
        $State_key = '17';
        $Zipcode_key = '18';
        $Phone_key = '19';
        $Balance_key = '20';
        $Credit_Limit_key = '21';
        $Credit_Days_key = '22';
        $Payment_Term_key = '23';
        $Merchandiser_Name_key = '24';
        $Ship_to_party_key = '25';
        $Sold_to_party_key = '26';
        $Payer_key = '27';
        $Bill_to_party_key = '28';
        $LATITUDE_key = '29';
        $LONGITUDE_key = '30';
        $erp_code = '31';
//        $TRN_NO_key = '32';
//        $TRN_NAME_key = '33';
//        $TRD_LLC_NO_key = '34';
        $couter = 0;
        foreach ($map_key_value_array as $map_key_value_array_key => $map_key_value_array_value) {
            //$map_key_value_array_key.'--'.$map_key_value_array_value;
            //array_search($map_key_value_array_value,$heading_array,true);
            if ($couter == 0) {
                $First_Name_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 1) {
                //echo '==>'.$map_key_value_array_value;
                //print_r($heading_array);
                $Last_Name_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 2) {
                $Email_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 3) {
                $Password_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 4) {
                $Mobile_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 5) {
                $Country_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 6) {
                $Status_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 7) {
                $Region_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 8) {
                $Sales_Organisation_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 9) {
                $Route_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 10) {
                $Channel_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 11) {
                $Customer_Category_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 12) {
                $Customer_Code_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 13) {
                $Customer_Type_key  = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 14) {
                $Office_Address_key  = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 15) {
                $Home_Address_key  = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 16) {
                $City_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 17) {
                $State_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 18) {
                $Zipcode_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 19) {
                $Phone_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 20) {
                $Balance_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 21) {
                $Credit_Limit_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 22) {
                // echo $couter.'===='.$map_key_value_array_value;
                //print_r($heading_array);
                $Credit_Days_key = array_search($map_key_value_array_value, $heading_array, true);
                //23====Credit Days===>23
            } else if ($couter == 23) {
                $Payment_Term_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 24) {
                $Merchandiser_Name_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 25) {
                $Ship_to_party_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 26) {
                $Sold_to_party_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 27) {
                $Payer_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 28) {
                $Bill_to_party_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 29) {
                $LATITUDE_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 30) {
                $LONGITUDE_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 31) {
                $erp_code = array_search($map_key_value_array_value, $heading_array, true);
            }
//            else if ($couter == 32) {
//                $TRN_NO_key = array_search($map_key_value_array_value, $heading_array, true);
//            } else if ($couter == 33) {
//                $TRN_NAME_key = array_search($map_key_value_array_value, $heading_array, true);
//            } else if ($couter == 34) {
//                $TRD_LLC_NO_key = array_search($map_key_value_array_value, $heading_array, true);
//            }
            $couter++;
        }
        //echo $Last_Name_key.'<br>';
        $map =   [
            '0'  => isset($row[$First_Name_key]) ? $row[$First_Name_key] : "", //First Name
            '1'  => isset($row[$Last_Name_key]) ? $row[$Last_Name_key] : "", //Last Name
            '2'  => isset($row[$Email_key]) ? $row[$Email_key] : "", //Email
            '3'  => isset($row[$Password_key]) ? $row[$Password_key] : "", //Password
            '4'  => isset($row[$Mobile_key]) ? $row[$Mobile_key] : "", //Mobile
            '5'  => isset($row[$Country_key]) ? $row[$Country_key] : "", //Country
            '6'  => isset($row[$Status_key]) ? $row[$Status_key] : "", //Status
            '7'  => isset($row[$Region_key]) ? $row[$Region_key] : "", //Region
            '8'  => isset($row[$Sales_Organisation_key]) ? $row[$Sales_Organisation_key] : "", //Sales Organisation
            '9'  => isset($row[$Route_key]) ? $row[$Route_key] : "", //Route
            '10'  => isset($row[$Channel_key]) ? $row[$Channel_key] : "", //Channel
            '11'  => isset($row[$Customer_Category_key]) ? $row[$Customer_Category_key] : "", //Customer Category
            '12'  => isset($row[$Customer_Code_key]) ? $row[$Customer_Code_key] : "", //Customer Code
            '13'  => isset($row[$Customer_Type_key]) ? $row[$Customer_Type_key] : "", //Customer Type
            '14'  => isset($row[$Office_Address_key]) ? $row[$Office_Address_key] : "", //Address one
            '15'  => isset($row[$Home_Address_key]) ? $row[$Home_Address_key] : "", //Address two
            '16'  => isset($row[$City_key]) ? $row[$City_key] : "", //City
            '17'  => isset($row[$State_key]) ? $row[$State_key] : "", //State
            '18'  => isset($row[$Zipcode_key]) ? $row[$Zipcode_key] : "", //Zipcode
            '19'  => isset($row[$Phone_key]) ? $row[$Phone_key] : "", //Phone
            '20'  => isset($row[$Balance_key]) ? $row[$Balance_key] : "", //Balance
            '21'  => isset($row[$Credit_Limit_key]) ? $row[$Credit_Limit_key] : "", //Credit Limit
            '22'  => isset($row[$Credit_Days_key]) ? $row[$Credit_Days_key] : "", //Credit Days
            '23'  => isset($row[$Payment_Term_key]) ? $row[$Payment_Term_key] : "", //Payment Term
            '24'  => isset($row[$Merchandiser_Name_key]) ? $row[$Merchandiser_Name_key] : "", //Payment Term
            '25'  => isset($row[$Ship_to_party_key]) ? $row[$Ship_to_party_key] : "", //Payment Term
            '26'  => isset($row[$Sold_to_party_key]) ? $row[$Sold_to_party_key] : "", //Payment Term
            '27'  => isset($row[$Payer_key]) ? $row[$Payer_key] : "", //Payment Term
            '28'  => isset($row[$Bill_to_party_key]) ? $row[$Bill_to_party_key] : "", //Payment Term
            '29'  => isset($row[$LATITUDE_key]) ? $row[$LATITUDE_key] : "", //Payment Term
            '30'  => isset($row[$LONGITUDE_key]) ? $row[$LONGITUDE_key] : "", //Payment Term
            '31'  => isset($row[$erp_code]) ? $row[$erp_code] : "", //Payment Term
//            '32'  => isset($row[$TRN_NO_key]) ? $row[$TRN_NO_key] : "", //Payment Term
//            '33'  => isset($row[$TRN_NAME_key]) ? $row[$TRN_NAME_key] : "", //Payment Term
//            '34'  => isset($row[$TRD_LLC_NO_key]) ? $row[$TRD_LLC_NO_key] : "", //Payment Term
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
                '2' => 'required|email',
                '3' => 'required',
                // '4' => 'required',
                '5' => 'required|exists:countries,name',
                '6' => 'required',
                '7' => 'required|exists:regions,region_name',
//                '8' => 'required|exists:customer_groups,group_name',
                '8' => 'required|exists:sales_organisations,name',
                // '9' => 'required|exists:routes,route_name',
                '10' => 'required|exists:channels,name',
                '11' => 'required|exists:customer_categories,customer_category_name',
                '12' => 'required',
                '13' => 'required|exists:customer_types,customer_type_name',
                '14' => 'required',
                // '15'  => 'required',
                // '16'  => 'required',
                // '17'  => 'required',
                // '18'  => 'required',
                // '19'  => 'required',
                '20'  => 'required',
                // '21'  => 'required',
                // '22'  => 'required',
                // '23'  => 'required|exists:payment_terms,name',
                '24'  => 'required|exists:users,firstname',
                '25'  => 'required',
                '26'  => 'required',
                '27'  => 'required',
                '28'  => 'required',
//                '30'  => 'required',
//                '31'  => 'required',
            ];
        } else {
            return [
                '0' => 'required',
                '1' => 'required',
                '2' => 'required|email|unique:users,email',
                '3' => 'required',
                // '4' => 'required',
                '5' => 'required|exists:countries,name',
                '6' => 'required',
                '7' => 'required|exists:regions,region_name',
//                '8' => 'required|exists:customer_groups,group_name',
                '8' => 'required|exists:sales_organisations,name',
                // '9' => 'required|exists:routes,route_name',
                '10' => 'required|exists:channels,name',
                '11' => 'required|exists:customer_categories,customer_category_name',
                '12' => 'required',
                '13' => 'required|exists:customer_types,customer_type_name',
                '14' => 'required',
                // '15'  => 'required',
                // '16'  => 'required',
                // '17'  => 'required',
                // '18'  => 'required',
                // '19'  => 'required',
                '20'  => 'required',
                // '21'  => 'required',
                // '22'  => 'required',
                // '23'  => 'required|exists:payment_terms,name',
                '24'  => 'required|exists:users,firstname',
                '25'  => 'required',
                '26'  => 'required',
                '27'  => 'required',
                '28'  => 'required',
//                '30'  => 'required',
//                '31'  => 'required',
            ];
        }
    }
    public function customValidationMessages()
    {
        return [
            '0.required' => 'First name required',
            '1.required' => 'Last name required',
            '2.required' => 'Email required',
            '2.email' => 'Email not valid',
            '2.unique' => 'Email already_exists',
            '3.required' => 'Password required',
            // '4.required' => 'Mobile required',
            '5.required' => 'Country required',
            '5.exists' => 'Country not exists',
            '6.required' => 'Status required',
            '7.required' => 'Region required',
            '7.exists' => 'Region not exists',
//            '8.required' => 'Customer group required',
//            '8.exists' => 'Customer group not exists',
            '8.required' => 'Sales organisation required',
            '8.exists' => 'Sales organisation not exists',
            // '9.required' => 'Route required',
            // '9.exists' => 'Route not exists',
            '10.required' => 'Channel required',
            '10.exists' => 'Channel not exists',
            '11.required' => 'Customer category required',
            '11.exists' => 'Customer category not exists',
            '12.required' => 'Customer code required',
            '13.required' => 'Customer type required',
            '13.exists' => 'Customer type not exists',
            '14.required' => 'Customer address one required',
            // '15.required'  => 'Customer address two required',
            // '16.required'  => 'City required',
            // '17.required'  => 'State required',
            // '18.required'  => 'Zipcode required',
            // '19.required'  => 'Phone required',
            '20.required'  => 'Balance required',
            // '21.required'  => 'Credit Limit required',
            // '22.required'  => 'Credit Days required',
            // '23.required'  => 'Payment Term required',
            // '23.exists'  => 'Payment Term not exists',
            '24.required'  => 'Merchandiser Name required',
            '24.exists'  => 'Merchandiser Name not exists',
            '25.required'  => 'Ship to party required',
            '26.required'  => 'Sold to party required',
            '27.required'  => 'Payer required',
            '28.required'  => 'Bill to party required',
//            '30.required'  => 'LATITUDE required',
//            '31.required'  => 'LONGITUDE required',
        ];
    }
    /* public function onFailure(Failure ...$failures)
    {
        // Handle the failures how you'd like.
    } */
    public function createWorkFlowObject($work_flow_rule_id, $module_name, $row, $raw_id)
    {
        $createObj = new WorkFlowObject;
        $createObj->work_flow_rule_id   = $work_flow_rule_id;
        $createObj->module_name         = $module_name;
        $createObj->raw_id                 = $raw_id;
        $createObj->request_object      = $row;
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
