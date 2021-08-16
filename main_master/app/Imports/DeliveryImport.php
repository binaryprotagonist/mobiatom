<?php

namespace App\Imports;

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
use Maatwebsite\Excel\Concerns\ToModel;

class DeliveryImport implements ToModel
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
		if(isset($row[0]) && $row[0]!='Delivery Number'){
			$delivery_id = 0;
			$delivery_exist = Delivery::where('delivery_number',$row[0])->first();
			if(is_object($delivery_exist)){
				$delivery_id = $delivery_exist->id;
				$delivery = $delivery_exist;
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
				
				$Order = Order::where('order_number',$row[2])->first();
				
				$user1 = User::where('email',$row[3])->first();
				if(is_object($user1)){
					$SalesmanInfo = SalesmanInfo::where('user_id',$user1->id)->first();
					if(is_object($SalesmanInfo)){
						$salesman_id = $SalesmanInfo->id;
					}else{
						$salesman_id = 0;
					}
				}else{
					$salesman_id = 0;
				}
				
				$PaymentTerm = PaymentTerm::where('name',$row[6])->first();
				$OrderType = OrderType::where('name',$row[18])->first();
				
				$delivery = new Delivery;
				$delivery->customer_id   = $customer_id;
				$delivery->order_id = (is_object($Order))?$Order->id:0;
				$delivery->salesman_id = $salesman_id;
				$delivery->delivery_number = $row[0];
				$delivery->delivery_date = date('Y-m-d',strtotime($row[4]));
				$delivery->delivery_weight = $row[5];
				$delivery->payment_term_id = (is_object($PaymentTerm))?$PaymentTerm->id:0;
				$delivery->total_qty = $row[7];
				$delivery->total_gross = $row[8];
				$delivery->total_discount_amount = $row[9];
				$delivery->total_net = $row[10];
				$delivery->total_vat = $row[11];
				$delivery->total_excise = $row[12];
				$delivery->grand_total = $row[13];
				$delivery->current_stage_comment = $row[14];
				$delivery->source = $row[15];
				$delivery->status = $row[16];
				$delivery->current_stage       = $row[17];
				$delivery->delivery_type       = (is_object($OrderType))?$OrderType->id:0;
				$delivery->delivery_due_date = date('Y-m-d',strtotime($row[19]));
				$delivery->save();
				$delivery_id = $delivery->id;
			}	
			
				$item = Item::where('item_name',$row[20])->first();
				$item_uom = ItemUom::where('name',$row[21])->first();
				$PriceDiscoPromoPlan = PriceDiscoPromoPlan::where('name',$row[25])->first();
				
				$deliveryDetail = new DeliveryDetail;
                $deliveryDetail->delivery_id      = $delivery_id;
                $deliveryDetail->item_id       = (is_object($item))?$item->id:0;
                $deliveryDetail->item_uom_id   = (is_object($item_uom))?$item_uom->id:0;
                $deliveryDetail->discount_id   = $row[22];
                $deliveryDetail->is_free       = $row[23];
                $deliveryDetail->is_item_poi   = $row[24];
                $deliveryDetail->promotion_id  = (is_object($PriceDiscoPromoPlan))?$PriceDiscoPromoPlan->id:0;
                $deliveryDetail->item_qty      = $row[26];
                $deliveryDetail->item_price    = $row[27];
                $deliveryDetail->item_gross    = $row[28];
                $deliveryDetail->item_discount_amount = $row[29];
                $deliveryDetail->item_net      = $row[30];
                $deliveryDetail->item_vat      = $row[31];
                $deliveryDetail->item_excise   = $row[32];
                $deliveryDetail->item_grand_total = $row[33];
				$deliveryDetail->batch_number = $row[34];
                $deliveryDetail->save();
				return $delivery;
		}
    }
}
