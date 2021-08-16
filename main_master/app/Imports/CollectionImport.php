<?php

namespace App\Imports;

use App\Model\Collection;
use App\Model\CollectionDetails;
use App\User;
use App\Model\Invoice;
use Maatwebsite\Excel\Concerns\ToModel;

class CollectionImport implements ToModel
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
		if(isset($row[0]) && $row[0]!='Collection Number'){
			$customer = User::where('email',$row[8])->first();
			$salesman = User::where('email',$row[9])->first();
			
			$collection = Collection::where('collection_number',$row[0])->first();
			$payemnt_type = '';
			if($row[1]=='Cash'){
				$payemnt_type = 1;
			}else if($row[1]=='Cheque'){
				$payemnt_type = 2;
			}else if($row[1]=='NEFT'){
				$payemnt_type = 3;
			}
			
			$source = "";
			if($row[7] == "Mobile"){
				$source = 1;
			}else if($row[7] == "Backend"){
				$source = 2;
			}else if($row[7] == "Frontend"){
				$source = 3;
			}
						
			if(!is_object($collection)){
				$collection = new Collection;
				$collection->collection_number = $row[0];
				$collection->collection_type = '1';
				$collection->payemnt_type = $payemnt_type;
				$collection->cheque_number = $row[2];
				$collection->cheque_date = ($row[3]!='')?date('Y-m-d',strtotime($row[3])):null;
				$collection->bank_info = $row[4];
				$collection->transaction_number = $row[5];
				$collection->status = $row[6];
				$collection->source = $source;
				$collection->current_stage = "Approved";
				$collection->customer_id = (is_object($customer))?$customer->id:0;
				$collection->salesman_id = (is_object($salesman))?$salesman->id:0;
				$collection->save();
			}
			
			$invoice = Invoice::where('invoice_number',$row[10])->first();
			
			$collectiondetail = new CollectionDetails;
            $collectiondetail->collection_id = $collection->id;
            $collectiondetail->invoice_id = (is_object($invoice))?$invoice->id:0;
            $collectiondetail->amount = $row[11];
            $collectiondetail->pending_amount = $row[12];
            $collectiondetail->save();
			return $collection;
		}
    }
}
