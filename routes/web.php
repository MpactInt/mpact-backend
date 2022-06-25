<?php


use App\Http\Controllers\Common\ChargebeeController;
use App\Http\Controllers\Common\MessageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', [ChargebeeController::class,'index']);
//
//Route::get('/create-customer', [\App\Http\Controllers\PaymentController::class, 'create_customer']);
//Route::get('/create-product-family', [\App\Http\Controllers\PaymentController::class, 'create_product_family']);
//Route::get('/create-plan', [\App\Http\Controllers\PaymentController::class, 'create_plan']);
//Route::get('/create-plan-price', [\App\Http\Controllers\PaymentController::class, 'create_item_price']);
//Route::get('/create-subscription', [\App\Http\Controllers\PaymentController::class, 'create_subscription']);



Route::get('/webhook-listen', [ChargebeeController::class, 'webhook_listen']);
Route::get('download', [MessageController::class, 'download']);

