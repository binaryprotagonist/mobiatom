<?php

namespace App\Imports;

use App\Model\AssignInventory;
use App\Model\AssignInventoryCustomer;
use App\Model\AssignInventoryDetails;
use App\User;
use App\Model\Item;
use App\Model\ItemUom;
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

class AssignInventoryImport implements ToModel, WithValidation, SkipsOnFailure, SkipsOnError, WithMapping
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

        $Activity_name_key = '0';
        $Valid_from_key = '1';
        $Valid_to_key = '2';
        $Status_key = '3';
        $Customer_code_key = '4';
        $Item_key = '5';
        $Item_UOM_key = '6';
        $Capacity_key = '7';
        $couter = 0;
        foreach ($map_key_value_array as $map_key_value_array_key => $map_key_value_array_value) {
            //$map_key_value_array_key.'--'.$map_key_value_array_value;
            //array_search($map_key_value_array_value,$heading_array,true);
            if ($couter == 0) {
                $Activity_name_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 1) {
                $Valid_from_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 2) {
                $Valid_to_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 3) {
                $Status_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 4) {
                $Customer_code_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 5) {
                $Item_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 6) {
                $Item_UOM_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 7) {
                $Capacity_key = array_search($map_key_value_array_value, $heading_array, true);
            }
            $couter++;
        }
        $map =   [
            '0'  => isset($row[$Activity_name_key]) ? $row[$Activity_name_key] : "", //Last Name
            '1'  => isset($row[$Valid_from_key]) ? $row[$Valid_from_key] : "", //Email
            '2'  => isset($row[$Valid_to_key]) ? $row[$Valid_to_key] : "", //Password
            '3'  => isset($row[$Status_key]) ? $row[$Status_key] : "", //Country
            '4'  => isset($row[$Customer_code_key]) ? $row[$Customer_code_key] : "", //Status
            '5'  => isset($row[$Item_key]) ? $row[$Item_key] : "", //Region
            '6'  => isset($row[$Item_UOM_key]) ? $row[$Item_UOM_key] : "", //Group Name
            '7'  => isset($row[$Capacity_key]) ? $row[$Capacity_key] : "" //capacity_key
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
                '4' => 'required|exists:customer_infos,customer_code',
                '5' => 'required|exists:items,item_code',
                '6' => 'required|exists:item_uoms,name',
                '7' => 'required'
            ];
        } else {
            return [
                '0' => 'required|unique:assign_inventories,activity_name',
                '1' => 'required',
                '2' => 'required',
                '3' => 'required',
                '4' => 'required|exists:customer_infos,customer_code',
                '5' => 'required|exists:items,item_code',
                '6' => 'required|exists:item_uoms,name',
                '7' => 'required'
            ];
        }
    }
    public function customValidationMessages()
    {
        return [
            '0.required' => 'Activity name required',
            '0.unique' => 'Activity name already_exists',
            '1.required' => 'Valid from date required',
            '2.required' => 'Valid to date required',
            '3.required' => 'Status required',
            '4.required' => 'Customer code required',
            '4.exists' => 'Customer code not exists',
            '5.required' => 'Item required',
            '5.exists' => 'Item not exists',
            '6.required' => 'Item UOM required',
            '6.exists' => 'Item UOM not exists',
            '7.required' => 'capacity required',
        ];
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
