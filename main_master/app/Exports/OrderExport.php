<?php

namespace App\Exports;

use App\Model\Item;
use App\Model\ItemMainPrice;
use App\Model\ItemMajorCategory;
use App\Model\ItemGroup;
use App\Model\Brand;
use App\Model\ItemUom;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use DB;

class OrderExport implements FromCollection, WithHeadings
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
		$order = DB::table('orders')
        ->join('customer_infos', 'customer_infos.id', '=', 'orders.customer_id','left')
        ->join('users', 'users.id', '=', 'customer_infos.user_id','left')
		->join('depots', 'depots.id', '=', 'orders.depot_id','left')
		->join('order_types', 'order_types.id', '=', 'orders.order_type_id','left')
		->join('payment_terms', 'payment_terms.id', '=', 'orders.payment_term_id','left')
		->join('order_details', 'order_details.order_id', '=', 'orders.id','left')
		->join('items', 'items.id', '=', 'order_details.item_id','left')
		->join('item_uoms', 'item_uoms.id', '=', 'order_details.item_uom_id','left')
		->join('price_disco_promo_plans', 'price_disco_promo_plans.id', '=', 'order_details.promotion_id','left')
        ->select('orders.order_number',/* 'users.email', 'depots.depot_code', */'order_types.name as order_type','orders.order_date','orders.delivery_date','payment_terms.name as payment_term' 
		,'orders.due_date' /*,'orders.total_qty'*/,'orders.total_gross','orders.total_discount_amount','orders.total_net'
		,'orders.total_vat','orders.total_excise','orders.grand_total','orders.any_comment'
		,/* 'orders.source', */'orders.status as order_status','orders.current_stage','items.item_name','item_uoms.name as item_uom'
		/* ,'order_details.discount_id' */,'order_details.is_free','order_details.is_item_poi'/* ,'price_disco_promo_plans.name as price_disco_promo_plan' */
		,'order_details.item_qty','order_details.item_price','order_details.item_gross','order_details.item_discount_amount','order_details.item_net'
		,'order_details.item_vat','order_details.item_excise','order_details.item_grand_total');

		if($start_date!='' && $end_date!=''){
			$order = $order->whereBetween('created_at', [$start_date, $end_date]);
		}
		$order = $order->where('orders.organisation_id', auth()->user()->organisation_id);
        $order = $order->get();

	// echo "<br>"; print_r($order); exit;
		
		/* if(is_object($order)){
			foreach($order as $key=>$ord){
				$ItemUom1 = ItemUom::find($itm->item_uom_id);
				$order[$key]->item_uom_id = (is_object($ItemUom1))?$ItemUom1->name:'';
			}
		} */
		
		return $order;
    }

	public function headings(): array
	{
		return [ 
			"Order Number",
			/* 	"Email", 
			"Depot Code",  */
			"Order Type",
			"Order Date",
			"Delivery Date",
			"Payment Term",
			"Due Date",
			/* "Total Qty", */
			"Total Gross",
			"Total Discount amount",
			"Total Net",
			"Total Vat",
			"Total Excise",
			"Grand Total",
			"Any Comment",
			/* 	"Source", */
			"Order Status",
			"Current Stage",
			"Item Name",
			"Item Uom",
			/* "Discount Id", */
			"Is Free",
			"Is Item Poi",
			/* "Price Disco Promo Plan", */
			"Item Qty",
			"Item Price",
			"Item Gross",
			"Item Discount amount",
			"Item Net",
			"Item Vat",
			"Item Excise",
			"Item Grand Total",
		];
	}
}
