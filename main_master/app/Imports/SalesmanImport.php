<?php

namespace App\Imports;

use App\Model\WorkFlowObject;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;

class SalesmanImport implements ToModel, WithValidation, SkipsOnFailure, SkipsOnError, WithMapping
{
    use Importable, SkipsErrors, SkipsFailures;

    protected $skipduplicate, $map_key_value_array;
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
        $First_Name_key = '0';
        $Last_Name_key = '1';
        $Email_key = '2';
        $Password_key = '3';
        $Mobile_key = '4';
        $Country_key = '5';
        $Status_key = '6';
        $Route_key = '7';
        $Region_key = '8';
        $Salesman_Type_key = '9';
        $Salesman_Role_key = '10';
        $Category_key = '11';
        $Salesman_Helper_key = '12';
        $Salesman_Code_key = '13';
        $Salesman_Supervisor_key = '14';
        $Profile_image_key = '15';
        $incentive_key = '16';
        $Date_of_joning_key = '17';
        $Block_start_date_key = '18';
        $Block_end_date_key = '19';
        $Order_From_key = '20';
        $Order_To_key = '21';
        $Invoice_From_key  = '22';
        $Invoice_To_key  = '23';
        $Collection_From_key  = '24';
        $Collection_To_key = '25';
        $Return_From_key = '26';
        $Return_To_key = '27';
        $Unload_From_key = '28';
        $Unload_To_key = '29';
        $Is_lob_key = '30';
        $Lob_name_key = '31';

        $couter = 0;
        foreach ($map_key_value_array as $map_key_value_array_key => $map_key_value_array_value) {
            if ($couter == 0) {
                $First_Name_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 1) {
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
                $Route_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 8) {
                $Region_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 9) {
                $Salesman_Type_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 10) {
                $Salesman_Role_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 11) {
                $Category_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 12) {
                $Salesman_Helper_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 13) {
                $Salesman_Code_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 14) {
                $Salesman_Supervisor_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 15) {
                $Profile_image_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 16) {
                $incentive_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 17) {
                $Date_of_joning_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 18) {
                $Block_start_date_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 19) {
                $Block_end_date_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 20) {
                $Order_From_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 21) {
                $Order_To_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 22) {
                $Invoice_From_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 23) {
                $Invoice_To_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 24) {
                $Collection_From_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 25) {
                $Collection_To_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 26) {
                $Return_From_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 27) {
                $Return_To_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 28) {
                $Unload_From_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 29) {
                $Unload_To_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 30) {
                $Is_lob_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 31) {
                $Lob_name_key = array_search($map_key_value_array_value, $heading_array, true);
            }
            $couter++;
        }

        $map =   [
            '0'  => isset($row[$First_Name_key]) ? $row[$First_Name_key] : "", //First Name
            '1'  => isset($row[$Last_Name_key]) ? $row[$Last_Name_key] : "", //Last Name
            '2'  => isset($row[$Email_key]) ? $row[$Email_key] : "", //Email
            '3'  => isset($row[$Password_key]) ? $row[$Password_key] : "", //Password
            '4'  => isset($row[$Mobile_key]) ? $row[$Mobile_key] : "", //Mobile
            '5'  => isset($row[$Country_key]) ? $row[$Country_key] : "", //Country
            '6'  => isset($row[$Status_key]) ? $row[$Status_key] : "", //Status
            '7'  => isset($row[$Route_key]) ? $row[$Route_key] : "", //Route
            '8'  => isset($row[$Region_key]) ? $row[$Region_key] : "", //Region
            '9'  => isset($row[$Salesman_Type_key]) ? $row[$Salesman_Type_key] : "", //Group Name
            '10'  => isset($row[$Salesman_Role_key]) ? $row[$Salesman_Role_key] : "", //Sales Organisation
            '11'  => isset($row[$Category_key]) ? $row[$Category_key] : "", // Category
            '12'  => isset($row[$Salesman_Helper_key]) ? $row[$Salesman_Helper_key] : "", // Salesman Helper 
            '13'  => isset($row[$Salesman_Code_key]) ? $row[$Salesman_Code_key] : "", // Profile image
            '14'  => isset($row[$Salesman_Supervisor_key]) ? $row[$Salesman_Supervisor_key] : "", // Incentive
            '15'  => isset($row[$Profile_image_key]) ? $row[$Profile_image_key] : "", //date of joinging
            '16'  => isset($row[$incentive_key]) ? $row[$incentive_key] : "", //block start date
            '17'  => isset($row[$Date_of_joning_key]) ? $row[$Date_of_joning_key] : "", //block end date
            '18'  => isset($row[$Block_start_date_key]) ? $row[$Block_start_date_key] : "", //salesman code
            '19'  => isset($row[$Block_end_date_key]) ? $row[$Block_end_date_key] : "", //Channel
            '20'  => isset($row[$Order_From_key]) ? $row[$Order_From_key] : "", //Customer Category
            '21'  => isset($row[$Order_To_key]) ? $row[$Order_To_key] : "", //Customer Code
            '22'  => isset($row[$Invoice_From_key]) ? $row[$Invoice_From_key] : "", //Customer Type
            '23'  => isset($row[$Invoice_To_key]) ? $row[$Invoice_To_key] : "", //Address one
            '24'  => isset($row[$Collection_From_key]) ? $row[$Collection_From_key] : "", //Address two
            '25'  => isset($row[$Collection_To_key]) ? $row[$Collection_To_key] : "", //City
            '26'  => isset($row[$Return_From_key]) ? $row[$Return_From_key] : "", //State
            '27'  => isset($row[$Return_To_key]) ? $row[$Return_To_key] : "", //Zipcode
            '28'  => isset($row[$Unload_From_key]) ? $row[$Unload_From_key] : "", //Phone
            '29'  => isset($row[$Unload_To_key]) ? $row[$Unload_To_key] : "",
            '30'  => isset($row[$Is_lob_key]) ? $row[$Is_lob_key] : "",
            '31'  => isset($row[$Lob_name_key]) ? $row[$Lob_name_key] : ""
        ];

        return $map;
    }

    public function model(array $row)
    {
        ++$this->rows;
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
                '4' => 'required',
                '5' => 'required|exists:country_masters,name',
                '6' => 'required',
                // '7' => 'required|exists:routes,route_name',
                // '8' => 'required|exists:salesman_types,name',
                '9' => 'required|exists:salesman_types,name',
                '10' => 'required|exists:salesman_roles,name',
                '11' => 'required',
                '13' => 'required',
                '14' => 'required'
            ];
        } else {
            return [
                '0' => 'required',
                '1' => 'required',
                '2' => 'required|email|unique:users,email',
                '3' => 'required',
                '4' => 'required',
                '5' => 'required|exists:country_masters,name',
                '6' => 'required',
                // '7' => 'required|exists:routes,route_name',
                // '8' => 'required|exists:salesman_types,name',
                '9' => 'required|exists:salesman_types,name',
                '10' => 'required|exists:salesman_roles,name',
                '11' => 'required|unique:salesman_infos,salesman_code',
                '13' => 'required',
                '14' => 'required'
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
            '4.required' => 'Mobile required',
            '5.required' => 'Country required',
            '5.exists' => 'Country not exists',
            '6.required' => 'Status required',
            // '7.required' => 'Route required',
            // '7.exists' => 'Route not exists',
            '9.required' => 'Salesman type required',
            '9.exists' => 'Salesman type not exists',
            '10.required' => 'Salesman role required',
            '10.exists' => 'Salesman role not exists',
            '11.required' => 'Salesman code required',
            '13.required' => 'Salesman Supervisor required',
            '13.unique' => 'Salesman code already_exists',
            '14.required' => 'Salesman Supervisor required'
        ];
    }

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
