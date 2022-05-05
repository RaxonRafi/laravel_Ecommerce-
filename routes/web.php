<?php
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\FrontendController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\SubcategoryController;
use App\Http\Controllers\ProductController;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::get('/',[FrontendController::class,'index'])->name('index');
Route::get('product/details/{slug}',[FrontendController::class, 'productdetails'])->name('productdetails');
Route::get('about',[FrontendController::class,'about']);
Route::get('team',[FrontendController::class,'team']);
Route::get('contact',[FrontendController::class,'contact']);
Route::post('get/sizes',[FrontendController::class, 'getsizes'])->name('get.sizes');
Route::post('get/inventory',[FrontendController::class, 'getinventory'])->name('get.inventory');

Auth::routes(['login'=>false]);
Route::get('/admin/login',[LoginController::class, 'showLoginForm'])->name('login');
Route::post('login',[LoginController::class, 'login'])->name('adminlogin');

Route::get('login',[CustomerController::class, 'customerlogin'])->name('customerlogin');
Route::post('customer/register',[CustomerController::class, 'customerregister'])->name('customer.register');
Route::get('customer/dashboard',[CustomerController::class, 'customerdashboard'])->name('customer.dashboard');
Route::post('insert/cart',[CustomerController::class, 'insertcart'])->name('insert.cart');
Route::get('cart',[CustomerController::class, 'cart'])->name('cart');



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
