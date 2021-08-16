<?php

namespace App\Imports;

use App\Model\Estimation;
use App\Model\EstimationDetail;
use App\User;
use App\Model\SalesPerson;
use App\Model\Item;
use App\Model\ItemUom;
use Maatwebsite\Excel\Concerns\ToModel;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Validators\Failure;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Throwable;

class EstimationImport implements ToModel, WithValidation, SkipsOnFailure, SkipsOnError
{
	use Importable, SkipsErrors, SkipsFailures;
	protected $skipduplicate;
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
	public function __construct(String  $skipduplicate)
	{
		$this->skipduplicate = $skipduplicate;
	}
	public function startRow(): int
    {
        return 2;
    }
    public function model(array $row)
    {
		$skipduplicate = $this->skipduplicate;
		if(isset($row[0]) && $row[0]!='Estimate code'){
			$SalesPerson = SalesPerson::where('email',$row[4])->first();
			$customer = User::where('email',$row[5])->first();
			$item = Item::where('item_name',$row[15])->first();
			$itemuom = ItemUom::where('name',$row[16])->first();
			
			$Estimation = Estimation::where('estimate_code',$row[0])->first();
						
			if(!is_object($Estimation)){
				$Estimation = new Estimation;
				$Estimation->estimate_code = $row[0];
				$Estimation->estimate_date = ($row[1]!='')?date('Y-m-d',strtotime($row[1])):null;
				$Estimation->expairy_date = ($row[2]!='')?date('Y-m-d',strtotime($row[2])):null;
				$Estimation->reference = $row[3];
				$Estimation->salesperson_id = (is_object($SalesPerson))?$SalesPerson->id:0;
				$Estimation->customer_id = (is_object($customer))?$customer->id:0;
				$Estimation->subject = $row[6];
				$Estimation->customer_note = $row[7];
				$Estimation->gross_total = $row[8];
				$Estimation->vat = $row[9];
				$Estimation->exise = $row[10];
				$Estimation->net_total = $row[11];
				$Estimation->discount = $row[12];
				$Estimation->total = $row[13];
				$Estimation->status = $row[14];
				$Estimation->save();
				
				$EstimationDetail = new EstimationDetail;
				$EstimationDetail->estimation_id = $Estimation->id;
				$EstimationDetail->item_id = (is_object($item))?$item->id:0;
				$EstimationDetail->item_uom_id = (is_object($itemuom))?$itemuom->id:0;
				$EstimationDetail->item_qty = $row[17];
				$EstimationDetail->item_price = $row[18];
				$EstimationDetail->item_discount_amount = $row[19];
				$EstimationDetail->item_vat = $row[20];
				$EstimationDetail->item_excise = $row[21];
				$EstimationDetail->item_grand_total = $row[12];
				$EstimationDetail->item_net = $row[23];
				$EstimationDetail->save();
			}else{
				if($skipduplicate == 0){
					$Estimation->estimate_code = $row[0];
					$Estimation->estimate_date = ($row[1]!='')?date('Y-m-d',strtotime($row[1])):null;
					$Estimation->expairy_date = ($row[2]!='')?date('Y-m-d',strtotime($row[2])):null;
					$Estimation->reference = $row[3];
					$Estimation->salesperson_id = (is_object($SalesPerson))?$SalesPerson->id:0;
					$Estimation->customer_id = (is_object($customer))?$customer->id:0;
					$Estimation->subject = $row[6];
					$Estimation->customer_note = $row[7];
					$Estimation->gross_total = $row[8];
					$Estimation->vat = $row[9];
					$Estimation->exise = $row[10];
					$Estimation->net_total = $row[11];
					$Estimation->discount = $row[12];
					$Estimation->total = $row[13];
					$Estimation->status = $row[14];
					$Estimation->save();
					
					$EstimationDetail = new EstimationDetail;
					$EstimationDetail->estimation_id = $Estimation->id;
					$EstimationDetail->item_id = (is_object($item))?$item->id:0;
					$EstimationDetail->item_uom_id = (is_object($itemuom))?$itemuom->id:0;
					$EstimationDetail->item_qty = $row[17];
					$EstimationDetail->item_price = $row[18];
					$EstimationDetail->item_discount_amount = $row[19];
					$EstimationDetail->item_vat = $row[20];
					$EstimationDetail->item_excise = $row[21];
					$EstimationDetail->item_grand_total = $row[22];
					$EstimationDetail->item_net = $row[23];
					$EstimationDetail->save();
				}
			}
			return $Estimation;
		}
    }
	public function rules(): array
    {
        return [
            '0' => 'required',
            '1' => 'required',
			'2' => 'required',
			'3' => 'required',
			'4' => 'required',
			'5' => 'required',
			'6' => 'required',
			'7' => 'required',
			'8' => 'required',
			'9' => 'required',
			'10' => 'required',
			'11' => 'required',
			'12' => 'required',
			'13' => 'required',
			'15' => 'required',
			'16' => 'required',
			'17' => 'required',
			'18' => 'required',
			'19' => 'required',
			'20' => 'required',
			'21' => 'required',
			'22' => 'required',
			'23' => 'required',
        ];
    }
	public function customValidationMessages()
	{
		return [
			'0.required' => 'Estimate code required',
			'1.required' => 'Estimate date required',
			'2.required' => 'Expairy date required',
			'3.required' => 'Reference required',
			'4.required' => 'Sales person required',
			'5.required' => 'Customer required',
			'6.required' => 'Subjectrequired',
			'7.required' => 'Customer note required',
			'8.required' => 'Gross total required',
			'9.required' => 'Vat required',
			'10.required' => 'Exise required',
			'11.required' => 'Net total required',
			'12.required' => 'Discount required',
			'13.required' => 'Total required',
			'15.required' => 'Item required',
			'16.required' => 'Item UOM required',
			'17.required' => 'Item qty required',
			'18.required' => 'Item price required',
			'19.required' => 'Item discount required',
			'20.required' => 'Item vat required',
			'21.required' => 'Item excise required',
			'22.required' => 'Item grand total required',
			'23.required' => 'Item net required',
		];
	}
	/* public function onFailure(Failure ...$failures)
    {
        // Handle the failures how you'd like.
    } */
}
