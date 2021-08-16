<?php

namespace App\Exports;

use App\Model\Delivery;
use App\Model\DeliveryDetail;
use App\Model\Order;
use App\Model\CustomerInfo;
use App\Model\SalesmanInfo;
use App\User;
use App\Model\Depot;
use App\Model\OrderType;
use App\Model\PaymentTerm;
use App\Model\Item;
use App\Model\ItemUom;
use App\Model\PriceDiscoPromoPlan;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use DB;

class DeliveryExport implements FromCollection, WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
	protected $StartDate,$EndDate;
	public function __construct(String  $StartDate,String $EndDate)
	{
		$this->StartDate = $StartDate;
		$this->EndDate = $EndDate;
	}
    public function collection()
    {
		$start_date = $this->StartDate;
		$end_date = $this->EndDate;
		$order = DB::table('deliveries')
        ->join('customer_infos', 'customer_infos.id', '=', 'deliveries.customer_id','left')
        ->join('users', 'users.id', '=', 'customer_infos.user_id','left')
		->join('orders', 'orders.id', '=', 'deliveries.order_id','left')
		//->join('depots', 'depots.id', '=', 'deliveries.depot_id','left')
		->join('order_types', 'order_types.id', '=', 'deliveries.delivery_type','left')
		->join('payment_terms', 'payment_terms.id', '=', 'deliveries.payment_term_id','left')
		->join('delivery_details', 'delivery_details.delivery_id', '=', 'deliveries.id','left')
		->join('items', 'items.id', '=', 'delivery_details.item_id','left')
		->join('item_uoms', 'item_uoms.id', '=', 'delivery_details.item_uom_id','left')
		->join('price_disco_promo_plans', 'price_disco_promo_plans.id', '=', 'delivery_details.promotion_id','left')
        ->select('deliveries.delivery_number',/* 'users.email', */'orders.order_number','deliveries.salesman_id','deliveries.delivery_date',
		'deliveries.delivery_weight','payment_terms.name as payment_term','deliveries.total_qty','deliveries.total_gross','deliveries.total_discount_amount',
		'deliveries.total_net','deliveries.total_vat','deliveries.total_excise','deliveries.grand_total','deliveries.current_stage_comment',/* 'deliveries.source', */
		'deliveries.status','deliveries.current_stage','order_types.name as order_type','deliveries.delivery_due_date','items.item_name','item_uoms.name as item_uom',
		/* 'price_disco_promo_plans.name as price_disco_promo_plan', */'delivery_details.is_free','delivery_details.is_item_poi','delivery_details.item_qty',
		'delivery_details.item_price','delivery_details.item_gross','delivery_details.item_discount_amount','delivery_details.item_net','delivery_details.item_vat',
		'delivery_details.item_excise','delivery_details.item_grand_total','delivery_details.batch_number'
		);

		if($start_date!='' && $end_date!=''){
			$order = $order->whereBetween('created_at', [$start_date, $end_date]);
		}
		$order = $order->where('deliveries.organisation_id', auth()->user()->organisation_id);
        $order = $order->get();

		// echo "<pre>";  print_r($order); exit;
		// $userarray=[];

		if(is_object($order)){
			foreach($order as $key=>$ord){
			/* 	$SalesmanInfo = SalesmanInfo::find($ord->salesman_id);
				if(is_object($SalesmanInfo)){ */
					$User = User::find($ord->salesman_id);
					if(is_object($User)){
						$order[$key]->salesman_id = $User->firstname.' '.$User->lastname;
					}else{
						$order[$key]->salesman_id = '';
					}
				/* }else{
					$order[$key]->email = '';
				} */
			}
			//array_push($userarray, $User->email);
		}
		/* echo "<pre>";  print_r($userarray);	
		echo "<pre>";  print_r($order); exit; */
		
		return $order;
    }

	public function headings(): array
	{
		return [ 
			"Delivery Number",
			/* "Email", */
			"Order Number",
			"Salesman Name",
			"Delivery Date",
			"Delivery Weight",
			"Payment Term",
			"Total Qty",
			"Total Gross",
			"Total Discount Amount",
			"Total Net",
			"Total Vat",
			"Total Excise",
			"Grand Total",
			"Current Stage Comment",
			/* "Source", */
			"Status",
			"Current Stage",
			"Order Type",
			"Delivery Due Date",
			"Item Name",
			"Item Uom",
			/* "Price Disco Promo Plan", */
			"Is Free",
			"Is Item Poi",
			"Item Qty",
			"Item Price",
			"Item Gross",
			"Item Discount Amount",
			"Item Net",
			"Item Vat",
			"Item Excise",
			"Item Grand Total",
			"Batch Number",
		];
	}
}
