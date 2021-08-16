<?php

namespace App\Imports;

use App\Model\Order;
use App\Model\OrderDetail;
use App\Model\CustomerInfo;
use App\User;
use App\Model\Depot;
use App\Model\OrderType;
use App\Model\PaymentTerm;
use App\Model\Item;
use App\Model\ItemUom;
use App\Model\PriceDiscoPromoPlan;
use Maatwebsite\Excel\Concerns\ToModel;

class OrderImport implements ToModel
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
		if(isset($row[0]) && $row[0]!='Order Number'){
			$order_id = 0;
			$order_exist = Order::where('order_number',$row[0])->first();
			if(is_object($order_exist)){
				$order_id = $order_exist->id;
				$order = $order_exist;
			}else{
				$user = User::where('email',$row[1])->first();
				if(is_object($user)){
					$CustomerInfo = CustomerInfo::where('user_id',$user->id)->first();
					if(is_object($CustomerInfo)){
						$customer_id = $CustomerInfo->id;
					}else{
						$customer_id = 0;
					}
				}else{
					$customer_id = 0;
				}
				$Depot = Depot::where('depot_code',$row[2])->first();
				$OrderType = OrderType::where('name',$row[3])->first();
				$PaymentTerm = PaymentTerm::where('name',$row[6])->first();
				
				$order = new Order;
				$order->customer_id         = $customer_id;
				$order->depot_id            = (is_object($Depot)) ? $Depot->id : 0;
				$order->order_type_id       = (is_object($OrderType)) ? $OrderType->id : 0;
				$order->order_number        = $row[0];
				$order->order_date          = date('Y-m-d',strtotime($row[4]));
				$order->delivery_date       = date('Y-m-d',strtotime($row[5]));
				$order->payment_term_id     = (is_object($PaymentTerm)) ? $PaymentTerm->id : 0;
				$order->due_date            = date('Y-m-d',strtotime($row[7]));
				$order->total_qty           = $row[8];
				$order->total_gross         = $row[9];
				$order->total_discount_amount   = $row[10];
				$order->total_net           = $row[11];
				$order->total_vat           = $row[12];
				$order->total_excise        = $row[13];
				$order->grand_total         = $row[14];
				$order->any_comment         = $row[15];
				$order->source              = $row[16];
				$order->status              = $row[17];
				$order->current_stage       = $row[18];
				$order->save();
				$order_id = $order->id;
			}	
			
				$item = Item::where('item_name',$row[19])->first();
				$item_uom = ItemUom::where('name',$row[20])->first();
				$PriceDiscoPromoPlan = PriceDiscoPromoPlan::where('name',$row[24])->first();
				
				$orderDetail = new OrderDetail;
                $orderDetail->order_id      = $order_id;
                $orderDetail->item_id       = (is_object($item))?$item->id:0;
                $orderDetail->item_uom_id   = (is_object($item_uom))?$item_uom->id:0;
                $orderDetail->discount_id   = $row[21];
                $orderDetail->is_free       = $row[22];
                $orderDetail->is_item_poi   = $row[23];
                $orderDetail->promotion_id  = (is_object($PriceDiscoPromoPlan))?$PriceDiscoPromoPlan->id:0;
                $orderDetail->item_qty      = $row[25];
                $orderDetail->item_price    = $row[26];
                $orderDetail->item_gross    = $row[27];
                $orderDetail->item_discount_amount = $row[28];
                $orderDetail->item_net      = $row[29];
                $orderDetail->item_vat      = $row[30];
                $orderDetail->item_excise   = $row[31];
                $orderDetail->item_grand_total = $row[32];
                $orderDetail->save();
				return $order;
		}
    }
}
