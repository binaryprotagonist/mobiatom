<?php

namespace App\Exports;

use App\Model\Expenses;
use App\Model\ExpenseCategory;
use App\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Collection;

class ExpensesExport implements FromCollection,WithHeadings
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
		$Expenses = Expenses::select('*');
		if($start_date!='' && $end_date!=''){
			$Expenses = $Expenses->whereBetween('created_at', [$start_date, $end_date]);
		}
        $Expenses = $Expenses->get();
		
		$ExpensesCollection = new Collection();
		if(is_object($Expenses)){
			foreach($Expenses as $Expense){
				$customer = User::find($Expense->customer_id);
				$expense_category = ExpenseCategory::find($Expense->expense_category_id);
				$ExpensesCollection->push((object)[
					'reference' => $Expense->reference,
					'customer' => (is_object($customer))?$customer->email:'',
					'expense_category' => (is_object($expense_category))?$expense_category->name:'',
					'expense_date' => $Expense->expense_date,
					'amount' => $Expense->amount,
				]);
			}
		}
		return $ExpensesCollection;
    }
	public function headings(): array
    {
        return [
            'Reference',
            'Customer email',
			'Expense category',
			'Expense date',
			'Amount'
        ];
    }
}
