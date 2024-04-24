<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AppController;
use App\Http\Controllers\ChargebackController;
use App\Http\Controllers\MarchantController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\RefundController;
use App\Http\Controllers\ReversalController;
use App\Http\Controllers\UserTargetController;
use App\Http\Controllers\ItController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('/auth/register', [AuthController::class, 'register']);
Route::get('/redirect', [AuthController::class, 'nouser'])->name('nouser');
Route::post('/auth/login', [AuthController::class, 'login'])->name('login');
Route::post('/forgot-password', [AuthController::class, 'forgot_password'])->name('forgot.password');
Route::post('/forgot-password-code', [AuthController::class, 'forgot_password_code'])->name('forgot.password.code');
Route::post('/forgot-password-reset', [AuthController::class, 'forgot_password_reset'])->name('forgot.password.reset');
Route::post('/auth/check-token', [AuthController::class, 'checktoken'])->name('token.check');
Route::post('/admin/csv-data-handle', [AdminController::class, 'csv_data_handle']);
Route::post('forgot_password', [AuthController::class, 'forgot_password']);
Route::post('otp_verification', [AuthController::class, 'otp_verification']);
Route::post('reset_password', [AuthController::class, 'reset_password']);

Route::group(['middleware' => ['auth:sanctum']], function () {

    //GetSources
    Route::get('/admin/get-sources', [ItController::class, 'get_sources']);
    // Route::middleware(['admin'])->group(function () {
    //UnitUser
    Route::get('/admin/user-units/{unit_id?}', [AdminController::class, 'unit_user']);
    Route::get('/admin/unit-brands/{unit_id?}', [AdminController::class, 'unit_brands']);
    Route::post('permission-modifiy', [AdminController::class, 'update_permission']);
    Route::get('get-permissions', [AdminController::class, 'get_permissions']);

    //Report Generate
    Route::post('/admin/unit-report-generate-1', [AdminController::class, 'get_all_units_report']);
    Route::post('/admin/report-generate-1', [AdminController::class, 'report_get']);
    Route::post('/admin/unit-sheets-generate', [AdminController::class, 'latest_unit_sheets_get']);
    Route::post('/admin/unit-sheets-generate-rework', [AdminController::class, 'unit_sheets_get_rework']);
    Route::post('/admin/unit-sheets-generate-users',[AdminController::class,'unit_sheets_get_users']);

    //Merchant Routes
    Route::get('/admin/merchant-listing', [MarchantController::class, 'merchant_listing'])->name('merchant.listing');
    Route::get('/admin/get-merchant/{merchant}', [MarchantController::class, 'merchant_view'])->name('merchant.data');
    Route::post('/admin/merchant-add-edit/{merchant?}', [MarchantController::class, 'merchant_add_update'])->name('merchant.add.update');
    Route::post('/admin/merchant-delete/{merchant?}', [MarchantController::class, 'merchant_delete'])->name('merchant.delete');

    //Refund Routes
    Route::get('/admin/refund-listing', [RefundController::class, 'refund_listing'])->name('refund.listing');
    Route::get('/admin/get-refund/{refund}', [RefundController::class, 'refund_view'])->name('refund.data');
    Route::post('/admin/refund-add-edit/{refund?}', [RefundController::class, 'refund_add_update'])->name('refund.add.update');
    Route::get('/admin/refund-delete/{refund}', [RefundController::class, 'refund_delete'])->name('refund.delete');

    //Chargeback Routes
    Route::get('/admin/chargeback-listing', [ChargebackController::class, 'chargeback_listing'])->name('chargeback.listing');
    Route::get('/admin/get-chargeback/{chargeback}', [ChargebackController::class, 'chargeback_view'])->name('chargeback.data');
    Route::post('/admin/chargeback-add-edit/{chargeback?}', [ChargebackController::class, 'chargeback_add_update'])->name('chargeback.add.update');
    Route::get('/admin/chargeback-delete/{chargeback}', [ChargebackController::class, 'chargeback_delete'])->name('chargeback.delete');
    
    //Lead Sources Routes
    Route::get('/admin/leadsource-listing', [AdminController::class, 'leadsource_listing'])->name('leadsource.listing');
    Route::get('/admin/get-leadsource/{leadsource}', [AdminController::class, 'leadsource_view'])->name('leadsource.data');
    Route::post('/admin/leadsource-add-edit/{leadsource?}', [AdminController::class, 'leadSourcesAddUpdate'])->name('leadsource.add.update');
    Route::get('/admin/leadsource-delete/{leadsource}', [AdminController::class, 'leadsource_delete'])->name('leadsource.delete');

    //Purchase Routes
    Route::get('/admin/purchase-listing', [PurchaseController::class, 'purchase_listing'])->name('purchase.listing');
    Route::get('/admin/get-purchase/{purchase}', [PurchaseController::class, 'purchase_view'])->name('purchase.data');
    Route::post('/admin/purchase-add-edit/{purchase?}', [PurchaseController::class, 'purchase_add_update'])->name('purchase.add.update');
    Route::get('/admin/purchase-delete/{purchase}', [PurchaseController::class, 'purchase_delete'])->name('purchase.delete');

    //Reversal Routes
    Route::get('/admin/reversal-listing', [ReversalController::class, 'reversal_listing'])->name('reversal.listing');
    Route::get('/admin/get-reversal/{reversal}', [ReversalController::class, 'reversal_view'])->name('reversal.data');
    Route::post('/admin/reversal-add-edit/{reversal?}', [ReversalController::class, 'reversal_add_update'])->name('reversal.add.update');
    Route::get('/admin/reversal-delete/{reversal}', [ReversalController::class, 'reversal_delete'])->name('reversal.delete');

    //User Targets
    Route::get('/admin/usertarget-listing', [UserTargetController::class, 'usertarget_listing'])->name('usertarget.listing');
    Route::get('/admin/get-usertarget/{usertarget}', [UserTargetController::class, 'usertarget_view'])->name('usertarget.data');
    Route::post('/admin/usertarget-add-edit/{usertarget?}', [UserTargetController::class, 'usertarget_add_update'])->name('usertarget.add.update');
    Route::post('/admin/usertarget-delete/{usertarget?}', [UserTargetController::class, 'usertarget_delete'])->name('usertarget.delete');

    //Users
    Route::get('/admin/user-listing', [AdminController::class, 'userlisting'])->name('user.listing');
    Route::get('/admin/get-user/{user}', [AdminController::class, 'specificuserdata'])->name('user.data');
    Route::post('/admin/user-add-edit/{user?}', [AdminController::class, 'useradd'])->name('user.add.update');
    Route::get('/admin/user-delete/{user}', [AdminController::class, 'user_delete'])->name('user.delete');
    Route::post('/admin/user-pass-update', [AdminController::class, 'update_password'])->name('user.add.update.password');
    //Roles
    Route::get('/admin/role-listing', [AdminController::class, 'rolelisting'])->name('role.listing');
    Route::get('/admin/view-role/{role}', [AdminController::class, 'roleview'])->name('role.view');
    Route::post('/admin/role-add-edit/{role?}', [AdminController::class, 'RolesAddEdit'])->name('role.add.update');

    //Unit
    Route::get('/admin/unit-listing', [AdminController::class, 'unitlisting'])->name('unit.listing');
    Route::get('/admin/view-unit/{unit}', [AdminController::class, 'unitview'])->name('unit.view');
    Route::post('/admin/unit-add-edit/{unit?}', [AdminController::class, 'UnitsAddEdit'])->name('unit.add.update');

    //Brands
    Route::get('/admin/brand-listing', [AdminController::class, 'brandslist'])->name('brand.listing');
    Route::get('/admin/view-brand/{brand}', [AdminController::class, 'specificbrand'])->name('brand.view');
    Route::post('/admin/brand-add-edit/{brand?}', [AdminController::class, 'BrandsAddEdit'])->name('brand.add.update');
    Route::get('/admin/delete-brand/{brand}', [AdminController::class, 'delete_brand'])->name('brand.delete');

    //Leads
    Route::get('/admin/leads-listing', [AdminController::class, 'leadslist'])->name('leads.listing');
    Route::get('/admin/search-leads-listing', [AdminController::class, 'searchLeadslist'])->name('leads.listing');
    Route::get('/admin/view-leads/{leads}', [AdminController::class, 'specificleads'])->name('leads.view');
    Route::post('/admin/leads-add-edit/{leads?}', [AdminController::class, 'addeditlead'])->name('leads.add.update');
    Route::get('/admin/delete-leads/{leads}', [AdminController::class, 'delete_lead'])->name('leads.delete');

    
    //dashboard
    Route::get('/admin/get-dashboard-table', [AdminController::class, 'dashboardTable'])->name('dashboard.table');


    //amount
    Route::get('/admin/leads-amount', [AdminController::class, 'totalAmount'])->name('leads.totalAmount');

    Route::get('/admin/leads-amount-received', [AdminController::class, 'totalReceivedAmount'])->name('leads.totalReceivedAmount');

    Route::get('/admin/leads-amount-monthly', [AdminController::class, 'totalAmountMonth'])->name('leads.totalAmountMonth');

    Route::get('/admin/leads-amount-received-monthly', [AdminController::class, 'totalAmountMonthReceived'])->name('leads.totalAmountMonthReceived');

    Route::get('/auth/logout', [AuthController::class, 'logout'])->name('logout');
    // //Brands
    // Route::get('/admin/view-brand/{brand}',[AdminController::class, 'specificuserdata'])->name('brand.view');
    // Route::post('/admin/brand-add-edit/{brand?}',[AdminController::class, 'BrandsAddEdit'])->name('brand.add.update');
    Route::post('admin/set-unit-target', [AdminController::class, 'setUnitTarget'])->name('set_Unit_Target');
    Route::get('admin/get-unit-targets_details/{unitID}', [AdminController::class, 'getUnitTargets'])->name('unit_targets');
    Route::post('admin/unit-targets-edit/{unitTarget}', [AdminController::class, 'getUnitTargetsedit'])->name('unit_targets_edit');
    Route::get('admin/unit-Target-List', [AdminController::class, 'unitTargetList'])->name('unit_target_list');
    Route::delete('admin/unit-targets-delete/{unitTarget}', [AdminController::class, 'getUnitTargetsdelete'])->name('unit_targets_delete');
    // });
});

Route::any(
    '/login',
    function () {
        return Response()->json(["status" => false, 'msg' => 'Token is Wrong OR Did not Exist!']);
    }
)->name('check-login');
