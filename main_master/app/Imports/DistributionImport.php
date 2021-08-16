<?php

namespace App\Imports;

use App\Model\Distribution;
use App\Model\DistributionCustomer;
use App\User;
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

class DistributionImport implements ToModel, WithValidation, SkipsOnFailure, SkipsOnError, WithMapping
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
        // pre($row, false);
        // pre($map_key_value_array);
        $Name_key = '0';
        $Start_date_key = '1';
        $End_date_key = '2';
        $Height_key = '3';
        $Width_key = '4';
        $Depth_key = '5';
        $Status_key = '6';
        $Customer_code_key = '7';
        $item_code_key = '8';
        $uom_key = '9';
        $Capacity_key = '10';
        $Total_no_of_facing_key = '11';
        $couter = 0;

        foreach ($map_key_value_array as $map_key_value_array_key => $map_key_value_array_value) {
            //$map_key_value_array_key.'--'.$map_key_value_array_value;
            //array_search($map_key_value_array_value,$heading_array,true);
            if ($couter == 0) {
                $Name_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 1) {
                $Start_date_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 2) {
                $End_date_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 3) {
                $Height_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 4) {
                $Width_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 5) {
                $Depth_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 6) {
                $Status_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 7) {
                $Customer_code_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 8) {
                $item_code_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 9) {
                $uom_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 10) {
                $Capacity_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 11) {
                $Total_no_of_facing_key = array_search($map_key_value_array_value, $heading_array, true);
            }
            $couter++;
        }
        $map =   [
            '0'  => isset($row[$Name_key]) ? $row[$Name_key] : "", //Last Name
            '1'  => isset($row[$Start_date_key]) ? $row[$Start_date_key] : "", //Email
            '2'  => isset($row[$End_date_key]) ? $row[$End_date_key] : "", //Password
            '3'  => isset($row[$Height_key]) ? $row[$Height_key] : "", //Country
            '4'  => isset($row[$Width_key]) ? $row[$Width_key] : "", //Status
            '5'  => isset($row[$Depth_key]) ? $row[$Depth_key] : "", //Region
            '6'  => isset($row[$Status_key]) ? $row[$Status_key] : "", //Group Name
            '7'  => isset($row[$Customer_code_key]) ? $row[$Customer_code_key] : "", //customer
            '8'  => isset($row[$item_code_key]) ? $row[$item_code_key] : "", // item
            '9'  => isset($row[$uom_key]) ? $row[$uom_key] : "", // uom
            '10'  => isset($row[$Capacity_key]) ? $row[$Capacity_key] : "", // capacity
            '11'  => isset($row[$Total_no_of_facing_key]) ? $row[$Total_no_of_facing_key] : "" // total no of facing
        ];
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
                '3' => 'required',
                '4' => 'required',
                '5' => 'required',
                '6' => 'required',
                '7' => 'required|exists:customer_infos,customer_code',
                '8' => 'required|exists:items,item_code',
                '9' => 'required|exists:item_uoms,name',
                '10' => 'required',
                '11' => 'required'
            ];
        } else {
            return [
                '0' => 'required|unique:distributions,name',
                '1' => 'required',
                '2' => 'required',
                '3' => 'required',
                '4' => 'required',
                '5' => 'required',
                '6' => 'required',
                '7' => 'required|exists:customer_infos,customer_code',
                '8' => 'required|exists:items,item_code',
                '9' => 'required|exists:item_uoms,name',
                '10' => 'required',
                '11' => 'required'
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
            '3.required' => 'height required',
            '4.required' => 'width required',
            '5.required' => 'depth required',
            '6.required' => 'status required',
            '7.required' => 'customer required',
            '7.exists' => 'customer not exist!',
            '8.required' => 'item required',
            '8.exists' => 'item not exist!',
            '9.required' => 'item uom required',
            '9.exists' => 'item uom not exist!',
            '10.required' => 'capacity required',
            '11.required' => 'Total no of facing required'
        ];
    }
    /* public function onFailure(Failure ...$failures)
    {
        // Handle the failures how you'd like.
    } */

    public function getRowCount(): int
    {
        return $this->rows;
    }

    public function successAllRecords()
    {
        return $this->rowsrecords;
    }
}
