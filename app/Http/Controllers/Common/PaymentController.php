<?php

namespace App\Http\Controllers\Common;
use App\Http\Controllers\Controller;

require_once('vendor/autoload.php');

use ChargeBee\ChargeBee\Models\Customer;
use ChargeBee\ChargeBee\Models\Item;
use ChargeBee\ChargeBee\Models\ItemFamily;
use ChargeBee\ChargeBee\Models\ItemPrice;
use ChargeBee\ChargeBee\Models\Subscription;
use Illuminate\Http\Request;
use ChargeBee\ChargeBee\Environment;
class PaymentController extends Controller
{
    public function __construct(){
//        Environment::configure("manifestinfotech-test", "test_7cuzNbMkBMqDbDSGZyu0JBW9jyBKLXcdcur");
        Environment::configure("mpact-int-test", "test_PBGB6V3hcdHW8G9vRwOHUP8SmCk5XguNr");
    }
    public function create_customer(){
        $result = Customer::create(array(
            "id" => "acme-east1",
            "company" => "Acme Eastern",
            "autoCollection" => "on",
            "card" => array(
                "number" => "4111111111111111",
                "cvv" => "100",
                "expiryMonth" => 12,
                "expiryYear" => 2022
            )
        ));
        $customer = $result->customer();
        $card = $result->card();
        echo "<pre>";
        print_r($customer);
        print_r($card);
    }

    public function create_product_family(){
        $result = ItemFamily::create(array(
            "id" => "cloud-storage",
            "name" => "Cloud Storage"
        ));
        $itemFamily = $result->itemFamily();
        echo "<pre>";
        print_r($itemFamily);
    }

    public function create_plan(){
        $result = Item::create(array(
            "id" => "silver-plan",
            "name" => "Silver Plan",
            "type" => "plan",
            "item_family_id" => "cloud-storage"
        ));
        $item = $result->item();
        echo "<pre>";
        print_r($item);
    }
    public function create_item_price(){
        $result = ItemPrice::create(array(
            "id" => "silver-plan-USD-monthly",
            "itemId" => "silver-plan",
            "name" => "Silver USD monthly",
            "pricingModel" => "per_unit",
            "price" => 50000,
            "externalName" => "Silver USD",
            "periodUnit" => "month",
            "period" => 1
        ));
        $itemPrice = $result->itemPrice();
        echo "<pre>";
        print_r($itemPrice);
    }

    public function create_subscription(){
        $result = Subscription::createWithItems("acme-east",array(
            "subscriptionItems" => array(array(
                "itemPriceId" => "silver-plan-USD-monthly",
                "quantity" => 4))
        ));
        $subscription = $result->subscription();
        $customer = $result->customer();
        $card = $result->card();
        $invoice = $result->invoice();
        $unbilledCharges = $result->unbilledCharges();

        echo "<pre>";
        print_r($subscription);
        print_r($customer);
        print_r($card);
        print_r($invoice);
        print_r($unbilledCharges);
    }
}
