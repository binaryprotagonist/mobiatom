<?php

namespace App\Imports;

use App\Model\Van;
use App\Model\VanType;
use App\Model\VanCategory;
use Maatwebsite\Excel\Concerns\ToModel;

class VanImport implements ToModel
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        if(isset($row[0]) && $row[0]!='Van Code'){
			$VanType = VanType::where('type',$row[4])->first();
			$VanCategory = VanCategory::where('name',$row[5])->first();
			
			$Van = new Van;
			$Van->van_code = $row[0];
			$Van->plate_number = $row[1];
			$Van->description = $row[2];
			$Van->capacity = $row[3];
			$Van->van_type_id = (is_object($VanType))?$VanType->id:0;
			$Van->van_category_id = (is_object($VanCategory))?$VanCategory->id:0;
			$Van->van_status = $row[6];
			$Van->save();
			updateNextComingNumber('App\Model\Van', 'Van');
			return $Van;
		}
    }
}
