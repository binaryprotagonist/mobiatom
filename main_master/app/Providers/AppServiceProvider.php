<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use App\Model\CustomerInfo;
use App\Observers\CustomerInfoObserver;
use App\Model\Item;
use App\Observers\ItemObserver;
use App\Model\SalesmanInfo;
use App\Observers\SalesmanInfoObserver;
use App\Model\JourneyPlan;
use App\Observers\JourneyPlanObserver;
use App\Model\Order;
use App\Observers\OrderObserver;
use App\Model\Delivery;
use App\Observers\DeliveryObserver;
use App\Model\Invoice;
use App\Observers\InvoiceObserver;
use App\Model\Collection;
use App\Observers\CollectionObserver;
use App\Model\CreditNote;
use App\Observers\CreditNoteObserver;
use App\Model\Goodreceiptnote;
use App\Observers\GoodreceiptnoteObserver;
use App\Model\Warehouse;
use App\Observers\WarehouseObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // $this->bootApplication();

        $headers = request()->headers->all();
        if (isset($headers['x-domain'])) {
            $sub = explode('.', $headers['x-domain'][0]);
            config(['app.current_domain' => $sub[0]]);
        }

        Schema::defaultStringLength(191);

        // CustomerInfo::observe(CustomerInfoObserver::class);
        // Item::observe(ItemObserver::class);
        // SalesmanInfo::observe(SalesmanInfoObserver::class);
        // JourneyPlan::observe(JourneyPlanObserver::class);
        // Order::observe(OrderObserver::class);
        // Delivery::observe(DeliveryObserver::class);
        // Invoice::observe(InvoiceObserver::class);
        // Collection::observe(CollectionObserver::class);
        // CreditNote::observe(CreditNoteObserver::class);
        // Warehouse::observe(WarehouseObserver::class);
        // Goodreceiptnote::observe(GoodreceiptnoteObserver::class);

        $this->timezone();
    }

    private function timezone()
    {
        if (is_object(request()->user())) {
            if (is_object(request()->user()->organisation->countryInfo)) {
                $countryCode = request()->user()->organisation->countryInfo->country_code;
                $timezone = \DateTimeZone::listIdentifiers(\DateTimeZone::PER_COUNTRY, $countryCode);
                config(['app.timezone' => $timezone[0]]);
                date_default_timezone_set($timezone[0]);
            }
        }
    }
}
