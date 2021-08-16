<?php

namespace App\Imports;

use App\Model\ComplaintFeedback;
use App\Model\ComplaintFeedbackImage;
use App\Model\Order;
use App\Model\Route;
use App\Model\CustomerInfo;
use App\Model\SalesmanInfo;
use App\User;
use App\Model\Item;
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
use Throwable;

class ComplaintfeedbackImport implements ToModel, WithValidation, SkipsOnFailure, SkipsOnError,WithMapping
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
        $Complaint_key = '0';
        $Title_key = '1';
        $Item_key = '2';
        $Description_key = '3';
        $Status_key = '4';
        $Customer_key = '5';
        $Salesman_key = '6';
        $Route_key = '7';
        $Image_key = '8';
        $couter = 0;
        foreach ($map_key_value_array as $map_key_value_array_key => $map_key_value_array_value) {
            //$map_key_value_array_key.'--'.$map_key_value_array_value;
            //array_search($map_key_value_array_value,$heading_array,true);
            if($couter == 0){
                $Complaint_key = array_search($map_key_value_array_value,$heading_array,true);
            }else if($couter == 1){
                $Title_key = array_search($map_key_value_array_value,$heading_array,true);
            }else if($couter == 2){
                $Item_key = array_search($map_key_value_array_value,$heading_array,true);
            }else if($couter == 3){
                $Description_key = array_search($map_key_value_array_value,$heading_array,true);
            }else if($couter == 4){
                $Status_key = array_search($map_key_value_array_value,$heading_array,true);
            }else if($couter == 5){
                $Customer_key = array_search($map_key_value_array_value,$heading_array,true);
            }else if($couter == 6){
                $Salesman_key = array_search($map_key_value_array_value,$heading_array,true);
            }else if($couter == 7){
                $Route_key = array_search($map_key_value_array_value,$heading_array,true);
            }else if($couter == 8){
                $Image_key = array_search($map_key_value_array_value,$heading_array,true);
            }
            $couter++;
        }
        //echo $Last_Name_key.'<br>';
        $map =   [
            '0'  => isset($row[$Complaint_key]) ? $row[$Complaint_key] : "",//First Name
            '1'  => isset($row[$Title_key]) ? $row[$Title_key] : "",//Last Name
            '2'  => isset($row[$Item_key]) ? $row[$Item_key] : "",//Email
            '3'  => isset($row[$Description_key]) ? $row[$Description_key] : "",//Password
            '4'  => isset($row[$Status_key]) ? $row[$Status_key] : "",//Mobile
            '5'  => isset($row[$Customer_key]) ? $row[$Customer_key] : "",//Country
            '6'  => isset($row[$Salesman_key]) ? $row[$Salesman_key] : "",//Status
            '7'  => isset($row[$Route_key]) ? $row[$Route_key] : "",//Region
            '8'  => isset($row[$Image_key]) ? $row[$Image_key] : ""//Group Name
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
                '2' => 'required|exists:items,item_code',
                '3' => 'required',
                '4' => 'required',
                '5' => 'required|exists:customer_infos,customer_code',
                '6' => 'required|exists:customer_infos,customer_code',
                '7' => 'required|exists:routes,route_code',
                '8' => 'required'
            ];
        }else{
            return [
                '0' => 'required|unique:complaint_feedbacks,complaint_id',
                '1' => 'required|unique:complaint_feedbacks,title',
                '2' => 'required|exists:items,item_code',
                '3' => 'required',
                '4' => 'required',
                '5' => 'required|exists:customer_infos,customer_code',
                '6' => 'required|exists:customer_infos,customer_code',
                '7' => 'required|exists:routes,route_code',
                '8' => 'required'
            ];
        }

    }
    public function customValidationMessages()
    {
        return [
            '0.required' => 'Complaint id required',
            '0.unique' => 'Complaint id already_exists',
            '1.required' => 'Title required',
            '1.unique' => 'Title already_exists',
            '2.required' => 'Item required',
            '2.exists' => 'Item not exist!',
            '3.required' => 'Description required',
            '4.required' => 'Status required',
            '5.required' => 'Customer name required',
            '5.exists' => 'Customer not exist!',
            '6.required' => 'Salesman name required',
            '6.exists' => 'Salesman not exist!',
            '7.required' => 'Route name required',
            '7.exists' => 'Route not exist!',
            '8.required' => 'Image required'
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