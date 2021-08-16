<?php

namespace App\Exports;

use App\Model\Item;
use App\Model\ItemMainPrice;
use App\Model\ItemMajorCategory;
use App\Model\ItemGroup;
use App\Model\Brand;
use App\Model\ItemUom;
use Maatwebsite\Excel\Concerns\FromCollection;
use DB;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ItemExport implements FromCollection, WithHeadings
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
		$start_date = date('Y-m-d', strtotime('-1 days', strtotime($this->StartDate)));
		$end_date = $this->EndDate;

		$item_query = Item::select('id', 'item_major_category_id', 'item_group_id', 'brand_id', 'item_code', 'erp_code', 'item_name', 'item_description', 'item_barcode', 'item_weight', 'item_shelf_life', 'lower_unit_item_upc', 'lower_unit_uom_id', 'lower_unit_item_price', 'is_tax_apply', 'item_vat_percentage', 'item_excise', 'lob_id', 'current_stage', 'current_stage_comment', 'status', 'stock_keeping_unit', 'volume', 'supervisor_category_id')
			->with(
				'itemUomLowerUnit:id,name,code',
				'ItemMainPrice:id,item_id,item_upc,item_uom_id,item_price,purchase_order_price,stock_keeping_unit',
				'ItemMainPrice.itemUom:id,name,code',
				'itemMajorCategory:id,name',
				'itemGroup:id,name,code',
				'brand:id,brand_name',
				'productCatalog',
				// 'lob',
				'supervisorCategory:id,name'
			);

		if ($start_date != '' && $end_date != '') {
			$item_query->whereBetween('created_at', [$start_date, $end_date]);
		}

		$item = $item_query->get();

		foreach ($item as $iKey => $i) {
			if (count($i->ItemMainPrice)) {
				foreach ($i->ItemMainPrice as $main) {
					$item[$iKey]->Name = $i->item_name;
					$item[$iKey]->Code = $i->item_code;
					$item[$iKey]->ERPCode = $i->erp_code;
					$item[$iKey]->Description = $i->item_description;
					$item[$iKey]->Barcode = $i->item_barcode;
					$item[$iKey]->Weight = $i->item_weight;
					$item[$iKey]->ShelfPrice = $i->item_shelf_life;
					$item[$iKey]->TaxApply = $i->is_tax_apply;
					$item[$iKey]->ItemUom = (is_object($i->itemUomLowerUnit)) ? $i->itemUomLowerUnit->name : " ";
					$item[$iKey]->LowerUnitItemUPC = $i->lower_unit_item_upc;
					$item[$iKey]->LowerUnitItemPrice = $i->lower_unit_item_price;
					$item[$iKey]->VatPercentage			= $i->item_vat_percentage;
					$item[$iKey]->Excise = $i->item_excise;
					$item[$iKey]->StockKeepingUnit			= $i->stock_keeping_unit;
					$item[$iKey]->Volume = $i->volume;
					$item[$iKey]->SupervistorCategory = (is_object($i->supervisorCategory)) ? $i->supervisorCategory->name : " ";
					$item[$iKey]->ItemMajorCategory = (is_object($i->itemMajorCategory)) ? $i->itemMajorCategory->name : " ";
					$item[$iKey]->ItemGroup = (is_object($i->itemGroup)) ? $i->itemGroup->name : " ";
					$item[$iKey]->Brands = (is_object($i->brand)) ? $i->brand->brand_name : " ";
					$item[$iKey]->LOB = (is_object($i->lob)) ? $i->lob->name : " ";
					$item[$iKey]->ItemStatus = (isset($i->status)) ? "Yes" : "No";
					$item[$iKey]->ItemImage	= (isset($i->item_image)) ? $i->item_image : " ";
					$item[$iKey]->Promotional = (isset($i->is_promotional)) ? "Yes" : "No";
					$item[$iKey]->ProductCatalog = (isset($i->is_product_catalog)) ? "Yes" : "No";

					$item[$iKey]->SecondaryUOMPrice = (is_object($main)) ? $main->item_price : " ";
					$item[$iKey]->SecondaryItemUPC = (is_object($main)) ? $main->item_upc : " ";
					$item[$iKey]->SecondaryUOM = (is_object($main->itemUom)) ? $main->itemUom->name : " ";
					$item[$iKey]->SecondaryPurchaseOrderPrice = (isset($main->purchase_order_price)) ? $main->purchase_order_price : " ";
					$item[$iKey]->SecondaryStockKeepingUnit = (isset($main->stock_keeping_unit)) ? $main->stock_keeping_unit : " ";

					// product catalog
					$item[$iKey]->NetWeight = (empty($i->productCatalog)) ? $i->productCatalog->net_weight : " ";
					$item[$iKey]->Flawer = (empty($i->productCatalog)) ? $i->productCatalog->flawer : " ";
					$item[$iKey]->ShelfFile = (empty($i->productCatalog)) ? $i->productCatalog->shelf_file : " ";
					$item[$iKey]->Ingredients = (empty($i->productCatalog)) ? $i->productCatalog->ingredients : " ";
					$item[$iKey]->Energy = (empty($i->productCatalog)) ? $i->productCatalog->energy : " ";
					$item[$iKey]->Fat = (empty($i->productCatalog)) ? $i->productCatalog->fat : " ";
					$item[$iKey]->Protein = (empty($i->productCatalog)) ? $i->productCatalog->protein : " ";
					$item[$iKey]->Carbohydrate = (empty($i->productCatalog)) ? $i->productCatalog->carbohydrate : " ";
					$item[$iKey]->Calcium = (empty($i->productCatalog)) ? $i->productCatalog->calcium : " ";
					$item[$iKey]->Sodium = (empty($i->productCatalog)) ? $i->productCatalog->sodium : " ";
					$item[$iKey]->Potassium = (empty($i->productCatalog)) ? $i->productCatalog->potassium : " ";
					$item[$iKey]->CrudeFibre = (empty($i->productCatalog)) ? $i->productCatalog->crude_fibre : " ";
					$item[$iKey]->Vitamin = (empty($i->productCatalog)) ? $i->productCatalog->vitamin : " ";
					$item[$iKey]->CatalogImage = (empty($i->productCatalog)) ? $i->productCatalog->image_string : " ";
				}
			} else {
				$item[$iKey]->Name = $i->item_name;
				$item[$iKey]->Code = $i->item_code;
				$item[$iKey]->ERPCode = $i->erp_code;
				$item[$iKey]->Description = $i->item_description;
				$item[$iKey]->Barcode = $i->item_barcode;
				$item[$iKey]->Weight = $i->item_weight;
				$item[$iKey]->ShelfPrice = $i->item_shelf_life;
				$item[$iKey]->TaxApply = $i->is_tax_apply;
				$item[$iKey]->ItemUom = (is_object($i->itemUomLowerUnit)) ? $i->itemUomLowerUnit->name : " ";
				$item[$iKey]->LowerUnitItemUPC = $i->lower_unit_item_upc;
				$item[$iKey]->LowerUnitItemPrice = $i->lower_unit_item_price;
				$item[$iKey]->VatPercentage			= $i->item_vat_percentage;
				$item[$iKey]->Excise = $i->item_excise;
				$item[$iKey]->StockKeepingUnit			= $i->stock_keeping_unit;
				$item[$iKey]->Volume = $i->volume;
				$item[$iKey]->SupervistorCategory = (is_object($i->supervisorCategory)) ? $i->supervisorCategory->name : " ";
				$item[$iKey]->ItemMajorCategory = (is_object($i->itemMajorCategory)) ? $i->itemMajorCategory->name : " ";
				$item[$iKey]->ItemGroup = (is_object($i->itemGroup)) ? $i->itemGroup->name : " ";
				$item[$iKey]->Brands = (is_object($i->brand)) ? $i->brand->brand_name : " ";
				$item[$iKey]->LOB = (is_object($i->lob)) ? $i->lob->name : " ";

				$item[$iKey]->SecondaryUOMPrice = "";
				$item[$iKey]->SecondaryItemUPC = "";
				$item[$iKey]->SecondaryUOM = "";
				$item[$iKey]->SecondaryPurchaseOrderPrice = "";
				$item[$iKey]->SecondaryStockKeepingUnit = "";

				$item[$iKey]->ItemStatus = (isset($i->status)) ? "Yes" : "No";
				$item[$iKey]->ItemImage	= (isset($i->item_image)) ? $i->item_image : " ";
				$item[$iKey]->Promotional = (isset($i->is_promotional)) ? "Yes" : "No";
				$item[$iKey]->ProductCatalog = (isset($i->is_product_catalog)) ? "Yes" : "No";

				$item[$iKey]->NetWeight = (empty($i->productCatalog)) ? $i->productCatalog->net_weight : " ";
				$item[$iKey]->Flawer = (empty($i->productCatalog)) ? $i->productCatalog->flawer : " ";
				$item[$iKey]->ShelfFile = (empty($i->productCatalog)) ? $i->productCatalog->shelf_file : " ";
				$item[$iKey]->Ingredients = (empty($i->productCatalog)) ? $i->productCatalog->ingredients : " ";
				$item[$iKey]->Energy = (empty($i->productCatalog)) ? $i->productCatalog->energy : " ";
				$item[$iKey]->Fat = (empty($i->productCatalog)) ? $i->productCatalog->fat : " ";
				$item[$iKey]->Protein = (empty($i->productCatalog)) ? $i->productCatalog->protein : " ";
				$item[$iKey]->Carbohydrate = (empty($i->productCatalog)) ? $i->productCatalog->carbohydrate : " ";
				$item[$iKey]->Calcium = (empty($i->productCatalog)) ? $i->productCatalog->calcium : " ";
				$item[$iKey]->Sodium = (empty($i->productCatalog)) ? $i->productCatalog->sodium : " ";
				$item[$iKey]->Potassium = (empty($i->productCatalog)) ? $i->productCatalog->potassium : " ";
				$item[$iKey]->CrudeFibre = (empty($i->productCatalog)) ? $i->productCatalog->crude_fibre : " ";
				$item[$iKey]->Vitamin = (empty($i->productCatalog)) ? $i->productCatalog->vitamin : " ";
				$item[$iKey]->CatalogImage = (empty($i->productCatalog)) ? $i->productCatalog->image_string : " ";
			}


			unset($item[$iKey]->id);
			unset($item[$iKey]->item_major_category_id);
			unset($item[$iKey]->item_group_id);
			unset($item[$iKey]->brand_id);
			unset($item[$iKey]->item_code);
			unset($item[$iKey]->erp_code);
			unset($item[$iKey]->item_name);
			unset($item[$iKey]->item_description);
			unset($item[$iKey]->item_barcode);
			unset($item[$iKey]->item_weight);
			unset($item[$iKey]->item_shelf_life);
			unset($item[$iKey]->lower_unit_item_upc);
			unset($item[$iKey]->lower_unit_uom_id);
			unset($item[$iKey]->lower_unit_item_price);
			unset($item[$iKey]->is_tax_apply);
			unset($item[$iKey]->item_vat_percentage);
			unset($item[$iKey]->item_excise);
			unset($item[$iKey]->current_stage);
			unset($item[$iKey]->current_stage_comment);
			unset($item[$iKey]->status);
			unset($item[$iKey]->stock_keeping_unit);
			unset($item[$iKey]->volume);
			unset($item[$iKey]->lob_id);
			unset($item[$iKey]->supervisor_category_id);
		}

		// $item = DB::table('items')
		// 	->join('item_major_categories', 'item_major_categories.id', '=', 'items.item_major_category_id')
		// 	->join('item_groups', 'item_groups.id', '=', 'items.item_group_id')
		// 	->join('brands', 'brands.id', '=', 'items.brand_id')
		// 	->join('item_uoms', 'item_uoms.id', '=', 'items.lower_unit_uom_id')
		// 	->join('item_main_prices', 'item_main_prices.item_id', '=', 'items.id')
		// 	->select(
		// 		'items.item_code',
		// 		'items.item_name',
		// 		'items.item_description',
		// 		'items.item_barcode',
		// 		'items.item_weight',
		// 		'items.item_shelf_life',
		// 		'items.is_tax_apply',
		// 		'items.lower_unit_item_upc',
		// 		'items.lower_unit_item_price',
		// 		'items.item_vat_percentage',
		// 		'items.stock_keeping_unit',
		// 		'items.item_excise',
		// 		'items.current_stage',
		// 		'items.status',
		// 		'item_major_categories.name as item_major_category',
		// 		'item_groups.name as item_group',
		// 		'brands.brand_name',
		// 		'item_uoms.name as item_uom',
		// 		'item_main_prices.item_upc',
		// 		'item_main_prices.item_uom_id',
		// 		'item_main_prices.item_price',
		// 		'item_main_prices.stock_keeping_unit as item_stock_keeping_unit'
		// 	);

		// if ($start_date != '' && $end_date != '') {
		// 	$item = $item->whereBetween('items.created_at', [$start_date, $end_date]);
		// }

		// $item = $item->where('items.organisation_id', auth()->user()->organisation_id);

		// $item = $item->get();

		// if (is_object($item)) {
		// 	foreach ($item as $key => $itm) {
		// 		$ItemUom1 = ItemUom::find($itm->item_uom_id);
		// 		$item[$key]->item_uom_id = (is_object($ItemUom1)) ? $ItemUom1->name : '';
		// 	}
		// }

		return $item;
	}

	public function headings(): array
	{
		return [
			'Name',
			'Code',
			'ERPCode',
			'Description',
			'Barcode',
			'Weight',
			'ShelfPrice',
			'TaxApply',
			'ItemUom',
			'LowerUnitItemUPC',
			'LowerUnitItemPrice',
			'VatPercentage',
			'Excise',
			'StockKeepingUnit',
			'Volume',
			'SupervistorCategory',
			'ItemMajorCategory',
			'ItemGroup',
			'Brands',
			'LOB',
			'ItemStatus',
			'ItemImage',
			'Promotional',
			'ProductCatalog',
			'SecondaryUOMPrice',
			'SecondaryItemUPC',
			'SecondaryUOM',
			'SecondaryPurchaseOrderPrice',
			'SecondaryStockKeepingUnit',
			'NetWeight',
			'Flawer',
			'ShelfFile',
			'Ingredients',
			'Energy',
			'Fat',
			'Protein',
			'Carbohydrate',
			'Calcium',
			'Sodium',
			'Potassium',
			'CrudeFibre',
			'Vitamin',
			'CatalogImage'
		];
	}
}
