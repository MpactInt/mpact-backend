<?php

namespace App\Http\Controllers\Common;

use App\Http\Controllers\Controller;

require_once('vendor/autoload.php');

use App\Models\ChargebeeUser;
use App\Models\Company;
use App\Models\CompanyEmployee;
use App\Models\Invitation;
use App\Models\Plan;
use App\Models\PlanTier;
use App\Models\SubscriptionPlan;
use App\Models\User;
use ChargeBee\ChargeBee\Models\Estimate;
use ChargeBee\ChargeBee\Models\HostedPage;

//use ChargeBee\ChargeBee\Models\Plan;
use ChargeBee\ChargeBee\Models\Item;
use ChargeBee\ChargeBee\Models\ItemPrice;
use Illuminate\Http\Request;

use ChargeBee\ChargeBee\Environment;
use ChargeBee\ChargeBee\Models\Subscription;
use ChargeBee\ChargeBee\Models\Customer;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Psy\Reflection\ReflectionClassConstant;
use ReflectionClass;


class ChargebeeController extends Controller
{
    public function __construct()
    {
        //        Environment::configure("manifestinfotech-test", "test_7cuzNbMkBMqDbDSGZyu0JBW9jyBKLXcdcur");
        Environment::configure("mpact-int-test", "test_PBGB6V3hcdHW8G9vRwOHUP8SmCk5XguNr");
    }


    /**
     * @return \Illuminate\Http\JsonResponse
     */

    public function get_plans()
    {
        $itemPrice = [];
        $all = ItemPrice::all(array('status' => 'active', "itemType[is]" => "plan"));
        foreach ($all as $entry) {
            $reflection = new ReflectionClass($entry->itemPrice());
            $property = $reflection->getProperty('_data');
            $property->setAccessible(true);
            $itemPrice[] = $property->getValue($entry->itemPrice());
        }
        return response()->json(['status' => 'success', 'res' => $itemPrice], 200);
    }



    /**
     * @return \Illuminate\Http\JsonResponse
     */

    public function get_plans2()
    {
        $itemPrice = [];
        $all = ItemPrice::all(array('status' => 'active', "itemType[is]" => "plan"));
        foreach ($all as $entry) {
            $reflection = new ReflectionClass($entry->itemPrice());
            $property = $reflection->getProperty('_data');
            $property->setAccessible(true);
            $itemPrice[] = $property->getValue($entry->itemPrice());
        }

        return response()->json(['status' => 'success', 'res' => $itemPrice], 200);
    }
    /**
     * @return \Illuminate\Http\JsonResponse
     */

    public function get_addons()
    {
        $user = Auth::guard('api')->user();
        $company = Company::where('user_id', $user->id)->first();
        $itemPrice = [];
        $all = ItemPrice::all(array('status' => 'active', "itemType[is]" => "addon", "periodUnit[is]" => $company->period_unit));
        foreach ($all as $entry) {
            $reflection = new ReflectionClass($entry->itemPrice());
            $property = $reflection->getProperty('_data');
            $property->setAccessible(true);
            $itemPrice[] = $property->getValue($entry->itemPrice());
        }
        array_unshift($itemPrice, ['id' => '', 'name' => 'Select Addon']);
        return response()->json(['status' => 'success', 'res' => $itemPrice], 200);
    }

    public function select_addon($id, Request $request)
    {
        $res = HostedPage::retrieve($id);
        $reflection = new ReflectionClass($res);
        $property = $reflection->getProperty('_response');
        $property->setAccessible(true);
        $res1 = $property->getValue($res);

        $result = HostedPage::checkoutExistingForItems(
            array(
                "subscription" => array(
                    "id" => $res1['hosted_page']['content']['subscription']['id']
                ),
                "subscriptionItems" => array(array(
                    "itemPriceId" => "$request->addon",
                    "quantity" => 1
                )),
                "redirectUrl" => env('FRONT_URL') . '/employer/membership-details'
            )
        );
        $hostedPage = $result->hostedPage();
        $reflection = new ReflectionClass($hostedPage);
        $property = $reflection->getProperty('_data');
        $property->setAccessible(true);
        $res = $property->getValue($hostedPage);
        return response()->json(['status' => 'success', 'res' => $res], 200);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function create_subscription(Request $request)
    {
        $payOffline = $request->payOffline;
        $link = $request->link;
        if (Auth::guard('api')->user()) {
            $link1 = env('FRONT_URL') . '/admin/payment-success/' . $link;
        } else {
            $link1 = env('FRONT_URL') . '/payment-success/' . $link;
        }
        $result = HostedPage::checkoutNewForItems(array(
            "subscription" => array(
                "auto_collection" => "on"
            ),
            "subscriptionItems" => array(
                array(
                    "itemPriceId" => "$request->plan",
                    "quantity" => $request->employees,
                ),
                //                array(
                //                    "itemPriceId" => "$request->addon",
                //                    "quantity" => 1
                //                )
            ),
            "customer" => array(
                "firstName" => $request->billingAddress['firstName'],
                "lastName" => $request->billingAddress['lastName'],
                "email" => $request->billingAddress['email'],
            ),
            "billingAddress" => array(
                "firstName" => $request->billingAddress['lastName'],
                "lastName" => $request->billingAddress['lastName'],
                "email" => $request->billingAddress['email'],
                "company" => $request->billingAddress['company'],
                "phone" => $request->billingAddress['phone'],
                "line1" => $request->billingAddress['address'],
                "city" => $request->billingAddress['city'],
                "state" => $request->billingAddress['state'],
                "zip" => $request->billingAddress['zip'],
                "country" => $request->billingAddress['country']
            ),
            "redirectUrl" => $link1,
        ));
        $hostedPage = $result->hostedPage();
        $reflection = new ReflectionClass($hostedPage);
        $property = $reflection->getProperty('_data');
        $property->setAccessible(true);
        $res = $property->getValue($hostedPage);

        $c = Company::where('employee_registration_link', $link)->first();
        $c->chargebee_subscription_id = $res['id'];
        $c->save();
        return response()->json(['status' => 'success', 'res' => $res], 200);
    }

    public function update_payment_status($link)
    {
        $c = Company::where('employee_registration_link', $link)->first();
        $c->payment_status = "COMPLETED";
        $c->save();
        $user = User::where('id', $c->user_id)->first();
        $user1 = Auth::guard('api')->user();
        if (!$user1) {
            Auth::login($user);
            $accessToken = Auth::user()->createToken('authToken')->accessToken;
            $c = '';
            if ($user->role == "COMPANY") {
                $c = Company::select('companies.*', 'company_employees.first_name', 'company_employees.last_name', 'company_employees.role')->join('company_employees', 'companies.id', 'company_employees.company_id')->where("company_employees.user_id", $user->id)->first();
                if ($c) {
                    $c->company_logo = url('/') . '/public/uploads/' . $c->company_logo;
                }
            }
            $user->last_login = DB::raw('CURRENT_TIMESTAMP');
            $user->save();

            $link1 = env('FRONT_URL') . '/registration/' . $link;
            $data = ['link' => $link1, 'name' => $c->company_name];

            Mail::send('registration-email', $data, function ($message) use ($user, $c) {
                $message->to($user->email, $c->company_name)
                    ->subject('Welcome to Mpact International’s Cognitive Dynamism Platform');
                $message->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
            });

            return response(['user' => $user, 'company' => $c, 'access_token' => $accessToken]);
        } else {
            return response(['user' => '', 'company' => '', 'access_token' => '']);
        }
    }

    public function create_estimate(Request $request)
    {
        $result = Estimate::createSubItemEstimate(array(
            "subscriptionItems" => array(
                array(
                    "itemPriceId" => $request->plan,
                    "quantity" => $request->employees
                ),
                //                array(
                //                    "itemPriceId" => $request->addon,
                //                    "quantity" => 1
                //                )
            )
        ));
        $estimate1 = [];
        $estimate = $result->estimate();
        //        dd($estimate);
        $reflection = new ReflectionClass($estimate);
        $property = $reflection->getProperty('_values');
        $property->setAccessible(true);
        $estimate1[] = $property->getValue($estimate);
        return response()->json(['status' => 'success', 'res' => $estimate1], 200);
    }

    public function get_plan_details_by_subscription_id($id)
    {
        $res = HostedPage::retrieve($id);
        $reflection = new ReflectionClass($res);
        $property = $reflection->getProperty('_response');
        $property->setAccessible(true);
        $res1 = $property->getValue($res);
        $sub_id = $res1['hosted_page']['content']['subscription']['id'];

        $sub = Subscription::retrieve($sub_id);
        $reflection = new ReflectionClass($sub);
        $property = $reflection->getProperty('_response');
        $property->setAccessible(true);
        $sub1 = $property->getValue($sub);

        return response()->json(['status' => 'success', 'res' => $sub1['subscription']['subscription_items'], 'id' => $sub_id], 200);
    }

    public function update_tiers_chargebee(Request $request)
    {

        $req = $request->all();

        $plan_id = $request['content']['item_price']['id'];
        PlanTier::where('plan_id', $plan_id)->delete();
        $tiers = $request['content']['tiers'];
        $resp = ["request" => $req['content']];
        foreach ($tiers as $t) {
            $pt = new PlanTier();
            $pt->plan_id = $plan_id;
            $pt->starting_unit = $t['starting_unit'];
            $pt->ending_unit = $t['ending_unit'];
            $pt->price = $t['price'];
            $pt->save();
        }

        Mail::send('webhook-email', $resp, function ($message) {
            $message->to("deepika.manifest@gmail.com", "webhook")
                ->subject('Welcome to Mpact International’s Cognitive Dynamism Platform');
            $message->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
        });

        // echo "request object";
        // dd($request);
        // echo "response object";
        // dd($res);
    }
}
