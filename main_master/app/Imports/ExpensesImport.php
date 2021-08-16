<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToModel;
use App\Model\CombinationMaster;
use App\Model\CombinationPlanKey;
use App\Model\Expenses;
use App\Model\ExpenseCategory;
use App\User;

class ExpensesImport implements ToModel
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
		if(isset($row[0]) && $row[0]!='Reference'){
			$customer = User::where('email',$row[1])->first();
			$ExpenseCategory = ExpenseCategory::where('name',$row[2])->first();
			$expenses = new Expenses;
            $expenses->reference            = $row[0];
			$expenses->customer_id          = (is_object($customer))?$customer->id:0;
			$expenses->expense_category_id  = (is_object($ExpenseCategory))?$ExpenseCategory->id:0;
            $expenses->expense_date       = date('Y-m-d',strtotime($row[3]));
			$expenses->amount            = $row[4];
            $expenses->save();
		}
    }
}
