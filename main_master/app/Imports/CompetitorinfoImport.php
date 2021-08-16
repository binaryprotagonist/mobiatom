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

class CompetitorinfoImport implements ToModel, WithValidation, SkipsOnFailure, SkipsOnError,WithMapping
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
        $Company_key = '0';
        $Brand_key = '1';
        $Item_key = '2';
        $Price_key = '3';
        $Note_key = '4';
        $Salesman_Code_key = '5';
        $Image_key = '6';
        $couter = 0;
        foreach ($map_key_value_array as $map_key_value_array_key => $map_key_value_array_value) {
            //$map_key_value_array_key.'--'.$map_key_value_array_value;
            //array_search($map_key_value_array_value,$heading_array,true);
            if ($couter == 0) {
                $Company_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 1) {
                $Brand_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 2) {
                $Item_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 3) {
                $Price_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 4) {
                $Note_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 5) {
                $Salesman_Code_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 6) {
                $Image_key = array_search($map_key_value_array_value, $heading_array, true);
            }
            $couter++;
        }
        //echo $Last_Name_key.'<br>';
        $map =   [
            '0'  => isset($row[$Company_key]) ? $row[$Company_key] : "", //Email
            '1'  => isset($row[$Brand_key]) ? $row[$Brand_key] : "", //Password
            '2'  => isset($row[$Item_key]) ? $row[$Item_key] : "", //Country
            '3'  => isset($row[$Price_key]) ? $row[$Price_key] : "", //Status
            '4'  => isset($row[$Note_key]) ? $row[$Note_key] : "", //Region
            '5'  => isset($row[$Salesman_Code_key]) ? $row[$Salesman_Code_key] : "", //Group Name
            '6'  => isset($row[$Image_key]) ? $row[$Image_key] : "" //capacity_key
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
                '3' => 'required',
                '4' => 'required',
                '5' => 'required|exists:customer_infos,customer_code',
                '6' => 'required',
            ];
        }else{
            return [
                '0' => 'required',
                '1' => 'required',
                '2' => 'required',
                '3' => 'required',
                '4' => 'required',
                '5' => 'required|exists:customer_infos,customer_code',
                '6' => 'required',
            ];
        }

    }
    public function customValidationMessages()
    {
        return [
            '0.required' => 'Company required',
            '1.required' => 'Brand required',
            '2.required' => 'Item required',
            '2.unique' => 'Item already_exist',
            '3.required' => 'Price required',
            '4.required' => 'Note required',
            '5.required' => 'Salesman code required',
            '5.exists' => 'Salesman code not exist',
            '6.required' => 'Image required',
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