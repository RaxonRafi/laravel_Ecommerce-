<?php
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ProblemPaymentController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\FrontendController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentCallbackController;
use App\Http\Controllers\SubcategoryController;
use App\Http\Controllers\ProductController;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::get('/',[FrontendController::class,'index'])->name('index');
Route::get('shop',[FrontendController::class,'shop'])->name('shop');
Route::get('product/details/{slug}',[FrontendController::class, 'productdetails'])->name('productdetails');
Route::get('about',[FrontendController::class,'about']);
Route::get('team',[FrontendController::class,'team']);
Route::get('contact',[FrontendController::class,'contact']);
Route::post('get/sizes',[FrontendController::class, 'getsizes'])->name('get.sizes');
Route::post('get/inventory',[FrontendController::class, 'getinventory'])->name('get.inventory');
Route::post('check/coupon',[FrontendController::class, 'checkcoupon'])->name('check.coupon');


Auth::routes(['login'=>false]);
Route::get('/admin/login',[LoginController::class, 'showLoginForm'])->name('login');
Route::post('login',[LoginController::class, 'login'])->name('adminlogin');

// Public: reaching the login/registration screen must not require a session.
Route::get('login',[CustomerController::class, 'customerlogin'])->name('customerlogin');
Route::post('customer/register',[CustomerController::class, 'customerregister'])->name('customer.register');
Route::post('get/city/list',[CustomerController::class, 'getcitylist'])->name('get.city.list');

// Everything that reads or mutates a specific customer's cart requires a session.
Route::middleware('auth')->group(function () {
    Route::get('customer/dashboard',[CustomerController::class, 'customerdashboard'])->name('customer.dashboard');
    Route::post('insert/cart',[CustomerController::class, 'insertcart'])->name('insert.cart');
    Route::get('cart',[CustomerController::class, 'cart'])->name('cart');
    Route::post('cart/remove',[CustomerController::class, 'cartremove'])->name('cart.remove');
    Route::post('set/country/city',[CustomerController::class, 'setcountrycity'])->name('set.country.city');
    Route::get('checkout',[FrontendController::class,'checkout'])->name('checkout');

    // Orders
    Route::post('order/place',[OrderController::class,'store'])->name('order.place');
    Route::get('orders',[OrderController::class,'index'])->name('order.index');
    Route::get('order/{order}',[OrderController::class,'show'])->name('order.show');
});

// Payment gateway callbacks. Public by necessity: SSLCommerz calls the IPN
// server-to-server with no session, and posts the customer back to the other
// three cross-origin, so none of them can carry a CSRF token (see
// PreventRequestForgery::$except). None of them trusts its own request body —
// every one re-validates with the gateway before anything is settled.
Route::prefix('payment/sslcommerz')->name('payment.sslcommerz.')->group(function () {
    Route::post('ipn',[PaymentCallbackController::class,'ipn'])->name('ipn')->middleware('throttle:60,1');
    Route::match(['get','post'],'success',[PaymentCallbackController::class,'success'])->name('success');
    Route::match(['get','post'],'fail',[PaymentCallbackController::class,'fail'])->name('fail');
    Route::match(['get','post'],'cancel',[PaymentCallbackController::class,'cancel'])->name('cancel');
});

// Admin order management. Guarded by auth + checkrole inside the controller.
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('orders',[AdminOrderController::class,'index'])->name('orders.index');
    Route::get('orders/{order}',[AdminOrderController::class,'show'])->name('orders.show');
    Route::patch('orders/{order}/status',[AdminOrderController::class,'updateStatus'])->name('orders.status');

    // Failed payments waiting on a human.
    Route::get('problem-payments',[ProblemPaymentController::class,'index'])->name('problem-payments.index');
    Route::get('problem-payments/{problemPayment}',[ProblemPaymentController::class,'show'])->name('problem-payments.show');
    Route::post('problem-payments/{problemPayment}/recheck',[ProblemPaymentController::class,'recheck'])->name('problem-payments.recheck');
    Route::post('problem-payments/{problemPayment}/resolve',[ProblemPaymentController::class,'resolveManually'])->name('problem-payments.resolve');
});



Route::get('/home', [HomeController::class, 'index'])->name('home');
Route::get('/profile', [HomeController::class, 'profile'])->name('profile');
Route::post('/change/name', [HomeController::class, 'changename'])->name('change.name');
Route::post('/change/password', [HomeController::class, 'changepassword'])->name('change.password');
Route::get('/add/team/member', [HomeController::class, 'addteammember']);
Route::post('/team/member/insert', [HomeController::class, 'teammemberinsert']);
Route::get('/team/member/delete/{team_member_id}', [HomeController::class, 'teammemberdelete']);
Route::get('/team/member/edit/{team_member_id}', [HomeController::class, 'teammemberedit']);
Route::post('/team/member/update/{team_member_id}', [HomeController::class, 'teammemberupdate']);
Route::get('/variation', [HomeController::class, 'variation'])->name('variation');
Route::post('/add/color', [HomeController::class, 'addcolor'])->name('add.color');
Route::post('/add/size', [HomeController::class, 'addsize'])->name('add.size');
Route::get('/shipping', [HomeController::class, 'shipping'])->name('shipping');
Route::post('/add/shipping', [HomeController::class, 'addshipping'])->name('add.shipping');
Route::get('/coupon', [HomeController::class, 'coupon'])->name('coupon');
Route::post('/add/coupon', [HomeController::class, 'addcoupon'])->name('add.coupon');



Route::resource('category', CategoryController::class);
Route::get('/restore/{id}', [CategoryController::class, 'restore'])->name('category.restore');
Route::get('/forcedelete/{id}', [CategoryController::class, 'forcedelete'])->name('category.forcedelete');
Route::resource('subcategory', SubcategoryController::class);
Route::resource('product', ProductController::class);
Route::get('/add/featured/photo/{product_id}', [ProductController::class, 'addfeaturedphoto'])->name('add.featured.photo');
Route::post('/add/featured/photo/{product_id}', [ProductController::class, 'addfeaturedphotopost'])->name('add.featured.photo.post');
Route::post('/get/subcategories', [ProductController::class, 'getsubcategories'])->name('get.subcategories');
Route::get('/add/inventory/{product_id}', [ProductController::class, 'addinventory'])->name('add.inventory');
Route::post('/add/inventory/post/{product_id}', [ProductController::class, 'addinventorypost'])->name('add.inventory.post');
