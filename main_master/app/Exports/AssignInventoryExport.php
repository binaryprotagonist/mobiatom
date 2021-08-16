<?php

namespace App\Exports;

use App\Model\AssignInventory;
use App\Model\AssignInventoryCustomer;
use App\Model\AssignInventoryDetails;
use App\Model\AssignInventoryPost;
use App\Model\Item;
use App\Model\ItemUom;
use App\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Collection;

class AssignInventoryExport implements FromCollection,WithHeadings
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
		$assigninventorys = AssignInventory::select('*');
		if($start_date!='' && $end_date!=''){
			$assigninventorys = $assigninventorys->whereBetween('created_at', [$start_date, $end_date]);
		}
        $assigninventorys = $assigninventorys->get();

		$EstimationCollection = new Collection();
		if(is_object($assigninventorys))
		{
			foreach($assigninventorys as $assigninventory):
	
				$assigninventorycustomers = AssignInventoryCustomer::where('assign_inventory_id',$assigninventory->id)->get();
				$assigninventorydetails = AssignInventoryDetails::where('assign_inventory_id',$assigninventory->id)->get();
				
				if($assigninventorycustomers):
					$countinventorycustomer = count($assigninventorycustomers);
				else:
					$countinventorycustomer = 0;
				endif;	
				if($assigninventorydetails):
					$countinventorydetails = count($assigninventorydetails);
				else:
					$countinventorydetails = 0;
				endif;	
				
				if($countinventorycustomer > 0 || $countinventorydetails >0):
					if($countinventorycustomer >= $countinventorydetails):
						foreach($assigninventorycustomers as $key=>$customers):
							
							if(isset($assigninventorydetails[$key])):
								$item = Item::find($assigninventorydetails[$key]->item_id);
								$itemuom = ItemUom::find($assigninventorydetails[$key]->item_uom_id);
							else:
								$item = array();
								$itemuom = array();
							endif;
							$customer = User::find($customers->customer_id);
							
							$EstimationCollection->push((object)[
								'activity_name' => $assigninventory->activity_name,
								'valid_from' => date('m/d/Y',strtotime($assigninventory->valid_from)),
								'valid_to' => date('m/d/Y',strtotime($assigninventory->valid_to)),
								'status' => $assigninventory->status,
								'customer_id' => (is_object($customer))?$customer->email:'',
								'item_id' =>  (is_object($item))?$item->item_name:'',
								'item_uom_id' =>  (is_object($itemuom))?$itemuom->name:''
							]);
						endforeach;
					else:
						foreach($assigninventorydetails as $key=>$items):
							if(isset($assigninventorycustomers[$key])):
								$customer = User::find($assigninventorycustomers[$key]->customer_id);
							else:
								$customer = array();
							endif;	
							
							$item = Item::find($items->item_id);
							$itemuom = ItemUom::find($items->item_uom_id);
							$EstimationCollection->push((object)[
								'activity_name' => $assigninventory->activity_name,
								'valid_from' => date('m/d/Y',strtotime($assigninventory->valid_from)),
								'valid_to' => date('m/d/Y',strtotime($assigninventory->valid_to)),
								'status' => $assigninventory->status,
								'customer_id' => (is_object($customer))?$customer->email:'',
								'item_id' =>  (is_object($item))?$item->item_name:'',
								'item_uom_id' =>  (is_object($itemuom))?$itemuom->name:''
							]);
						endforeach;
					endif;	
				else:
					$EstimationCollection->push((object)[
						'activity_name' => $assigninventory->activity_name,
						'valid_from' => date('m/d/Y',strtotime($assigninventory->valid_from)),
						'valid_to' => date('m/d/Y',strtotime($assigninventory->valid_to)),
						'status' => $assigninventory->status,
						'customer_id' => '',
						'item_id' => '',
						'item_uom_id' => ''
					]);
				endif;
			endforeach;
		}
		return $EstimationCollection;
    }
	public function headings(): array
    {
        return [
            'Activity name',
			'Valid from',
			'Valid to',
			'Status',
			'Customer email',
			'Item',
			'Item UOM'
        ];
    }
}
