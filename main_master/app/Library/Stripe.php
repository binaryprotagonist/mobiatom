<?php

namespace App\Library;

use Illuminate\Http\Request;
use Illuminate\Foundation\Bus\DispatchesJobs;

class Stripe
{
  use DispatchesJobs;

  public $card_number = null;

  public $card_expiry = null;

  public $card_cvv = null;

  public $api_key;

  public $published_key;

  public $id_stripe;

  protected $default_card;

  // public $country = 'USA';
  //
  // public $currency = 'usd';

  public $profile_description = 'Mobiato Solutions';

  // public $id_customer;

  public function __construct($id_stripe = null)
  {
    // $this->context = new \App\Classes\Context;
    $this->api_key = env('STRIPE_SECRET');
    $this->published_key = env('STRIPE_KEY');
    \Stripe\Stripe::setApiKey($this->api_key);
    if ($id_stripe) {
      $this->id_stripe = $id_stripe;
      $this->customer = \Stripe\Customer::retrieve($id_stripe);
    }
  }

  /*
    | Generate a Card Token
    | $data is a array passing the card details (i.e. card_number, month)
    | and it will be generate a a Token
    |
     */

  public function set_stripe_token($data)
  {
    $exp = explode('-', $data['__ce']);
    $exp_year = trim($exp[0]);
    $exp_month = trim($exp[1]);
    return \Stripe\Token::create(array(
      'card' => array(
        'number' => (string) str_replace(' ', '', $data['__cc']),
        'exp_month' => $exp_month,
        'exp_year' => $exp_year,
        'cvc' => $data['__cvc'],
        'name' => $data['name'],
      )
    ));
  }

  public function set_stripe_token_address($data)
  {
    if (is_numeric(str_replace('/', '', $data->card_month))) {
      $card_date = str_replace('/', '', $data->card_month);
      $exp_month = substr($card_date, 0, 2);
      $exp_year =  '20' . substr($card_date, 2, 3);
      $cae = $exp_year . '-' . $exp_month;
    } else {
      return 'card expiry is not numeric';
    }

    if (isset($data->iaddress1)) {
      $address1 = $data->iaddress1;
      if (isset($data->iaddress2)) {
        $address2 = $data->iaddress2;
      } else {
        $address2 = '';
      }
      $city = $data->icity;
      $postcode = $data->izip;
    }

    if (is_numeric($data->istate)) {
    //   $s = $this->context->states->find($data->istate);
      $state = $s->name;
    } else {
      $state = $data->istate;
    }

    try {
      return \Stripe\Token::create(array(
        'card' => array(
          'name' => (string) $data->ifirst_name . ' ' .  $data->ilast_name,
          'number' => (string) ps($data->number),
          'exp_month' => $exp_month,
          'exp_year' => $exp_year,
          'cvc' => $data->card_cvv,
          'address_line1' => $address1,
          'address_line2' => $address2,
          'address_city' => $city,
          'address_state' => $state,
          'address_zip' => $postcode,
          'address_country' => 'USA'
        )
      ));
    } catch (\Stripe\Error\Card $e) {
      $body = $e->getJsonBody();
      $err  = $body['error'];
      return array(
        'status' => 'error',
        'message' => $err['message']
      );
    } catch (Exception $e) {
      $body = $e->getJsonBody();
      $err  = $body['error'];
      return array(
        'status' => 'error',
        'message' => $err['message']
      );
    }
  }

  /*
    | This function is create a new stripe Customer
    | in Stripe all the data working via customer
    | Passing two arugument
    | @token is Token Generate
    | @user give the current user object
    |
     */

  public function set_stripe_customer($token, $email, $card_data = null)
  {
    try {
      $address2 = null;
      if (isset($card_data->iaddress2) && $card_data->iaddress2) {
        $address2 = $card_data->iaddress2;
      }
      return \Stripe\Customer::create(array(
        'source'   => $token,
        'email'    => $email,
        'name' => $card_data->ifirst_name . ' ' . $card_data->ilast_name,
        'address' => array(
          'city' => $card_data->icity,
          'state' => $card_data->istate,
          'line1' => $card_data->iaddress1,
          'line2' => $address2,
          'country' => 'US',
          'postal_code' => $card_data->izip
        )
      ));
    } catch (\Stripe\Error\Customer $e) {
      $body = $e->getJsonBody();
      $err  = $body['error'];
      return array(
        'status' => 'error',
        'message' => $err['message']
      );
    } catch (Exception $e) {
      $body = $e->getJsonBody();
      $err  = $body['error'];
      return array(
        'status' => 'error',
        'message' => $err['message']
      );
    }
  }

  public function getCharge($id_charge)
  {
    return \Stripe\Charge::retrieve($id_charge);
  }

  /*
    | This function is create a new customer and Charge card
    | there are passing three arugument
    | @card Passing the Charge Card
    | @amount passing the Charge Amount
    | @description
    | Make this card default
     */
  public function chargeCard($card, $amount, $description = null, $email = null)
  {
    $token = \Stripe\Token::create([
      'card' => $card,
      'type' => 'card'
    ]);

    try {
      return \Stripe\Charge::create(array(
        'amount' => $amount,
        'currency' => $this->currency,
        'description' => $description,
        'receipt_email' => $email,
        'token' => $token
      ));
    } catch (\Stripe\Error\Card $e) {
      $body = $e->getJsonBody();
      $err  = $body['error'];
      return $err['message'];
    } catch (\Stripe\Error\Base $e) {
      $body = $e->getJsonBody();
      $err  = $body['error'];
      return array(
        'status' => 'error',
        'message' => 'Invalid card data'
      );
    } catch (Exception $e) {
      return $e;
      return 'There is some invalide card data';
    }
  }

  public function chargeDefaultCard($amount, $description = null, $email = null)
  {
    $token = \Stripe\Token::create([
      'card' => $this->customer->default_source
    ]);

    return \Stripe\Charge::create(array(
      'amount' => $amount,
      'currency' => $this->currency,
      'description' => $description,
      'receipt_email' => $email,
      'token' => $token
    ));
  }

  public function getInvoice($id)
  {
    return \Stripe\Invoice::retrieve($id);
  }

  public function getReceiptURL($id)
  {
    $invoice = $this->getInvoice($id);
    if ($invoice && $invoice->charge) {
      $charge = $this->getCharge($invoice->charge);
      if ($charge && $charge->receipt_url) {
        return $charge->receipt_url;
      }
    }

    return null;
  }

  public function createInvoice($id)
  {
    return \Stripe\Invoice::create([
      "customer" => $id
    ]);
  }

  /*
    | This function is Charge the Customer
    | Arugument @amount is price to chage
    | @description anything descibe
    | @customer_profile_id is Stripe customer prifile id
    |
     */

  public function chargeCustomerCard($amount, $description, $customer_profile_id, $email = null)
  {
    try {
      return \Stripe\Charge::create(array(
        'amount' => $amount, // $15.00 this time
        'currency' => $this->currency,
        'customer' => $customer_profile_id,
        'receipt_email' => $email,
        'description' => $description
      ));
    } catch (\Stripe\Error\Base $e) {
      $body = $e->getJsonBody();
      $err  = $body['error'];
      return array(
        'status' => 'error',
        'message' => 'Invalid card data'
      );
    } catch (\Stripe\Error\Card $e) {
      $body = $e->getJsonBody();
      $err  = $body['error'];
      return $err['message'];
    } catch (Exception $e) {
      return $e;
      return 'There is some invalide card data';
    }
  }

  public function addNewCustomer($card_data, $user)
  {
    if (isset($user->id_stripe) && $user->id_stripe) {
      $this->id_stripe = $user->id_stripe;
    }
    return $this->setNewCard($card_data, $user);
  }

  /*
    | This Function is Add a new Card
    | Pass the card data
    |
     */

  public function setNewCard($card_data, $customer, $id_stripe = null)
  {
    if ($id_stripe) {
      $id_stripe = $id_stripe;
    } else {
      $id_stripe = $this->id_stripe;
    }

    try {
      if (!$id_stripe) {
        $token = $this->set_stripe_token_address($card_data);
        if ($token['status'] == 'error') {
          return array(
            'status' => 'error',
            'message' => $token['message']
          );
        }

        $customer_retrieve = $this->set_stripe_customer($token, $customer->email, $card_data);

        if (isset($customer_retrieve->id) && $customer_retrieve->id) {
          $customer->id_stripe = $customer_retrieve->id;
          $customer->save();
          $card = $this->getCustomerCard($customer_retrieve->id, $customer_retrieve->default_source);
        } else {
          return array(
            'status' => 'error',
            'message' => 'Customer record could not be created'
          );
        }
      } else {
        $token = $this->set_stripe_token_address($card_data);
        $this->customer = \Stripe\Customer::retrieve($id_stripe);
        $card = $this->customer->sources->create([
          'source' => $token,
        ]);
        $this->setDefaultCard($card->id);
      }
    } catch (\Stripe\Error\Card $e) {
      $body = $e->getJsonBody();
      $err  = $body['error'];
      return array(
        'status' => 'error',
        'message' => $err['message']
      );
    } catch (Exception $e) {
      $body = $e->getJsonBody();
      $err  = $body['error'];
      return array(
        'status' => 'error',
        'message' => $err['message']
      );
    }

    if (isset($card)) {
      return $card;
    }
  }

  /*
    | This Function is Get All Customer Card
    |
     */

  public function getAllCard()
  {
    return \Stripe\Customer::retrieve($this->id_stripe)->sources->all([
      'object' => 'card'
    ]);
  }

  /*
    | This Function is Get a Customer Find card Details
    | @card data arugument is pass in the function
     */

  public function getCard($card)
  {
    return $this->customer->sources->retrieve($card);
  }

  /*
    | This Function is Delete the card
    | @card data arugument is pass in the function
     */

  public function deleteCard($card)
  {
    try {
      $default_card = $this->getDefaultCard();

      if (
        (isset($default_card->id) && $default_card->id) &&
        ($card == $default_card->id)
      ) {
        return array(
          'status' => 'error',
          'message' => 'Default card cannot be deleted'
        );
      }
      return $this->customer->sources->retrieve($card)->delete();
    } catch (\Stripe\Error\Card $e) {
      $body = $e->getJsonBody();
      $err  = $body['error'];
      return array(
        'status' => 'error',
        'message' => $err['message']
      );
    } catch (\Stripe\Error\Base $e) {
      $body = $e->getJsonBody();
      $err  = $body['error'];
      return array(
        'status' => 'error',
        'message' => 'Invalid card data'
      );
    } catch (Exception $e) {
      $body = $e->getJsonBody();
      $err  = $body['error'];
      return array(
        'status' => 'error',
        'message' => $err['message']
      );
    }
  }

  /*
    | Get Customer Default card
    */

  public function getDefaultCard()
  {
    return $this->customer->sources->retrieve($this->customer->default_source);
  }

  public function getRetrieveCustomer($id_stripe)
  {
    return $this->customer = \Stripe\Customer::retrieve($id_stripe);
  }

  public function getRetrieveToken($token)
  {
    return \Stripe\Token::retrieve($token);
  }

  public function getAllCharges($limit = 10)
  {
    return \Stripe\Charge::all([
      'limit' => $limit
    ]);
  }

  public function setCustomer($id_stripe)
  {
    $this->id_stripe = $id_stripe;
    $this->customer = \Stripe\Customer::retrieve($id_stripe);
  }

  public function setCustomerRefund($charge)
  {
    return \Stripe\Refund::create([
      'charge' => $charge
    ]);
  }

  public function partialRefund($id_charge, $amount)
  {
    $amount = ceil($amount);
    return \Stripe\Refund::create([
      "charge" => $id_charge,
      'amount' => $amount
    ]);
  }

  public function getCustomerCharge($id_charge)
  {
    return \Stripe\Charge::retrieve($id_charge);
  }

  public function getBalance()
  {
    return \Stripe\Balance::retrieve();
  }

  public function setDefaultCard($card_id)
  {
    $customer = $this->customer;
    $customer->default_source = $card_id;
    $customer->save();
  }

  public function create_subscription($data)
  {
    $data = (object) $data;
    $id = str_slug($data->name);
    $amount = $data->price * 100;
    return \Stripe\Plan::create(array(
      'amount' => $amount,
      'interval' => 'month',
      'product' => array(
        'name' => $data->name
      ),
      'currency' => 'usd',
      // 'id' => $id,
      'interval_count' => $data->duration,
      'nickname' => $data->name
    ));
  }

  public function subscribe($plan)
  {
    try {
      return \Stripe\Subscription::create(array(
        'customer' => $this->customer->id,
        'items' => array(
          array(
            'plan' => $plan,
          ),
        ),
      ));
    } catch (\Stripe\Error\Base $e) {
      $body = $e->getJsonBody();
      $err  = $body['error'];
      return array(
        'status' => 'error',
        'message' => $err['message']
      );
    } catch (Exception $e) {
      $body = $e->getJsonBody();
      $err  = $body['error'];
      return array(
        'status' => 'error',
        'message' => $err['message']
      );
    }
  }

  public function retrieveSubscription($sub)
  {
    try {
      return \Stripe\Subscription::retrieve($sub);
    } catch (\Stripe\Error\Base $e) {
      $body = $e->getJsonBody();
      $err  = $body['error'];
      return array(
        'status' => 'error',
        'message' => $err['message']
      );
    } catch (Exception $e) {
      $body = $e->getJsonBody();
      $err  = $body['error'];
      return array(
        'status' => 'error',
        'message' => $err['message']
      );
    }
  }

  public function cancelSubscription($sub)
  {
    $sub = \Stripe\Subscription::retrieve($sub);
    $sub->cancel();
    return $sub;
  }

  //updateSubscription function
  // Arugument $sub_id (Subscription id)

  public function updateSubscription($sub_id)
  {
    try {
      return \Stripe\Subscription::update($sub_id);
    } catch (\Stripe\Error\Base $e) {
      $body = $e->getJsonBody();
      $err  = $body['error'];
      return array(
        'status' => 'error',
        'message' => $err['message']
      );
    } catch (Exception $e) {
      $body = $e->getJsonBody();
      $err  = $body['error'];
      return array(
        'status' => 'error',
        'message' => $err['message']
      );
    }
  }

  public function getPlan($id)
  {
    try {
      return \Stripe\Plan::retrieve($id);
    } catch (\Stripe\Error\Base $e) {
      $body = $e->getJsonBody();
      $err  = $body['error'];
      return array(
        'status' => 'error',
        'message' => $err['message']
      );
    } catch (Exception $e) {
      $body = $e->getJsonBody();
      $err  = $body['error'];
      return array(
        'status' => 'error',
        'message' => $err['message']
      );
    }
  }

  public function getSubscription($id)
  {
    return \Stripe\Subscription::retrieve($id);
  }

  public function getTrans($id)
  {
    return \Stripe\Issuing\Transaction::retrieve($id);
  }

  public function getCustomerCard($customer, $card)
  {
    return \Stripe\Customer::retrieveSource($customer, $card);
  }
}
