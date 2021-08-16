<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToModel;
use App\Model\CombinationMaster;
use App\Model\CombinationPlanKey;
use App\Model\Planogram;
use App\Model\PlanogramImage;
use App\Model\Distribution;
use App\User;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Validators\Failure;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Throwable;

class PlanogramImport implements ToModel, WithValidation, SkipsOnFailure, SkipsOnError,WithMapping
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
    public function __construct(String  $skipduplicate,array $map_key_value_array,array $heading_array)
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
        $Name_key = '0';
        $Start_Date_key = '1';
        $End_Date_key = '2';
        $Customer_code_key = '3';
        $Status_key = '4';
        $Distribution_name_key = '5';
        $Image_key = '6';
        $Image2_key = '7';
        $Image3_key = '8';
        $Image4_key = '9';

        $couter = 0;
        foreach ($map_key_value_array as $map_key_value_array_key => $map_key_value_array_value) {
            //$map_key_value_array_key.'--'.$map_key_value_array_value;
            //array_search($map_key_value_array_value,$heading_array,true);
            if($couter == 0){
                $Name_key = array_search($map_key_value_array_value,$heading_array,true);
            }else if($couter == 1){
                $Start_Date_key = array_search($map_key_value_array_value,$heading_array,true);
            }else if($couter == 2){
                $End_Date_key = array_search($map_key_value_array_value,$heading_array,true);
            }else if($couter == 3){
                $Customer_code_key = array_search($map_key_value_array_value,$heading_array,true);
            }else if($couter == 4){
                $Status_key = array_search($map_key_value_array_value,$heading_array,true);
            }else if($couter == 5){
                $Distribution_name_key = array_search($map_key_value_array_value,$heading_array,true);
            }else if($couter == 6){
                $Image_key = array_search($map_key_value_array_value,$heading_array,true);
            }else if($couter == 7){
                $Image2_key = array_search($map_key_value_array_value,$heading_array,true);
            }else if($couter == 8){
                $Image3_key = array_search($map_key_value_array_value,$heading_array,true);
            }else if($couter == 9){
                $Image4_key = array_search($map_key_value_array_value,$heading_array,true);
            }
            $couter++;
        }
        //echo $Last_Name_key.'<br>';
        $map =   [
            '0'  => isset($row[$Name_key]) ? $row[$Name_key] : "",//Name
            '1'  => isset($row[$Start_Date_key]) ? $row[$Start_Date_key] : "",//Name
            '2'  => isset($row[$End_Date_key]) ? $row[$End_Date_key] : "",//Start Date
            '3'  => isset($row[$Customer_code_key]) ? $row[$Customer_code_key] : "",//End Date
            '4'  => isset($row[$Status_key]) ? $row[$Status_key] : "",//Status
            '5'  => isset($row[$Distribution_name_key]) ? $row[$Distribution_name_key] : "",//Status
            '6'  => isset($row[$Image_key]) ? $row[$Image_key] : "",//Status
            '7'  => isset($row[$Image2_key]) ? $row[$Image2_key] : "",//Status
            '8'  => isset($row[$Image3_key]) ? $row[$Image3_key] : "",//Status
            '9'  => isset($row[$Image4_key]) ? $row[$Image4_key] : ""//Status
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
        if($skipduplicate == 0){
            return [
                '0' => 'required',
                '1' => 'required',
                '2' => 'required',
                '3' => 'required|exists:customer_infos,customer_code',
                '4' => 'required',
                '5' => 'required|exists:distributions,name',
                '6' => 'required'
            ];
        }else{
            return [
                '0' => 'required|unique:planograms,name',
                '1' => 'required',
                '2' => 'required',
                '3' => 'required|exists:customer_infos,customer_code',
                '4' => 'required',
                '5' => 'required|exists:distributions,name',
                '6' => 'required'
            ];
        }

    }
    public function customValidationMessages()
    {
        return [
            '0.required' => 'name required',
            '0.unique' => 'name already_exists',
            '1.required' => 'start date required',
            '2.required' => 'end date required',
            '3.required' => 'customer code required',
            '3.exists' => 'customer code not exists',
            '4.required' => 'status required',
            '5.required' => 'distribution name required',
            '5.exists' => 'distribution name not exists',
            '6.required' => 'Image required'
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
