<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Traits\Organisationid;
use App\Model\Organisation;
use App\Model\CombinationPlanKey;
use App\Model\PDPCountry;
use App\Model\PDPRegion;
use App\Model\PDPArea;
use App\Model\PDPRoute;
use App\Model\PDPSalesOrganisation;
use App\Model\PDPChannel;
use App\Model\PDPCustomerCategory;
use App\Model\PDPCustomer;
use App\Model\PDPItemMajorCategory;
use App\Model\PDPItemGroup;
use App\Model\PDPItem;
use App\Model\PDPPromotionItem;
use App\Model\PDPPromotionOfferItem;
use App\Model\OrderDetail;
use App\Model\DeliveryDetail;
use App\Model\InvoiceDetail;
use App\Model\CreditNoteDetail;
use App\Model\DebitNoteDetail;
use App\Model\PDPDiscountSlab;

class PriceDiscoPromoPlan extends Model
{
    use SoftDeletes, LogsActivity, Organisationid;

    protected $fillable = [
        'uuid', 'organisation_id', 'route_id', 'is_merchandiser', 'merchandiser_id', 'use_for', 'combination_plan_key_id', 'name', 'start_date', 'end_date', 'combination_key_value',
        //For Promotion
        'order_item_type', 'offer_item_type',
        //For Discount 
        'type', 'qty_from', 'qty_to', 'discount_value', 'discount_percentage', 'discount_apply_on', 'discount_type', 'is_enforce',

        'priority_sequence', 'status'
    ];

    protected static $logAttributes = ['*'];

    protected static $logOnlyDirty = false;

    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->uuid = (string) \Uuid::generate();
        });
    }

    public function organisation()
    {
        return $this->belongsTo(Organisation::class,  'organisation_id', 'id');
    }

    public function combinationPlanKeyPricing()
    {
        return $this->belongsTo(CombinationPlanKey::class,  'combination_plan_key_id', 'id')->where('use_for', 'Pricing');
    }

    public function combinationPlanKeyPricingPlain()
    {
        return $this->belongsTo(CombinationPlanKey::class,  'combination_plan_key_id', 'id');
    }

    public function combinationPlanKeyDiscount()
    {
        return $this->belongsTo(CombinationPlanKey::class,  'combination_plan_key_id', 'id')->where('use_for', 'Discount');
    }

    public function combinationPlanKeyPromotion()
    {
        return $this->belongsTo(CombinationPlanKey::class,  'combination_plan_key_id', 'id')->where('use_for', 'Promotion');
    }

    public function PDPCountries()
    {
        return $this->hasMany(PDPCountry::class,  'price_disco_promo_plan_id', 'id');
    }

    public function PDPRegions()
    {
        return $this->hasMany(PDPRegion::class,  'price_disco_promo_plan_id', 'id');
    }

    public function PDPAreas()
    {
        return $this->hasMany(PDPArea::class,  'price_disco_promo_plan_id', 'id');
    }

    public function PDPRoutes()
    {
        return $this->hasMany(PDPRoute::class,  'price_disco_promo_plan_id', 'id');
    }

    public function PDPSalesOrganisations()
    {
        return $this->hasMany(PDPSalesOrganisation::class,  'price_disco_promo_plan_id', 'id');
    }

    public function PDPChannels()
    {
        return $this->hasMany(PDPChannel::class,  'price_disco_promo_plan_id', 'id');
    }

    public function PDPCustomerCategories()
    {
        return $this->hasMany(PDPCustomerCategory::class,  'price_disco_promo_plan_id', 'id');
    }

    public function PDPCustomers()
    {
        return $this->hasMany(PDPCustomer::class,  'price_disco_promo_plan_id', 'id');
    }

    public function PDPItemMajorCategories()
    {
        return $this->hasMany(PDPItemMajorCategory::class,  'price_disco_promo_plan_id', 'id');
    }

    public function PDPItemGroups()
    {
        return $this->hasMany(PDPItemGroup::class,  'price_disco_promo_plan_id', 'id');
    }

    public function PDPItems()
    {
        return $this->hasMany(PDPItem::class,  'price_disco_promo_plan_id', 'id');
    }

    public function PDPPromotionItems()
    {
        return $this->hasMany(PDPPromotionItem::class,  'price_disco_promo_plan_id', 'id');
    }

    public function PDPPromotionOfferItems()
    {
        return $this->hasMany(PDPPromotionOfferItem::class,  'price_disco_promo_plan_id', 'id');
    }

    public function PDPDiscountSlabs()
    {
        return $this->hasMany(PDPDiscountSlab::class,  'price_disco_promo_plan_id', 'id');
    }

    public function discountOrderDetails()
    {
        return $this->hasMany(OrderDetail::class,  'discount_id', 'id');
    }

    public function promotionOrderDetails()
    {
        return $this->hasMany(OrderDetail::class,  'promotion_id', 'id');
    }

    public function discountDeliveryDetails()
    {
        return $this->hasMany(DeliveryDetail::class,  'discount_id', 'id');
    }

    public function promotionDeliveryDetails()
    {
        return $this->hasMany(DeliveryDetail::class,  'promotion_id', 'id');
    }

    public function discountInvoiceDetails()
    {
        return $this->hasMany(InvoiceDetail::class,  'discount_id', 'id');
    }

    public function promotionInvoiceDetails()
    {
        return $this->hasMany(InvoiceDetail::class,  'promotion_id', 'id');
    }

    public function discountCreditNoteDetails()
    {
        return $this->hasMany(CreditNoteDetail::class,  'discount_id', 'id');
    }

    public function promotionCreditNoteDetails()
    {
        return $this->hasMany(CreditNoteDetail::class,  'promotion_id', 'id');
    }

    public function discountDebitNoteDetails()
    {
        return $this->hasMany(DebitNoteDetail::class,  'discount_id', 'id');
    }

    public function promotionDebitNoteDetails()
    {
        return $this->hasMany(DebitNoteDetail::class,  'promotion_id', 'id');
    }
}
