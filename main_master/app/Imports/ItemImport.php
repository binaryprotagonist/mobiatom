<?php

namespace App\Imports;

use App\Model\Item;
use App\Model\ItemMainPrice;
use App\Model\ItemMajorCategory;
use App\Model\ItemGroup;
use App\Model\Brand;
use App\Model\ItemUom;
use App\Model\WorkFlowObject;
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

class ItemImport implements ToModel, WithValidation, SkipsOnFailure, SkipsOnError, WithMapping
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
        return 2;
    }

    final public function map($row): array
    {
        $heading_array = $this->heading_array;
        $map_key_value_array = $this->map_key_value_array;
        $Item_Code_key = '0';
        $Item_Name_key = '1';
        $Item_Description_key = '2';
        $Barcode_key = '3';
        $Weight_key = '4';
        $Shelf_Life_key = '5';
        $Tax_Apply_key = '6';
        $Item_Uom_key = '7';
        $Lower_Unit_Item_UPC_key = '8';
        $Lower_Unit_Item_Price_key = '9';
        $Vat_Percentage_key = '10';
        $Stock_Keeping_Unit_key = '11';
        $Volume_key = '12';
        $Item_Major_Category_key = '13';
        $Item_Sub_Category_key = '14';
        $Item_Group_key = '15';
        $Brand_key = '16';
        $Sub_Brand_key = '17';
        $Lob_key = '18';
        $Secondary_UOM_key = '19';
        $Secondary_Item_UPC_key = '20';
        $Secondary_UOM_Price_key = '21';
        $Item_Stock_Keeping_Unit_key = '22';
        $Status_key = '23';
        $Promotional_key = '24';
        $Product_Catalog_key = '25';
        $Item_image_key = '26';
        $Net_Weight_key = '27';
        $Flawer_key = '28';
        $Shelf_File_key = '29';
        $Ingredients_key = '30';
        $Energy_key = '31';
        $Fat_key = '32';
        $Protein_key = '33';
        $Carbohydrate_key = '34';
        $Calcium_key = '35';
        $Sodium_key = '36';
        $Potassium_key = '37';
        $Crude_Fibre_key = '38';
        $Vitamin_key = '39';
        $Catalog_Image_key = '40';
        $erp_code = '41';
        $couter = 0;

        foreach ($map_key_value_array as $map_key_value_array_key => $map_key_value_array_value) {
            //$map_key_value_array_key.'--'.$map_key_value_array_value;
            //array_search($map_key_value_array_value,$heading_array,true);
            if ($couter == 0) {
                $Item_Code_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 1) {
                $Item_Name_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 2) {
                $Item_Description_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 3) {
                $Barcode_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 4) {
                $Weight_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 5) {
                $Shelf_Life_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 6) {
                $Tax_Apply_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 7) {
                $Item_Uom_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 8) {
                $Lower_Unit_Item_UPC_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 9) {
                $Lower_Unit_Item_Price_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 10) {
                $Vat_Percentage_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 11) {
                $Stock_Keeping_Unit_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 12) {
                $Volume_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 13) {
                $Item_Major_Category_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 14) {
                $Item_Sub_Category_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 15) {
                $Item_Group_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 16) {
                $Brand_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 17) {
                $Sub_Brand_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 18) {
                $Lob_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 19) {
                $Secondary_UOM_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 20) {
                $Secondary_Item_UPC_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 21) {
                $Secondary_UOM_Price_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 22) {
                $Item_Stock_Keeping_Unit_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 23) {
                $Status_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 24) {
                $Promotional_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 25) {
                $Product_Catalog_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 26) {
                $Net_Weight_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 27) {
                $Item_image_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 28) {
                $Flawer_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 29) {
                $Shelf_File_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 30) {
                $Ingredients_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 31) {
                $Energy_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 32) {
                $Fat_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 33) {
                $Protein_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 34) {
                $Carbohydrate_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 35) {
                $Calcium_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 36) {
                $Sodium_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 37) {
                $Potassium_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 38) {
                $Crude_Fibre_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 39) {
                $Vitamin_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 40) {
                $Catalog_Image_key = array_search($map_key_value_array_value, $heading_array, true);
            } else if ($couter == 41) {
                $erp_code = array_search($map_key_value_array_value, $heading_array, true);
            }
            $couter++;
        }

        $map = [
            '0' => isset($row[$Item_Code_key]) ? $row[$Item_Code_key] : "", //First Name
            '1' => isset($row[$Item_Name_key]) ? $row[$Item_Name_key] : "", //Last Name
            '2' => isset($row[$Item_Description_key]) ? $row[$Item_Description_key] : "", //Email
            '3' => isset($row[$Barcode_key]) ? $row[$Barcode_key] : "", //Password
            '4' => isset($row[$Weight_key]) ? $row[$Weight_key] : "", //Mobile
            '5' => isset($row[$Shelf_Life_key]) ? $row[$Shelf_Life_key] : "", //Country
            '6' => isset($row[$Tax_Apply_key]) ? $row[$Tax_Apply_key] : "", //Status
            '7' => isset($row[$Item_Uom_key]) ? $row[$Item_Uom_key] : "", //Region
            '8' => isset($row[$Lower_Unit_Item_UPC_key]) ? $row[$Lower_Unit_Item_UPC_key] : "", //Group Name
            '9' => isset($row[$Lower_Unit_Item_Price_key]) ? $row[$Lower_Unit_Item_Price_key] : "", //Group Name
            '10' => isset($row[$Vat_Percentage_key]) ? $row[$Vat_Percentage_key] : "", //Group Name
            '11' => isset($row[$Stock_Keeping_Unit_key]) ? $row[$Stock_Keeping_Unit_key] : "", //Group Name
            '12' => isset($row[$Volume_key]) ? $row[$Volume_key] : "", //Group Name
            '13' => isset($row[$Item_Major_Category_key]) ? $row[$Item_Major_Category_key] : "", //Group Name
            '14' => isset($row[$Item_Sub_Category_key]) ? $row[$Item_Sub_Category_key] : "", //Group Name
            '15' => isset($row[$Item_Group_key]) ? $row[$Item_Group_key] : "", //Group Name
            '16' => isset($row[$Brand_key]) ? $row[$Brand_key] : "", //Brand Name
            '17' => isset($row[$Sub_Brand_key]) ? $row[$Sub_Brand_key] : "", //Sub Brand Name
            '18' => isset($row[$Lob_key]) ? $row[$Lob_key] : "", //LOB Name
            '19' => isset($row[$Secondary_UOM_key]) ? $row[$Secondary_UOM_key] : "", //Group Name
            '20' => isset($row[$Secondary_Item_UPC_key]) ? $row[$Secondary_Item_UPC_key] : "", //Group Name
            '21' => isset($row[$Secondary_UOM_Price_key]) ? $row[$Secondary_UOM_Price_key] : "", //Group Name
            '22' => isset($row[$Item_Stock_Keeping_Unit_key]) ? $row[$Item_Stock_Keeping_Unit_key] : "", //Group Name
            '23' => isset($row[$Status_key]) ? $row[$Status_key] : "", //Group Name
            '24' => isset($row[$Promotional_key]) ? $row[$Promotional_key] : "", //Group Name
            '25' => isset($row[$Product_Catalog_key]) ? $row[$Product_Catalog_key] : "", //Group Name
            '26' => isset($row[$Item_image_key]) ? $row[$Item_image_key] : "", //Group Name
            '27' => isset($row[$Net_Weight_key]) ? $row[$Net_Weight_key] : "", //Group Name
            '28' => isset($row[$Flawer_key]) ? $row[$Flawer_key] : "", //Group Name
            '29' => isset($row[$Shelf_File_key]) ? $row[$Shelf_File_key] : "", //Group Name
            '30' => isset($row[$Ingredients_key]) ? $row[$Ingredients_key] : "", //Group Name
            '31' => isset($row[$Energy_key]) ? $row[$Energy_key] : "", //Group Name
            '32' => isset($row[$Fat_key]) ? $row[$Fat_key] : "", //Group Name
            '33' => isset($row[$Protein_key]) ? $row[$Protein_key] : "", //Group Name
            '34' => isset($row[$Carbohydrate_key]) ? $row[$Carbohydrate_key] : "", //Group Name
            '35' => isset($row[$Calcium_key]) ? $row[$Calcium_key] : "", //Group Name
            '36' => isset($row[$Sodium_key]) ? $row[$Sodium_key] : "", //Group Name
            '37' => isset($row[$Potassium_key]) ? $row[$Potassium_key] : "", //Group Name
            '38' => isset($row[$Crude_Fibre_key]) ? $row[$Crude_Fibre_key] : "", //Group Name
            '39' => isset($row[$Vitamin_key]) ? $row[$Vitamin_key] : "", //Group Name
            '40' => isset($row[$Catalog_Image_key]) ? $row[$Catalog_Image_key] : "", //Group Name
            '41' => isset($row[$erp_code]) ? $row[$erp_code] : "", //Group Name
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
                // '2' => 'required',
                // '3' => 'required',
                // '4' => 'required',
                // '5' => 'required',
                '6' => 'required',
                '7' => 'required',
                '8' => 'required',
                '9' => 'required',
                '10' => 'required',
                '11' => 'required',
                // '12' => 'required',
                '13' => 'required',
                // '14' => 'required',
                '15' => 'required',
                '16' => 'required',
                // '16' => 'required|exists:item_uoms,name',
                // '17' => 'required',
                // '18' => 'required',
                // '19' => 'required',
                // '20' => 'required',
                // '21' => 'required',
                '23' => 'required|in:Yes,No',
                '24' => 'required|in:Yes,No',
                '25' => 'required|in:Yes,No',
                // '25' => 'required',
                // '26' => 'required',
                // '27' => 'required',
                // '28' => 'required',
                // '29' => 'required',
                // '30' => 'required',
                // '31' => 'required',
                // '32' => 'required',
                // '33' => 'required',
                // '34' => 'required',
                // '35' => 'required',
                // '36' => 'required'
            ];
        } else {
            return [
                '0' => 'required',
                '1' => 'required',
                // '2' => 'required',
                // '3' => 'required',
                // '4' => 'required',
                // '5' => 'required',
                '6' => 'required',
                '7' => 'required',
                '8' => 'required',
                '9' => 'required',
                '10' => 'required',
                '11' => 'required',
                // '12' => 'required',
                '13' => 'required',
                // '14' => 'required',
                '15' => 'required',
                '16' => 'required',
                // '16' => 'required|exists:item_uoms,name',
                // '17' => 'required',
                // '18' => 'required',
                // '19' => 'required',
                // '20' => 'required',
                // '21' => 'required',
                '23' => 'required|in:Yes,No',
                '24' => 'required|in:Yes,No',
                '25' => 'required|in:Yes,No',
                // '25' => 'required',
                // '26' => 'required',
                // '27' => 'required',
                // '28' => 'required',
                // '29' => 'required',
                // '30' => 'required',
                // '31' => 'required',
                // '32' => 'required',
                // '33' => 'required',
                // '34' => 'required',
                // '35' => 'required',
                // '36' => 'required'
            ];
        }
    }

    public function customValidationMessages()
    {
        return [
            '0.required' => 'Item Code required',
            '0.unique' => 'Item Code already_exists',
            '1.required' => 'Item Name required',
            '2.required' => 'Item Description required',
            // '3.required' => 'Barcode required',
            // '4.required' => 'Weight required',
            // '5.required' => 'Shelf Life required',
            '6.required' => 'Tax Apply required',
            '7.required' => 'Item Uom required',
            '7.exists' => 'Item Uom not exist!',
            '8.required' => 'Lower Unit Item UPC required',
            '9.required' => 'Lower Unit Item Price required',
            '10.required' => 'Vat Percentage required',
            '11.required' => 'Stock Keeping Unit required',
            // '12.required' => 'Volume (ltr) required',
            '13.required' => 'Item Major Category required',
            '13.exists' => 'Item Major Category not exist',
            '15.required' => 'Item Group required',
            '15.exists' => 'Item Group not exist',
            '16.required' => 'Brand required',
            '16.exists' => 'Brand not exist',
            // '16.required' => 'Secondary Item UOM required',
            // '16.exists' => 'Secondary Item UOM not exist',
            // '17.required' => 'Secondary Item UPC required',
            // '18.required' => 'Secondary UOM Price required',
            // '19.required' => 'Item Stock Keeping Unit required',
            // '20.required' => 'Item Status required',
            // '22.required' => '',
            '23.required' => 'Item Status required',
            '24.required' => 'Promotional required',
            '25.required' => 'Product Catalog required',
            // '23.required' => 'Net Weight required',
            // '24.required' => 'Flawer required',
            // '26.required' => 'Ingredients required',
            // '27.required' => 'Energy required',
            // '28.required' => 'Fat required',
            // '29.required' => 'Protein required',
            // '30.required' => 'Carbohydrate required',
            // '31.required' => 'Calcium required',
            // '32.required' => 'Sodium required',
            // '33.required' => 'Potassium required',
            // '34.required' => 'Crude Fibre required',
            // '35.required' => 'Vitamin required',
            // '36.required' => 'Image required',
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
