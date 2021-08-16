<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\Offer;
use App\Model\Organisation;
use App\Model\Plan;
use App\Model\PlanHistory;
use App\Model\PlanInvoice;
use App\Model\Software;
use App\Model\Subscription;
use App\Model\UserLoginTrack;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function store(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "add");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating subscription", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {

            $software = Software::where('slug', config('app.current_domain'))->first();

            $offer = Offer::find($request->offer_id);
            $plan = Plan::find($request->plan_id);

            $plan_end_date = date('Y-m-d', strtotime("+".$plan->durations." months", strtotime(date('Y-m-d'))));

            $subscription = new Subscription;
            $subscription->software_id = $software->id;
            $subscription->plan_id = $request->plan_id;
            $subscription->offer_id = $request->offer_id;
            $subscription->trial_period_start_date = date('Y-m-d');
            $subscription->subscribe_after_trial = $request->subscribe_after_trial;
            $subscription->subscribe_after_trial = 0;
            if (is_object($offer)) {
                $subscription->offer_start_date = $offer->offer_start_date;
                $subscription->offer_end_date = $request->offer_end_date;
            }
            $subscription->valid_to = $plan_end_date; // 1 years
            $subscription->save();

            $plan_history = new PlanHistory;
            $plan_history->subscription_id = $subscription->id;
            $plan_history->plan_id = $subscription->plan_id;
            $plan_history->date_start = date('Y-m-d');
            $plan_history->date_end = $plan_end_date; // 1 Years
            $plan_history->save();

            $plan_invoice = new PlanInvoice;
            $plan_invoice->subscription_id = $subscription->id;
            $plan_invoice->plan_history_id = $plan_history->id;
            $plan_invoice->start_date = date('Y-m-d');
            $plan_invoice->end_date = $plan_end_date; // 1 Years
            $plan_invoice->description = $request->description;
            $amount = $plan->current_price;
            if (is_object($offer)) {
                if ($offer->discount_amount) {
                    $amount = $amount - $offer->discount_amount;
                }
                if ($offer->discount_percentage) {
                    $amount = $amount / $offer->discount_percentage * 100;
                }
                $plan_invoice->amount = $amount; // if any discount
            } else {
                $plan_invoice->amount = $amount;  // if not discount
            }
            $plan_invoice->paid_date = date('Y-m-d');
            $plan_invoice->due_date = $plan_end_date; // 1 years
            $plan_invoice->save();

            \DB::commit();

            $org = Organisation::find($request->user()->organisation_id);
            return prepareResult(true, $subscription, [], "Plan subscribe successfully", $this->success);
        } catch (\Exception $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        } catch (\Throwable $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        }
    }

    public function planUnsubscribed(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "unsub");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating subscription", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {

            $subscription = Subscription::where('id', $request->id)->first();
            $subscription->date_unsubscribed = date('Y-m-d');
            $subscription->save();
            
            // $subscription = \Stripe\Subscription::retrieve($subscription->stripe_subscription_id);
            // $subscription->cancel();

            \DB::commit();
            return prepareResult(true, $subscription, [], "Plan unsubscribe successfully", $this->success);
        } catch (\Exception $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        } catch (\Throwable $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        }
    }

    public function updateSubscription(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        $input = $request->json()->all();
        $validate = $this->validations($input, "updateSub");
        if ($validate["error"]) {
            return prepareResult(false, [], $validate['errors']->first(), "Error while validating subscription", $this->unprocessableEntity);
        }

        \DB::beginTransaction();
        try {

            $software = Software::where('slug', config('app.currenct_domain'))->first();

            $offer = Offer::find($request->offer_id);
            $plan = Plan::find($request->plan_id);

            $plan_end_date = date('Y-m-d', strtotime("+".$plan->durations." months", strtotime(date('Y-m-d'))));
            
            $subscription = Subscription::where('id', $request->id)->first();
            $subscription->software_id = $software->id;
            $subscription->plan_id = $request->plan_id;
            $subscription->offer_id = $request->offer_id;
            $subscription->trial_period_start_date = date('Y-m-d');
            $subscription->subscribe_after_trial = null;
            if (is_object($offer)) {
                $subscription->offer_start_date = $offer->offer_start_date;
                $subscription->offer_end_date = $request->offer_end_date;
            }
            $subscription->date_subscribed = date('Y-m-d');
            $subscription->valid_to = $plan_end_date;
            $subscription->save();

            $plan_history = new PlanHistory;
            $plan_history->subscription_id = $subscription->id;
            $plan_history->plan_id = $subscription->plan_id;
            $plan_history->date_start = date('Y-m-d');
            $plan_history->date_end = $plan_end_date;
            $plan_history->save();

            $plan_invoice = new PlanInvoice;
            $plan_invoice->subscription_id = $subscription->id;
            $plan_invoice->plan_history_id = $plan_history->id;
            $plan_invoice->start_date = date('Y-m-d');
            $plan_invoice->end_date = $plan_end_date;
            $plan_invoice->description = $request->description;
            $amount = $plan->current_price;
            if (is_object($offer)) {
                if ($offer->discount_amount) {
                    $amount = $amount - $offer->discount_amount;
                }
                if ($offer->discount_percentage) {
                    $amount = $amount / $offer->discount_percentage * 100;
                }
                $plan_invoice->amount = $amount; // if any discount
            } else {
                $plan_invoice->amount = $amount;  // if not discount
            }
            $plan_invoice->paid_date = date('Y-m-d');
            $plan_invoice->due_date = $plan_end_date;
            $plan_invoice->save();

            \DB::commit();
            return prepareResult(true, $subscription, [], "Plan subscription update successfully", $this->success);
        } catch (\Exception $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        } catch (\Throwable $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        }
    }

    public function loginDomainTrack(Request $request)
    {
        if (!$this->isAuthorized) {
            return prepareResult(false, [], [], "User not authenticate", $this->unauthorized);
        }

        \DB::beginTransaction();
        try {
            $software = Software::where('slug', config('app.current_domain'))->first();
            $user_login_track = new UserLoginTrack;
            $user_login_track->software_id = $software->id;
            $user_login_track->login_date = date('Y-m-d');
            $user_login_track->trial_expired_date = date('Y-m-d', strtotime(date('Y-m-d'). ' + 14 days'));
            $user_login_track->save();

            \DB::commit();
            return prepareResult(true, $user_login_track, [], "User login track successfully", $this->success);
        } catch (\Exception $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        } catch (\Throwable $exception) {
            \DB::rollback();
            return prepareResult(false, [], $exception->getMessage(), "Oops!!!, something went wrong, please try again.", $this->internal_server_error);
        }
    }

    private function validations($input, $type)
    {
        $errors = [];
        $error = false;
        if ($type == "add") {
            $validator = \Validator::make($input, [
                'plan_id' => 'required|integer|exists:plans,id',
            ]);
        }
        
        if ($type == "unsub") {
            $validator = \Validator::make($input, [
                'id' => 'required|integer|exists:subscription,id',
            ]);
        }
        
        if ($type == "updateSub") {
            $validator = \Validator::make($input, [
                'id' => 'required|integer|exists:subscription,id',
            ]);
        }

        if ($validator->fails()) {
            $error = true;
            $errors = $validator->errors();
        }

        return ["error" => $error, "errors" => $errors];
    }
}
