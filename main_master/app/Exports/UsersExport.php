<?php

namespace App\Exports;

use App\User;
use App\Model\CustomerInfo;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Collection;

class UsersExport implements FromCollection, WithHeadings
{
	/**
	 * @return \Illuminate\Support\Collection
	 */
	protected $StartDate, $EndDate;
	public function __construct(String  $StartDate, String $EndDate)
	{
		$this->StartDate = $StartDate;
		$this->EndDate = $EndDate;
	}
	public function collection()
	{
		$start_date = $this->StartDate;
		$end_date = $this->EndDate;

		$users = CustomerInfo::with(
				'user:id,organisation_id,usertype,firstname,lastname,email,mobile,role_id,country_id,status,parent_id',
                'user_country',
                'customerMerchandiser',
                'customerMerchandiser.merchandizer',
                'route:id,route_code,route_name,status',
                'channel:id,name,status',
                'region:id,region_name,region_status',
                'customerGroup:id,group_code,group_name',
                'customerCategory:id,customer_category_code,customer_category_name',
                'customerType:id,customer_type_name',
                'salesOrganisation:id,name',
				'paymentTerm:id,name',
                'shipToParty:id,user_id,customer_code',
                'shipToParty.user:id,firstname,lastname',
                'soldToParty:id,user_id,customer_code',
                'soldToParty.user:id,firstname,lastname',
                'payer:id,user_id,customer_code',
                'payer.user:id,firstname,lastname',
                'billToPayer:id,user_id,customer_code',
                //'paymentTerm:id,name,number_of_days',
                'billToPayer.user:id,firstname,lastname',
                'customFieldValueSave',
                'customFieldValueSave.customField',


				'customerlob',
                'customerlob.route:id,route_code,route_name,status',
                'customerlob.channel:id,name,status',
                'customerlob.region:id,region_code,region_name,region_status',
                'customerlob.customerType:id,customer_type_name',
                'customerlob.customerCategory:id,customer_category_code,customer_category_name',
                'customerlob.customerGroup:id,group_code,group_name',
                'customerlob.salesOrganisation:id,name',
                'customerlob.lob:id,name', 
                'customerlob.paymentTerm:id,name', 
                'customerlob.shipToParty:id,customer_code,user_id',
                'customerlob.shipToParty.user',
                'customerlob.soldToParty:id,customer_code,user_id',
                'customerlob.soldToParty.user',
                'customerlob.payer:id,customer_code,user_id',
                'customerlob.payer.user',
                'customerlob.billToPayer:id,customer_code,user_id',
                'customerlob.billToPayer.user',
                'customerDocument'

		);

		if ($start_date != '' && $end_date != '') {
			$users = $users->whereBetween('created_at', [$start_date, $end_date]);
		}

		$users = $users->get();

		$user_collection = new Collection();
		if (is_object($users)) {
			foreach ($users as $key => $user_detils) {
				$firstname = "N/A"; 
				$lastname = "N/A"; 
				$email = "N/A"; 
				$mobile = "N/A"; 
				$address_1 = "N/A"; 
				$address_2 = "N/A"; 
				$city = "N/A"; 
				$state = "N/A"; 
				$zipcode = "N/A"; 
				$phone = "N/A"; 
				$balance = 0; 
				$credit_limit = 0; 
				$country_name = "N/A"; 
				$region_name = "N/A"; 
				$sales_organisation = "N/A"; 
				$customer_group = "N/A"; 
				$route ="N/A"; 
				$channel = "N/A"; 
				$customer_category =  "N/A";
				$customer_type = "N/A";
				$lob = "N/A";
				$payment_term = "N/A";
				$ship_to_party = "N/A";
				$payer = "N/A";
				$bill_to_party = "N/A";				

				if (is_object($user_detils->user)) {
					$firstname = $user_detils->user->firstname;
					$lastname = $user_detils->user->lastname;
					$email = $user_detils->user->email;
					$mobile = $user_detils->user->mobile;
				}
				if (is_object($user_detils->user_country)) {
					if (is_object($user_detils->user_country->country)) {
						$country_name = $user_detils->user_country->country->name;
					}else{
						$country_name = "N/A"; 
					} 
				}
				if (is_object($user_detils->paymentTerm)) {
					$payment_term = $user_detils->paymentTerm->name;
				}

				if (count($user_detils->customerlob)) {
					foreach ($user_detils->customerlob as $dkey => $detail) {
						if (is_object($users[$key])) {

							if (is_object($detail->region)) {
								$region_name =  $detail->region->region_name;
							}	
							if (is_object($detail->salesOrganisation)) {
								$sales_organisation =  $detail->salesOrganisation->name;
							}
							if (is_object($detail->customerGroup)) {
								$customer_group =  $detail->customerGroup->group_name;
							}
							if (is_object($detail->route)) {
								$route =  $detail->route->route_name;
							}

							if (is_object($detail->channel)) {
								$channel =  $detail->channel->name;
							}
							if (is_object($detail->customerCategory)) {
								$customer_category =  $detail->customerCategory->customer_category_name;
							}
							if (is_object($detail->customerType)) {
								$customer_type =  $detail->customerType->customer_type_name;
							}
							if (is_object($detail->lob)) {
								$lob =  $detail->lob->name;
							}

							if (is_object($detail->shipToParty )) {
								$ship_to_party =  $detail->shipToParty->customer_code;
							}

							if (is_object($detail->soldToParty )) {
								$sold_to_party =  $detail->soldToParty->customer_code;
							}

							if (is_object($detail->Payer )) {
								$payer =  $detail->Payer->customer_code;
							}			

							if (is_object($detail->billToPayer )) {
								$bill_to_party =  $detail->billToPayer->customer_code;
							}
							
							$user_collection->push((object)[								
								
								'Customer Code'=> (!empty($user_detils->customer_code) ? $user_detils->customer_code :  "N/A"),
								'First_Name'=>$firstname,
								'Last_Name'=>$lastname,
								'Email"'=> $email,
								'Mobile'=> $mobile,
								'Country'=> $country_name,
								'Region'=>  $region_name,
								'Sales_Organisation'=> $sales_organisation,
								'Customer_Group'=> $customer_group,
								'Route'=> $route,
								'Channel'=> $channel,
								'Customer_Category'=> $customer_category,
								'Customer_Type'=> $customer_type,
								'Address_1'=> (!empty($users[$key]->customer_address_1) ? $users[$key]->customer_address_1 : "N/A"),
								'Address_2'=> (!empty($users[$key]->customer_address_2) ? $users[$key]->customer_address_2 : "N/A"),
								'City'=> (!empty($users[$key]->customer_city) ? $users[$key]->customer_city : "N/A"),
								'State'=> (!empty($users[$key]->customer_state) ? $users[$key]->customer_state : "N/A"),
								'Zipcode'=> (!empty($users[$key]->customer_zipcode) ? $users[$key]->customer_zipcode : "N/A"),
								'Phone'=> (!empty($users[$key]->customer_phone) ? $users[$key]->customer_phone : "N/A"),
								'LOB'=> $lob,
								'Balance'=> (!empty($detail->balance) ? $detail->balance :  0),
								'Credit_Limit'=> (!empty($detail->credit_limit) ? $detail->credit_limit :  0),	 							 
								'Credit_Days'=> (!empty($detail->credit_days) ? $detail->credit_days :  0),
								'Payment_Term'=> $payment_term,
								'Ship to party'=> $ship_to_party,
								'Sold to party'=> $sold_to_party,
								'Payer'=> $payer,
								'Bill to party'=> $bill_to_party,
								'Latitude'=> (!empty($users[$key]->customer_address_1_lat) ? $users[$key]->customer_address_1_lat : "N/A"),
								'Longitude'=> (!empty($users[$key]->customer_address_1_lang) ? $users[$key]->customer_address_1_lang : "N/A"),
								'ERP Code'=> (!empty($users[$key]->erp_code) ? $users[$key]->erp_code : "N/A"),
							]);						
						}						
					}
				}else{

					if (is_object($users[$key])) {

						if (is_object($user_detils->region)) {
							$region_name =  $user_detils->region->region_name;
						}	
						if (is_object($user_detils->salesOrganisation)) {
							$sales_organisation =  $user_detils->salesOrganisation->name;
						}
						if (is_object($user_detils->customerGroup)) {
							$customer_group =  $user_detils->customerGroup->group_name;
						}
						if (is_object($user_detils->route)) {
							$route =  $user_detils->route->route_name;
						}

						if (is_object($user_detils->channel)) {
							$channel =  $user_detils->channel->name;
						}
						if (is_object($user_detils->customerCategory)) {
							$customer_category =  $user_detils->customerCategory->customer_category_name;
						}
						if (is_object($user_detils->customerType)) {
							$customer_type =  $user_detils->customerType->customer_type_name;
						}
						if (is_object($user_detils->lob)) {
							$lob =  $user_detils->lob->name;
						}

						if (is_object($user_detils->shipToParty )) {
							$ship_to_party =  $user_detils->shipToParty->customer_code;
						}

						if (is_object($user_detils->soldToParty )) {
							$sold_to_party =  $user_detils->soldToParty->customer_code;
						}

						if (is_object($user_detils->Payer )) {
							$payer =  $user_detils->Payer->customer_code;
						} 

						if (is_object($user_detils->billToPayer )) {
							$bill_to_party =  $user_detils->billToPayer->customer_code;
						}
					
						$user_collection->push((object)[
							'Customer Code'=> (!empty($user_detils->customer_code) ? $user_detils->customer_code :  "N/A"),
							'First_Name'=>$firstname,
							'Last_Name'=>$lastname,
							'Email"'=> $email,
							'Mobile'=> $mobile,
							'Country'=> $country_name,
							'Region'=>  $region_name,
							'Sales_Organisation'=> $sales_organisation,
							'Customer_Group'=> $customer_group,
							'Route'=> $route,
							'Channel'=> $channel,
							'Customer_Category'=> $customer_category,
							'Customer_Type'=> $customer_type,
							'Address_1'=> (!empty($users[$key]->customer_address_1) ? $users[$key]->customer_address_1 : "N/A"),
							'Address_2'=> (!empty($users[$key]->customer_address_2) ? $users[$key]->customer_address_2 : "N/A"),
							'City'=> (!empty($users[$key]->customer_city) ? $users[$key]->customer_city : "N/A"),
							'State'=> (!empty($users[$key]->customer_state) ? $users[$key]->customer_state : "N/A"),
							'Zipcode'=> (!empty($users[$key]->customer_zipcode) ? $users[$key]->customer_zipcode : "N/A"),
							'Phone'=> (!empty($users[$key]->customer_phone) ? $users[$key]->customer_phone : "N/A"),
							'LOB'=> $lob,
							'Balance'=> (!empty($detail->balance) ? $detail->balance :  0),
							'Credit_Limit'=> (!empty($detail->credit_limit) ? $detail->credit_limit :  0),	 							 
							'Credit_Days'=> (!empty($detail->credit_days) ? $detail->credit_days :  0),
							'Payment_Term'=> $payment_term,
							'Ship to party'=> $ship_to_party,
							'Sold to party'=> $sold_to_party,
							'Payer'=> $payer,
							'Bill to party'=> $bill_to_party,
							'Latitude'=> (!empty($users[$key]->customer_address_1_lat) ? $users[$key]->customer_address_1_lat : "N/A"),
							'Longitude'=> (!empty($users[$key]->customer_address_1_lang) ? $users[$key]->customer_address_1_lang : "N/A"),
							'ERP Code'=> (!empty($users[$key]->erp_code) ? $users[$key]->erp_code : "N/A"),
						]);						
					}

				} 
				 
			}
		}
		return $user_collection;
	}

	public function headings(): array
	{
		return [ 
			"Customer Code",
			"First Name",
			"Last Name",
			"Email",			 
			"Mobile",
			"Country",
			"Region",
			"Sales Organisation",
			"Customer Group",
			"Route",
			"Channel",
			"Customer Category",
			"Customer Type",
			"Address_1",
			"Address_2",
			"City",
			"State",
			"Zipcode",
			"Phone",
			"LOB",
			"Balance",	
			"Credit Limit",			 
			"Credit Days",
			"Payment Term",
			"Ship to party",
			"Sold to party",
			"Payer",
			"Bill to party",
			"Latitude",
			"Longitude",
			"ERP Code", 
		];
	}
}
