<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Roles;
use App\Models\Units;
use App\Models\Brands;
use App\Models\UnitBrands;
use App\Models\UserUnits;
use App\Models\UnitTarget;
use App\Models\Leads;
use App\Models\SourceModel;
use App\Models\UserTargetsModel;
use App\Models\UserPermissionModel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\ChargeBackModel;
use App\Models\PurchaseModel;
use App\Models\RefundModel;
use App\Models\ReversalModel;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use DateInterval;
use DatePeriod;
use DateTime;
use Illuminate\Support\Facades\Hash;
use Exception;
use SebastianBergmann\CodeCoverage\Report\Xml\Unit;

class AdminController extends Controller
{
    // public function user_permission($user)
    // {

    //     // dd($user);
    //     $data = UserPermissionModel::query();

    //     $data->where('role',$user->user_role);


    //     if($user->permission){
    //         $data->where('child_role',$user->permission);

    //     }

    //     $data = $data->first();



    //     $data->roles = json_decode($data->roles);
    //     $data->merchant = json_decode($data->merchant);
    //     $data->leads = json_decode($data->leads);
    //     $data->refund = json_decode($data->refund);
    //     $data->chargeback = json_decode($data->chargeback);
    //     $data->reversal = json_decode($data->reversal);
    //     $data->brands = json_decode($data->brands);
    //     $data->units = json_decode($data->units);
    //     $data->users = json_decode($data->users);
    //     $data->purchase = json_decode($data->purchase);
    //     $data->user_targets = json_decode($data->user_targets);
    //     $data->unit_targets = json_decode($data->unit_targets);


    //     return $data;
    // }
    public function csv_data_handle_page()
    {
        return view('csv_data_handle');
    }
     public function csv_data_handle_submit(Request $req)
    {

        $file_data_in = fopen($_FILES['csv']['tmp_name'], 'r');

        $category = $req->category;
        if($category=="leads")
        {
    
    
            $data = [];
            while (($row = fgetcsv($file_data_in)) !== FALSE) {
                // dd($row);
                $data[] = $row;
                // $data[$row[1]][$row[3]][] = $row[4];
            }
    
            unset($data[0]);
    
            $data = array_values($data);
            // dd($data);
    
    
            foreach ($data as $item) {
              
    //dd($item);
                $date = date('Y-m-d', strtotime($item[0]));
                $source = $item[1];
                $brand = $item[2];
                $product = $item[3];
                $name = $item[4];
                $email = $item[5];
                $phone = $item[6];
                $description = $item[7];
                $amount = floatval(str_replace(",", "", trim(str_replace("$", "", $item[8]))));
                $received = floatval(str_replace(",", "", trim(str_replace("$", "", $item[9]))));
                $recovery = floatval(str_replace(",", "", trim(str_replace("$", "", $item[10]))));
                $sales_rep = $item[11];
                $account_rep = $item[12];
                $unit = $item[13]; 
    
    
                $processData = false;
                
                if ($unit !== "" && $brand !== "" && $sales_rep !== "") {
                   
                    $user_data = $this->user_units_get($unit);
                    // dd($user_data);
                    $unit_brands = $this->units_brand_get($unit);
                    $processData = $this->check_units($unit);
                    
                    $processData = ( in_array($sales_rep, $user_data) == false || in_array($brand, $unit_brands) == false )  ? false : true;
    
                    if ($account_rep != "") {
                        $processData = in_array(intval($account_rep), $user_data) ? true : false;
                    }
                }
                //  dd($processData,$sales_rep,$user_data,$account_rep);
                if (!$processData) {
                    continue;
                }
    
    
    
                $leads = new Leads();
                // dd($leads,$code,$description);
                $code = $this->generateUniqueId("lead_");
    
                $lead = Leads::where('code', $code)->first();
    
                if ($lead) {
                    $code = $this->generateUniqueId("lead_");
                }
                $leads->code = $code;
                $leads->source = $source;
                $leads->brand = $brand;
                $leads->product = $product;
                $leads->email = $email;
                $leads->name = $name;
                $leads->phone = $phone;
                $leads->description = $description;
                $leads->quoted_amount = $amount;
                $leads->received = ($received != "") ? round($received) : 0;
                $leads->recovery = ($recovery != "") ? round($recovery) : 0;
                $leads->sales_rep = $sales_rep;
                $leads->date = $date;
                // $leads->code =  $code;
                $leads->account_rep = ($account_rep != "") ? $account_rep : Null;
                $leads->unit_id = $unit;
    
    
    
    
                $leads->save();
            }
    
             return redirect()->route('csv.data.page')->with('success', 'Data Upload Success');
        }
        $data = [];
        while (($row = fgetcsv($file_data_in)) !== FALSE) {
            // dd($row);
            $data[] = $row;
            // $data[$row[1]][$row[3]][] = $row[4];
        }

        unset($data[0]);

        $data = array_values($data);

        foreach ($data as $item) {
            $lead_code = $item[0];
            $amount = $item[1];
            $date = date('Y-m-d', strtotime($item[2]));
            $type = $item[3];
            $merchant_id = $item[4];
            $reason = $item[5];


            $lead = Leads::where('code', $lead_code)->first();

            if ($category == "refund") {
                $refund = new RefundModel();
                $unit_id = isset($item[6]) ? $item[6] : null;
                $user_id = isset($item[7]) ? $item[7] : null;
                $refund->lead_code = $lead_code;
                $refund->unit_id = $unit_id;
                $refund->refund_amount = $amount;
                $refund->refund_type = $type;
                $refund->merchant_id = $merchant_id;
                $refund->reason = $reason;
                $refund->refund_user_id = $user_id;
                $refund->refund_date = $date;
                $refund->save();
            } else if ($category == "chargeback") {
                $unit_id = isset($item[6]) ? $item[6] : null;
                $user_id = isset($item[7]) ? $item[7] : null;
                $description = isset($item[8]) ? $item[8] : ""; 
                $chargeback = new ChargeBackModel();
                $chargeback->lead_code = $lead_code;
                $chargeback->unit_id = $unit_id;
                $chargeback->chargeback_amount = $amount;
                $chargeback->chargeback_type = $type;
                $chargeback->merchant_id = $merchant_id;
                $chargeback->reason = $reason;
                $chargeback->chargeback_user_id = $user_id;
                $chargeback->chargeback_date = $date;
                $chargeback->description = $description;
                $chargeback->save();
            } else if ($category == "purchase") {
                $user_id = isset($item[7]) ? $item[7] : null;
                $unit_id = isset($item[6]) ? $item[6] : null;
                $purchase = new PurchaseModel();
                $purchase->lead_code = $lead_code;
                $purchase->unit_id = $unit_id;
                $purchase->purchase_amount = $amount;
                $purchase->purchase_type = $type;
                $purchase->reason = $reason;
                $purchase->purchase_user_id = $user_id;
                $purchase->purchase_date = $date;
                $purchase->save();
            } else if ($category == "reversal") {
                $user_id = isset($item[7]) ? $item[7] : null;
                $unit_id = isset($item[6]) ? $item[6] : null;
                $reversal = new ReversalModel();
                $reversal->lead_code = $lead_code;
                $reversal->unit_id = $unit_id;
                $reversal->reversal_amount = $amount;
                $reversal->reversal_type = $type;
                $reversal->merchant_id = $merchant_id;
                $reversal->reversal_user_id = $user_id;
                $reversal->reversal_date = $date;
                $reversal->save();
            }
        }

        return redirect()->route('csv.data.page')->with('success', 'Data Upload Success');
    }
    public function update_password(Request $request)
    {
        // dd('yes'); 
        if (auth()->user()) {
            $user = User::where('id', intval($request->userid))->first();
            if ($user) {
                $user->password = Hash::make($request->newpassword);
                if ($user->save()) {
                    return response()->json(['status' => true, 'msg' => 'Password Updated Successfully', 'user' => $user]);
                } else {
                    return response()->json(['status' => false, 'msg' => 'Error']);
                }
            } else {
                return response()->json(['status' => false, 'msg' => 'No User Found']);
            }
        }
    }
    public function get_permissions(Request $req)
    {

        $data = UserPermissionModel::query();

        $data->where('role', $req->role);



        if ($req->role != 3) {
            if (isset($req->child_role) && $req->child_role != "undefined") {
                $data->where('child_role', $req->child_role);
            }
        }

        $data = $data->first();


        $id = $data->id;
        $child_role = $data->child_role;
        $role = $data->role;
        unset($data->id);
        unset($data->child_role);
        unset($data->role);
        unset($data->created_at);
        unset($data->updated_at);



        $data->roles = json_decode($data->roles);
        $data->merchant = json_decode($data->merchant);
        $data->leads = json_decode($data->leads);
        $data->refund = json_decode($data->refund);
        $data->chargeback = json_decode($data->chargeback);
        $data->reversal = json_decode($data->reversal);
        $data->brands = json_decode($data->brands);
        $data->units = json_decode($data->units);
        $data->users = json_decode($data->users);
        $data->purchase = json_decode($data->purchase);
        $data->user_targets = json_decode($data->user_targets);
        $data->unit_targets = json_decode($data->unit_targets);







        return response()->json(['status' => true, 'permissions' => [$data], 'id' => $id, 'role' => $role, 'child_role' => $child_role]);
    }
    public function update_permission(Request $req)
    {
        $role = $req->role;
        $child = $req->child_role;
        $permissions = json_decode($req->permissions)[0];

        // dd($permissions);


        $data = UserPermissionModel::query();

        $data->where('role', $role);


        if (isset($child) && $role != 3) {
            $data->where('child_role', $child);
        }

        $data = $data->first();

        // return response()->json(['data'=>$permissions);

        $data->roles = json_encode($permissions->roles);
        $data->merchant = json_encode($permissions->merchant);
        $data->leads = json_encode($permissions->leads);
        $data->refund = json_encode($permissions->refund);
        $data->chargeback = json_encode($permissions->chargeback);
        $data->reversal = json_encode($permissions->reversal);
        $data->brands = json_encode($permissions->brands);
        $data->units = json_encode($permissions->units);
        $data->users = json_encode($permissions->users);
        $data->purchase = json_encode($permissions->purchase);
        $data->user_targets = json_encode($permissions->user_targets);
        $data->unit_targets = json_encode($permissions->unit_targets);
        $data->report = $permissions->report;
        $data->unit_report = $permissions->unit_report;
        $data->user_reports = $permissions->user_reports;
        

        $data->save();


        return response()->json(['status' => true, 'msg' => 'Permissions Update Success']);
    }

   
    public function csv_data_handle(Request $req)
    {
        


        $file_data_in = fopen($_FILES['csv']['tmp_name'], 'r');


        $data = [];
        while (($row = fgetcsv($file_data_in)) !== FALSE) {
            // dd($row);
            $data[] = $row;
            // $data[$row[1]][$row[3]][] = $row[4];
        }

        unset($data[0]);

        $data = array_values($data);
        // dd($data);


        foreach ($data as $item) {
          
//dd($item);
            $date = date('Y-m-d', strtotime($item[0]));
            $source = $item[1];
            $brand = $item[2];
            $product = $item[3];
            $name = $item[4];
            $email = $item[5];
            $phone = $item[6];
            $description = $item[7];
            $amount = floatval(str_replace(",", "", trim(str_replace("$", "", $item[8]))));
            $received = floatval(str_replace(",", "", trim(str_replace("$", "", $item[9]))));
            $recovery = floatval(str_replace(",", "", trim(str_replace("$", "", $item[10]))));
            $sales_rep = $item[11];
            $account_rep = $item[12];
            $unit = $item[13]; 


            $processData = false;
            
            if ($unit !== "" && $brand !== "" && $sales_rep !== "") {
               
                $user_data = $this->user_units_get($unit);
                // dd($user_data);
                $unit_brands = $this->units_brand_get($unit);
                $processData = $this->check_units($unit);
                
                $processData = ( in_array($sales_rep, $user_data) == false || in_array($brand, $unit_brands) == false )  ? false : true;

                if ($account_rep != "") {
                    $processData = in_array(intval($account_rep), $user_data) ? true : false;
                }
            }
            //  dd($processData,$sales_rep,$user_data,$account_rep);
            if (!$processData) {
                continue;
            }



            $leads = new Leads();
            // dd($leads,$code,$description);
            $code = $this->generateUniqueId("lead_");

            $lead = Leads::where('code', $code)->first();

            if ($lead) {
                $code = $this->generateUniqueId("lead_");
            }
            $leads->code = $code;
            $leads->source = $source;
            $leads->brand = $brand;
            $leads->product = $product;
            $leads->email = $email;
            $leads->name = $name;
            $leads->phone = $phone;
            $leads->description = $description;
            $leads->quoted_amount = $amount;
            $leads->received = ($received != "") ? round($received) : 0;
            $leads->recovery = ($recovery != "") ? round($recovery) : 0;
            $leads->sales_rep = $sales_rep;
            $leads->date = $date;
            // $leads->code =  $code;
            $leads->account_rep = ($account_rep != "") ? $account_rep : Null;
            $leads->unit_id = $unit;




            $leads->save();
        }

        return response()->json(['status' => true]);

        //    return response()->json(['data'=>$this->user_permission(auth()->user())]);
    }

    public function user_units_get($unit_id)
    {

        $users = User::with(
            'user_target',
            'userleads',
            'userrefunds',
            'userchargeback',
            'userpurchase',
            'userreversal'
        )
            ->where('user_role', '!=', '1')->where('status', 1)->get()->toArray();



        $user_data = [];
        foreach ($users as $value) {
            $grosssum = 0;
            $target = 0;
            $refunds = 0;
            $chargeback = 0;
            $purchase = 0;
            $reversal = 0;
            $net = 0;

            $user_unit = json_decode($value['unit_id']);
            if (in_array($unit_id, $user_unit)) {
                $leads = array_values($value['userleads']);

                if (!empty($leads)) {




                    $net =  array_sum(array_column($value['userleads'], 'gross'));
                }


                $user_data[] = $value['id'];
            }
        }



        return $user_data;
    }

    public function units_brand_get($unit_id)
    {
        $data = UnitBrands::where('unit_id', $unit_id)->get();

        if (count($data) == 0) {
            return [];
        }
        $unit_data = [];
        foreach ($data as $value) {
            $brand = Brands::where('id', $value->brand_id)->first();
            $unit_data[] = $brand->id;
        }

        return $unit_data;
    }

    public function check_units($unit_id)
    {
        $unit = Units::where('id', $unit_id)->first();

        if ($unit) {
            return true;
        } else {
            return false;
        }
    }

    function generateUniqueId($name)
    {
        // Get the first three letters of the name
        $namePrefix = strtolower($name);

        // Calculate the remaining length for the unique ID (between 5 and 7 characters)
        $remainingLength = mt_rand(5, 7);

        // Generate a random string for the remaining characters
        $randomString = bin2hex(random_bytes($remainingLength));

        // Combine the name prefix and random string
        $uniqueId = $namePrefix . $randomString;

        // Ensure the total length is between 8 and 10 characters
        if (strlen($uniqueId) < 8) {
            $uniqueId .= bin2hex(random_bytes(8 - strlen($uniqueId)));
        } elseif (strlen($uniqueId) > 10) {
            $uniqueId = substr($uniqueId, 0, 10);
        }

        return $uniqueId;
    }
    function unitsJsonConverter($json)
    {
        $unitIds = [];

        $unitsJson = stripslashes($json);

        if ($unitsJson !== '') {
            $unitsArray = json_decode($unitsJson, true);

            if (!empty($unitsArray)) {
                $unitIds = array_map('strval', array_column($unitsArray, 'value'));
            }
        }


        return json_encode($unitIds);
    }


    function unitsJsonConverterInt($json)
    {
        $unitIds = [];

        $unitsJson = stripslashes($json);

        if ($unitsJson !== '') {
            $unitsArray = json_decode($unitsJson, true);

            if (!empty($unitsArray)) {
                $unitIds = array_map('intval', array_column($unitsArray, 'value'));
            }
        }


        return json_encode($unitIds);
    }
public function getUnitReportByMonth(Request $req)
    {
        $units = "";


        if (auth()->user()->role->id == 4 && auth()->user()->permission !== null && auth()->user()->permission == 2) {
            $units = json_decode(auth()->user()->unit_id);

            $units = Units::with('MonthTarget', 'unit_leads')->whereIn('id', $units)->get()->toArray();
        } else if ((auth()->user()->role->id == 3 && auth()->user()->permission == null) || (auth()->user()->role->id == 4 && auth()->user()->permission == 1)) {
            $units = json_decode(auth()->user()->unit_id);

            $units = Units::with('MonthTarget', 'unit_leads')->whereIn('id', $units)->get()->toArray();
        } else {
            $units = Units::with('MonthTarget', 'unit_leads')->get()->toArray();
        }
        $month = $req->month;
        $year = $req->year;

        if (isset($month) && $month != "Null") {
            $grand_total = 0;
            $unit_array = [];
            foreach ($units as $unit) {

                $unit_id = $unit['id'];

                $unit_name = Units::where('id', $unit_id)->first()->name;
                $month = $req->month;

                $users = User::with(
                    'user_target',
                    'userleads',
                    'userrefunds',
                    'userchargeback',
                    'userpurchase',
                    'userreversal'
                )->where('user_role', '!=', '1')->get()->toArray();

                $total = 0;



                foreach ($users as $userDataKey => $userdata) {
                    $grosssum = 0;

                    $target = 0;
                    $refunds = 0;
                    $chargeback = 0;
                    $purchase = 0;
                    $reversal = 0;
                    $net = 0;



                    $userUnits = json_decode($userdata['unit_id'], true);

                    foreach ($userUnits as $userUnitKey => $userUnit) {
                        $userUnits[$userUnitKey] = intval($userUnit);
                    }



                    if (in_array(intval($unit_id), $userUnits)) {


                        if (!empty($userdata['userleads'])) {
                            foreach ($userdata['userleads'] as $item) {
                                $leads = Leads::where('code', $item['code'])->first();
                                // $item['gross'] = $this->getCurrentLeadAmount($leads);
                                if ($item['unit_id'] == intval($unit_id)) {
                                    if ($month != 'Null' && isset($month) && $month == date('n', strtotime($item['date'])) && date('Y') == date('Y', strtotime($item['date']))) {
                                        $grosssum += $item['gross'];
                                    } elseif ($month == 'Null' && !isset($month) && date('Y') == date('Y', strtotime($item['date']))) {
                                        $grosssum += $item['gross'];
                                    }
                                }
                            }
                        }


                        if (!empty($userdata['user_target'])) {
                            foreach ($userdata['user_target'] as $item) {
                                if ($item['unit_id'] == intval($unit_id)) {
                                    if ($month != 'Null' && isset($month) && $month == $item['month']  && date('Y') == $item['year']) {
                                        $target += $item['target'];
                                    } elseif ($month == 'Null' && !isset($month) && date('Y') == $item['year']) {
                                        $target += $item['target'];
                                    }
                                }
                            }
                        }




                        if (!empty($userdata['userrefunds'])) {
                            foreach ($userdata['userrefunds'] as $item) {
                                if ($item['unit_id'] == intval($unit_id)) {
                                    if ($month != 'Null' && isset($month) && $month == date('n', strtotime($item['refund_date'])) && date('Y') == date('Y', strtotime($item['refund_date']))) {
                                        $refunds += $item['refund_amount'];
                                    } elseif ($month == 'Null' && !isset($month) && date('Y') == date('Y',  strtotime($item['refund_date']))) {
                                        $refunds += $item['refund_amount'];
                                    }
                                }
                            }
                        }


                        if (!empty($userdata['userchargeback'])) {
                            foreach ($userdata['userchargeback'] as $item) {
                                if ($item['unit_id'] == intval($unit_id)) {
                                    if ($month != 'Null' && isset($month) && $month == date('n', strtotime($item['chargeback_date'])) && date('Y') == date('Y', strtotime($item['chargeback_date']))) {
                                        $chargeback += $item['chargeback_amount'];
                                    } elseif ($month == 'Null' && !isset($month) && date('Y') == date('Y', strtotime($item['chargeback_date']))) {
                                        $chargeback += $item['chargeback_amount'];
                                    }
                                }
                            }
                        }


                        if (!empty($userdata['userpurchase'])) {
                            foreach ($userdata['userpurchase'] as $item) {
                                if ($item['unit_id'] == intval($unit_id)) {
                                    if ($month != 'Null' && isset($month) && $month == date('n', strtotime($item['purchase_date'])) && date('Y') == date('Y', strtotime($item['purchase_date']))) {
                                        $purchase += $item['purchase_amount'];
                                    } elseif ($month == 'Null' && !isset($month) && date('Y') == date('Y', strtotime($item['purchase_date']))) {
                                        $purchase += $item['purchase_amount'];
                                    }
                                }
                            }
                        }


                        if (!empty($userdata['userreversal'])) {
                            foreach ($userdata['userreversal'] as $item) {
                                if ($item['unit_id'] == intval($unit_id)) {
                                    if ($month != 'Null' && isset($month) && $month == date('n', strtotime($item['reversal_date'])) && date('Y') == date('Y', strtotime($item['reversal_date']))) {
                                        $reversal += $item['reversal_amount'];
                                    } elseif ($month == 'Null' && !isset($month) && date('Y') == date('Y', strtotime($item['reversal_date']))) {
                                        $reversal += $item['reversal_amount'];
                                    }
                                }
                            }
                        }

                        // if($target > 0){
                        //     $achievd = ($grosssum/$target) * 100;
                        // }
                        // else{
                        //     $achievd = 0;
                        // }



                        // $should_be_at = ($target/$this->getTotalWorkingDays(date('m'),date('Y'))) *  $this->getWorkdays(date('Y')."-".date('M')."-01", date('Y-M-d'));
                        $net = $grosssum + $reversal - $refunds - $chargeback - $purchase;
                    }

                    $total += $net;
                }
                $grand_total += $total;
                $unit_array[] = [
                    "unit_name" => $unit['name'],
                    "net" => "$" . $total,
                ];
            }
        } else if (isset($year) && $year != "Null") {

            $grand_total = 0;
            $unit_array = [];
            foreach ($units as $unit) {

                $unit_id = $unit['id'];

                $unit_name = Units::where('id', $unit_id)->first()->name;
                $month = $req->month;

                $users = User::with(
                    'user_target',
                    'userleads',
                    'userrefunds',
                    'userchargeback',
                    'userpurchase',
                    'userreversal'
                )->where('user_role', '!=', '1')->get()->toArray();

                $total = 0;



                foreach ($users as $userDataKey => $userdata) {
                    $grosssum = 0;

                    $target = 0;
                    $refunds = 0;
                    $chargeback = 0;
                    $purchase = 0;
                    $reversal = 0;
                    $net = 0;



                    $userUnits = json_decode($userdata['unit_id'], true);

                    foreach ($userUnits as $userUnitKey => $userUnit) {
                        $userUnits[$userUnitKey] = intval($userUnit);
                    }



                    if (in_array(intval($unit_id), $userUnits)) {


                        if (!empty($userdata['userleads'])) {
                            foreach ($userdata['userleads'] as $item) {
                                $leads = Leads::where('code', $item['code'])->first();
                                // $item['gross'] = $this->getCurrentLeadAmount($leads);
                                if ($item['unit_id'] == intval($unit_id)) {
                                    if ($month == date('n', strtotime($item['date'])) && $year == date('Y', strtotime($item['date']))) {

                                        $grosssum += $item['gross'];
                                    }
                                }
                            }
                        }


                        if (!empty($userdata['user_target'])) {
                            foreach ($userdata['user_target'] as $item) {
                                if ($item['unit_id'] == intval($unit_id)) {
                                    if ($month == $item['month'] && $year == $item['year']) {
                                        $target += $item['target'];
                                    }
                                }
                            }
                        }




                        if (!empty($userdata['userrefunds'])) {
                            foreach ($userdata['userrefunds'] as $item) {
                                if ($item['unit_id'] == intval($unit_id)) {
                                    if ($month == date('n',  strtotime($item['refund_date'])) && $year == date('Y',  strtotime($item['refund_date']))) {
                                        $refunds += $item['refund_amount'];
                                    }
                                }
                            }
                        }


                        if (!empty($userdata['userchargeback'])) {
                            foreach ($userdata['userchargeback'] as $item) {
                                if ($item['unit_id'] == intval($unit_id)) {
                                    if ($month == date('n',  strtotime($item['chargeback_date'])) && $year == date('Y', strtotime($item['chargeback_date']))) {
                                        $chargeback += $item['chargeback_amount'];
                                    }
                                }
                            }
                        }


                        if (!empty($userdata['userpurchase'])) {
                            foreach ($userdata['userpurchase'] as $item) {
                                if ($item['unit_id'] == intval($unit_id)) {
                                    if ($month == date('n',  strtotime($item['purchase_date'])) && $year == date('Y', strtotime($item['purchase_date']))) {
                                        $purchase += $item['purchase_amount'];
                                    }
                                }
                            }
                        }


                        if (!empty($userdata['userreversal'])) {
                            foreach ($userdata['userreversal'] as $item) {
                                if ($item['unit_id'] == intval($unit_id)) {
                                    if ($month == date('n',  strtotime($item['reversal_date'])) && $year == date('Y', strtotime($item['reversal_date']))) {
                                        $reversal += $item['reversal_amount'];
                                    }
                                }
                            }
                        }

                        // if($target > 0){
                        //     $achievd = ($grosssum/$target) * 100;
                        // }
                        // else{
                        //     $achievd = 0;
                        // }



                        // $should_be_at = ($target/$this->getTotalWorkingDays(date('m'),date('Y'))) *  $this->getWorkdays(date('Y')."-".date('M')."-01", date('Y-M-d'));
                        $net = $grosssum + $reversal - $refunds - $chargeback - $purchase;
                    }

                    $total += $net;
                }
                $grand_total += $total;
                $unit_array[] = [
                    "unit_name" => $unit['name'],
                    "net" => "$" . $total,
                ];
            }
        }

        return response()->json(['status' => true, 'msg' => 'unit report get success', 'data' => $unit_array, 'grand_total' => $grand_total]);
    }
    public function getTotalWorkingDays($month, $year)
    {
        $totalDays = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $totalWorkingDays = 0;

        for ($day = 1; $day <= $totalDays; $day++) {
            $date = "$year-$month-$day";
            $dayOfWeek = date('N', strtotime($date));


            if ($dayOfWeek >= 1 && $dayOfWeek <= 5) {
                $totalWorkingDays++;
            }
        }



        return $totalWorkingDays;
    }
    public function getWorkdays($date1, $date2, $workSat = FALSE, $patron = NULL)
    {
        if (!defined('SATURDAY')) define('SATURDAY', 6);
        if (!defined('SUNDAY')) define('SUNDAY', 0);

        // Array of all public festivities
        $publicHolidays = array();
        // The Patron day (if any) is added to public festivities
        //   if ($patron) {
        //     $publicHolidays[] = $patron;
        //   }

        /*
       * Array of all Easter Mondays in the given interval
       */
        $yearStart = date('Y', strtotime($date1));
        $yearEnd   = date('Y', strtotime($date2));

        for ($i = $yearStart; $i <= $yearEnd; $i++) {
            $easter = date('Y-m-d', easter_date($i));
            list($y, $m, $g) = explode("-", $easter);
            $monday = mktime(0, 0, 0, date($m), date($g) + 1, date($y));
            $easterMondays[] = $monday;
        }

        $start = strtotime($date1);
        $end   = strtotime($date2);
        $workdays = 0;
        for ($i = $start; $i <= $end; $i = strtotime("+1 day", $i)) {
            $day = date("w", $i);  // 0=sun, 1=mon, ..., 6=sat
            $mmgg = date('m-d', $i);
            if (
                $day != SUNDAY && $day != SATURDAY &&
                !in_array($i, $easterMondays)
            ) {
                $workdays++;
            }
        }


        return intval($workdays);
    }

    public function getCurrentWorkingDay($date)
    {

        $dayOfWeek = date('j', strtotime($date));



        if ($dayOfWeek >= 1 && $dayOfWeek <= 6) {

            return intval($dayOfWeek);
        }
    }
    //   public function unit_sheets_get_rework(Request $req)
    // {

    //     // dd($req->unit_id);
    //     $unit_ids = $req->unit_id;
    //     // $unit_name = [];
    //     // foreach($unit_id as $unit)
    //     // {
    //     //   $unit_name[] = Units::where('id', $unit)->first()->name;
    //     // }

    //     // dd($unit_name);



    //     // dd($unit_name);

    //     $month = $req->month;

    //     $users = User::with(
    //         'user_target',
    //         'userleads',
    //         'userrefunds',
    //         'userchargeback',
    //         'userpurchase',
    //         'userreversal'
    //     )->where('user_role','!=','1')->get()->toArray();



    //     $total = 0;
    //     $userreportarray = [];

    //      foreach($unit_ids as $unit_id)
    //         {
    //         $unit_name = Units::where('id', $unit_id)->first()->name;
    //         foreach ($users as $userDataKey => $userdata) {
    //             $grosssum = 0;

    //             $target = 0;
    //             $refunds = 0;
    //             $chargeback = 0;
    //             $purchase = 0;
    //             $reversal = 0;
    //             $net = 0;
    //             $achievd= 0;
    //             $should_be_at= 0;



    //             $userUnits = json_decode($userdata['unit_id'],true);

    //             foreach($userUnits as $userUnitKey => $userUnit){
    //                 $userUnits[$userUnitKey] = intval($userUnit);
    //             }

    //             // $unitUsers = array_intersect($req->unit_id, $userUnits);



    //                 if(in_array(intval($unit_id), $userUnits)){



    //                 $leads = array_values($userdata['userleads']);

    //                 if(!empty($leads)){



    //                     foreach ($leads as $key => $lead) {

    //                         if($lead['unit_id'] != $unit_id)
    //                         {
    //                             continue;
    //                         }

    //                         $date = $lead['created_at'];

    //                         $lead_month = date('n', strtotime($date));

    //                         $lead_year = date('Y', strtotime($date));



    //                         if (isset($month) && $month == $lead_month) {

    //                             $grosssum =  array_sum(array_column($userdata['userleads'], 'gross'));
    //                             $target = array_sum(array_column($userdata['user_target'], 'target'));

    //                             $refunds = array_sum(array_column($userdata['userrefunds'], 'refund_amount'));
    //                             $chargeback = array_sum(array_column($userdata['userchargeback'], 'chargeback_amount'));
    //                             $purchase = array_sum(array_column($userdata['userpurchase'], 'purchase_amount'));
    //                             $reversal = array_sum(array_column($userdata['userreversal'], 'reversal_amount'));

    //                             if($target > 0){
    //                                 $achievd = ($grosssum/$target) * 100;
    //                             }
    //                             else{
    //                                 $achievd = 0;
    //                             }


    //                             // dd($this->getTotalWorkingDays(date('m'),date('Y'))   , $this->getCurrentWorkingDay(date('Y-m-d')));

    //                             $should_be_at = ($target/$this->getTotalWorkingDays(date('m'),date('Y'))) *  $this->getWorkdays(date('Y')."-".date('M')."-01", date('Y-M-d'));
    //                             $net = $grosssum + $reversal - $refunds - $chargeback - $purchase;


    //                         } 
    //                         else if (!isset($month) && $lead_year == date('Y')) {
    //                             $grosssum =  array_sum(array_column($userdata['userleads'], 'gross'));
    //                             $target = array_sum(array_column($userdata['user_target'], 'target'));
    //                             $refunds = array_sum(array_column($userdata['userrefunds'], 'refund_amount'));
    //                             $chargeback = array_sum(array_column($userdata['userchargeback'], 'chargeback_amount'));
    //                             $purchase = array_sum(array_column($userdata['userpurchase'], 'purchase_amount'));
    //                             $reversal = array_sum(array_column($userdata['userreversal'], 'reversal_amount'));
    //                              if($target > 0){
    //                                 $achievd = ($grosssum/$target) * 100;
    //                             }
    //                             else{
    //                                 $achievd = 0;
    //                             }
    //                               $should_be_at = ($target/$this->getTotalWorkingDays(date('m'),date('Y'))) * $this->getWorkdays(date('Y')."-".date('M')."-01", date('Y-M-d'), TRUE);
    //                             $net = $grosssum + $reversal - $refunds - $chargeback - $purchase;


    //                         }
    //                     }


    //                 }



    //             $total += $net; 
    //             $userreportarray[] = [
    //                                 "unit_name" =>$unit_name,
    //                                 "user_name" => $userdata['name'],
    //                                 "target"=>$target,
    //                                 "gross_sum" => $grosssum,
    //                                 "reversal" => $reversal,
    //                                 "refunds" => $refunds,
    //                                 "chargeback" => $chargeback,
    //                                 "purchase" => $purchase,
    //                                 "net" => $net,
    //                                 "achived"=>number_format($achievd, 2, '.', ''). '%',
    //                                 "should_be_at"=>"$".round($should_be_at,2)
    //                             ];    
    //             }







    //             }
    //         }



    //     // if(count($userreportarray) > 0)
    //     // {
    //     //     array_push($userreportarray,["total"=>$total]);
    //     // }




    //     return response()->json(['status' => true, 'msg' => "reprot get success", 'data' => $userreportarray,'total'=>$total]);
    // }

    function unitsMigrator($users){
        
        if($users){
            foreach($users as $user){
                $units = ($user->unit_id !== '' && $user->unit_id !== '[]') ? json_decode($user->unit_id, true) : [];
                                                
                if(!empty($units)){
                    foreach($units as $unit){

                        $newUnit = new UserUnits();
                        $newUnit->user_id = $user->id;
                        $newUnit->unit_id = intval($unit);
                        $newUnit->save(); 
                    }
                }
            }
        }

    }

    public function latest_unit_sheets_get(Request $req){

        $unit_id = json_decode($this->unitsJsonConverterInt($req->unit_id));      
        $month = (isset($req->month))? intval($req->month): null;
        $year = (isset($req->year))? intval($req->year): date('Y');

        $users = null;

        $user_id = auth()->user()->id;

        if(!empty($unit_id)){

            $total = 0;

            $data = [];


            foreach($unit_id as $unitId){

                $unitData = Units::where('id', $unitId)->first();

                if (auth()->user()->user_role == 4 && auth()->user()->permission == 3 ) {
                    // Sub Executive 
                    $users = UserUnits::with('user', 'unit')->where('unit_id', $unitId)->where('user_id', $user_id)->get()->toArray(); 
                
                } else {
                    $users = UserUnits::with('user', 'unit')->where('unit_id', $unitId)->get()->toArray();
                }
                
                // dd($users);
                
                if($users !== null){
        
                    foreach($users as $user){
                
                        
                        // var_dump("<pre>");
                        // var_dump($user);
                        // var_dump("</pre>"); 
           
        
        
                        $start = Carbon::createFromFormat('Y-m', $year. '-'. $month)->firstOfMonth()
                        ->format('Y-m-d');
                        
                        $end = Carbon::createFromFormat('Y-m', $year. '-' . $month)
                        ->endOfMonth()
                        ->format('Y-m-d');
                        
                               
                        if(($user['user']['leave_date'] !== null && strtotime($user['user']['leave_date']) < strtotime($end)) || ($user['user']['join_date'] !== null && strtotime($user['user']['join_date']) > strtotime($end)) || ($user['user']['user_role'] ==1) || ($user['user']['user_role'] ==2) || ($user['user']['show_reports']==0 && auth()->user()->user_role!==1)){
                            continue;
                        }
                        
            
                        
                        // dd($month);
                        $target = UserTargetsModel::where('user_id', $user['user']['id'])->where('month', $month)->where('year', $year)->first('target');


                        if($target){
                            $target = $target->target;
                        }
                        else{
                            $target = 0;
                        }

                        // dd($target);

                        $gross = Leads::where('sales_rep', $user['user']['id'])->whereBetween('date', [$start, $end] )->sum('gross');

                        $refunds = RefundModel::where('refund_user_id', $user['user']['id'])->whereBetween('refund_date', [$start, $end] )->sum('refund_amount');

                        $chargeBack = ChargeBackModel::where('chargeback_user_id', $user['user']['id'])->whereBetween('chargeback_date', [$start, $end] )->sum('chargeback_amount');
                        
                        // if($user['user']['id'] == 8){
                        //     dd($chargeBack);
                        // }

                        $purchase = PurchaseModel::where('purchase_user_id', $user['user']['id'])->whereBetween('purchase_date', [$start, $end] )->sum('purchase_amount');

                        $reversal = ReversalModel::where('reversal_user_id', $user['user']['id'])->whereBetween('reversal_date', [$start, $end] )->sum('reversal_amount');
                        
                        $net = $gross + $reversal - $refunds - $chargeBack - $purchase;
                        
                        if ($target > 0) {
                            $achievd = ($net / $target) * 100;
                        } else {
                            $achievd = 0;
                        }
                        
                        $should_be_at = ($target / $this->getTotalWorkingDays($month, $year)) *  $this->getWorkdays($year . "-" . $month. "-01", $end);

                        $total+= $net;
                
                        $data[] = [
                            'unit_name' => $unitData->name,
                            'user_name' => $user['user']['name'],
                            "target"=> $target,
                            "gross_sum"=> $gross, 
                            "reversal"=> $reversal,  
                            "refunds"=> $refunds, 
                            "chargeback"=> $chargeBack, 
                            "purchase"=> $purchase, 
                            "net"=> $net,
                            "achived"=> round($achievd,2)."%",
                            "should_be_at"=> "$" . number_format($should_be_at,2,'.')
                            ];
                    }
        
                }
        
            }

            return response()->json(['status' => true, 'msg' => "reprot get success", 'data' => $data, 'total' => $total]);
        }
                
   


    }
    
     public function get_all_units_report(Request $req){
         
        $month = (isset($req->month))? intval($req->month): null;
        $year = (isset($req->year))? intval($req->year): date('Y');
        
        if(auth()->user()->user_role==1)
        {
            $units = Units::get();
        }
        else
        {
            $myUnits = UserUnits::where('user_id',auth()->user()->id)->get();
            $unit_ids = [];
            foreach($myUnits as $u)
            {
                $unit_ids[] = $u->unit_id;
            }
            
            $units = Units::whereIn('id',$unit_ids)->get();
        }
          

        $users = null;

        $user_id = auth()->user()->id;

        if($units){
 
            $total = 0;

            $data = [];

            foreach($units as $unit){
       
                        $start = Carbon::createFromFormat('Y-m', $year. '-'. $month)->firstOfMonth()
                        ->format('Y-m-d');
                        
                        $end = Carbon::createFromFormat('Y-m', $year. '-' . $month)
                        ->endOfMonth()
                        ->format('Y-m-d');

                        $gross = Leads::where('unit_id', $unit->id)->whereBetween('date', [$start, $end] )->sum('gross');

                        $refunds = RefundModel::where('unit_id', $unit->id)->whereBetween('refund_date', [$start, $end] )->sum('refund_amount');

                        $chargeBack = ChargeBackModel::where('unit_id', $unit->id)->whereBetween('chargeback_date', [$start, $end] )->sum('chargeback_amount');

                        $purchase = PurchaseModel::where('unit_id', $unit->id)->whereBetween('purchase_date', [$start, $end] )->sum('purchase_amount');

                        $reversal = ReversalModel::where('unit_id', $unit->id)->whereBetween('reversal_date', [$start, $end] )->sum('reversal_amount');

                        $total+= $gross + $reversal - $refunds - $chargeBack - $purchase;

                        $data[] = [
                            'unit_name' => $unit->name,
                            "net"=> $gross + $reversal - $refunds - $chargeBack - $purchase,
                            ];
                    
        
        
            }

            return response()->json(['status' => true, 'msg' => "reprot get success", 'data' => $data, 'grand_total' => $total]);
        }
                
    }
    
     public function unit_sheets_get(Request $req)
    {

        $unit_id = json_decode($this->unitsJsonConverter($req->unit_id));

        // dd($unit_name);

        $month = $req->month;
        $year = $req->year;

        if (auth()->user()->user_role == 4) {
            $users = User::with(
                'user_target',
                'userleads',
                'userrefunds',
                'userchargeback',
                'userpurchase',
                'userreversal'
            )->where('user_role', '!=', '1')->where('id', auth()->user()->id)->where('status', 1)->get()->toArray();
        } else {
            $users = User::with(
                'user_target',
                'userleads',
                'userrefunds',
                'userchargeback',
                'userpurchase',
                'userreversal'
            )->where('user_role', '!=', '1')->where('status', 1)->get()->toArray();
        }


        $total = 0;
        $userreportarray = [];


        if ($req->unit_id != "[]" && isset($req->unit_id) && $req->unit_id != "undefined") {

            $unit_ids = $unit_id;
            asort($unit_ids);
            foreach ($unit_ids as $unit_id) {

                $unit_name = Units::where('id', $unit_id)->first()->name;
                foreach ($users as $userDataKey => $userdata) {



                    $userUnits = json_decode($userdata['unit_id'], true);

                    foreach ($userUnits as $userUnitKey => $userUnit) {
                        $userUnits[$userUnitKey] = intval($userUnit);
                    }

                    // $unitUsers = array_intersect($req->unit_id, $userUnits);



                    if (in_array(intval($unit_id), $userUnits)) {

                        $grosssum = 0;

                        $target = 0;
                        $refunds = 0;
                        $chargeback = 0;
                        $purchase = 0;
                        $reversal = 0;
                        $net = 0;
                        $achievd = 0;
                        $should_be_at = 0;


                        if (!empty($userdata['userleads'])) {
                            foreach ($userdata['userleads'] as $item) {
                                $leads = Leads::where('code', $item['code'])->first();
                                // $item['gross'] = $this->getCurrentLeadAmount($leads);
                                if ($item['unit_id'] == intval($unit_id)) {
                                    if ($month != 'Null' && isset($month) && $month == date('n', strtotime($item['date'])) && date('Y') == date('Y', strtotime($item['date']))) {
                                        $grosssum += $item['gross'];
                                    } elseif ($month != 'Null' && isset($month) && $year != 'Null' && isset($year) && $month == date('n', strtotime($item['date'])) && $year == date('Y', strtotime($item['date']))) {
                                        $grosssum += $item['gross'];
                                    }
                                }
                            }
                        }


                        if (!empty($userdata['user_target'])) {
                            foreach ($userdata['user_target'] as $item) {
                                if ($item['unit_id'] == intval($unit_id)) {
                                    if ($month != 'Null' && isset($month) && $month == $item['month'] && date('Y') == $item['year']) {
                                        $target += $item['target'];
                                    } elseif ($month != 'Null' && isset($month) && $year != 'Null' && isset($year) && $month == $item['month'] && $year == $item['year']) {
                                        $target += $item['target'];
                                    }
                                }
                            }
                        }




                        if (!empty($userdata['userrefunds'])) {
                            foreach ($userdata['userrefunds'] as $item) {
                                if ($item['unit_id'] == intval($unit_id)) {
                                    if ($month != 'Null' && isset($month) && $month == date('n', strtotime($item['refund_date'])) && date('Y') == date('Y', strtotime($item['refund_date']))) {
                                        $refunds += $item['refund_amount'];
                                    } else if ($month != 'Null' && isset($month) && $year != 'Null' && isset($year) && $month == date('n', strtotime($item['refund_date'])) && $year == date('Y', strtotime($item['refund_date']))) {
                                        $refunds += $item['refund_amount'];
                                    }
                                }
                            }
                        }


                        if (!empty($userdata['userchargeback'])) {
                            foreach ($userdata['userchargeback'] as $item) {
                                //   dd($item);
                                if ($item['unit_id'] == intval($unit_id)) {
                                    if ($month != 'Null' && isset($month) && $month == date('n', strtotime($item['chargeback_date'])) && date('Y') == date('Y', strtotime($item['chargeback_date']))) {
                                        $chargeback += $item['chargeback_amount'];
                                    } else if ($month != 'Null' && isset($month) && $year != 'Null' && isset($year) && $month == date('n', strtotime($item['chargeback_date'])) && $year == date('Y', strtotime($item['chargeback_date']))) {
                                        $chargeback += $item['chargeback_amount'];
                                    }
                                }
                            }
                        }


                        if (!empty($userdata['userpurchase'])) {
                            foreach ($userdata['userpurchase'] as $item) {
                                if ($item['unit_id'] == intval($unit_id)) {


                                    if ($month !== 'Null' && isset($month) && $month == date('n', strtotime($item['purchase_date'])) && date('Y') == date('Y', strtotime($item['purchase_date']))) {
                                        $purchase += $item['purchase_amount'];
                                    } else if ($month != 'Null' && isset($month) && $year != 'Null' && isset($year) && $month == date('n', strtotime($item['purchase_date'])) && $year == date('Y', strtotime($item['purchase_date']))) {
                                        $purchase += $item['purchase_amount'];
                                    }
                                }
                            }
                        }


                        if (!empty($userdata['userreversal'])) {
                            foreach ($userdata['userreversal'] as $item) {
                                if ($item['unit_id'] == intval($unit_id)) {
                                    if ($month != 'Null' && isset($month) && $month == date('n', strtotime($item['reversal_date'])) && date('Y') == date('Y', strtotime($item['reversal_date']))) {
                                        $reversal += $item['reversal_amount'];
                                    } else if ($month != 'Null' && isset($month) && $year != 'Null' && isset($year) && $month == date('n', strtotime($item['reversal_date'])) && $year == date('Y', strtotime($item['reversal_date']))) {
                                        $reversal += $item['reversal_amount'];
                                    }
                                }
                            }
                        }

                        if ($target > 0) {
                            $achievd = ($grosssum / $target) * 100;
                        } else {
                            $achievd = 0;
                        }

                        if ($month != 'Null' && isset($month)) {
                            $should_be_at = ($target / $this->getTotalWorkingDays(date('m'), date('Y'))) *  $this->getWorkdays(date('Y') . "-" . date('M') . "-01", date('Y-M-d'));
                        } elseif (isset($year) && $year) {
                            $should_be_at = ($target / $this->getTotalWorkingDays(date('m'), $year)) *  $this->getWorkdays($year . "-" . date('M') . "-01", date('Y-M-d'));
                        }

                        // $should_be_at = ($target / $this->getTotalWorkingDays(date('m'), date('Y'))) *  $this->getWorkdays(date('Y') . "-" . date('M') . "-01", date('Y-M-d'));
                        $net = $grosssum + $reversal - $refunds - $chargeback - $purchase;





                        $total += $net;
                        $userreportarray[] = [
                            "unit_name" => $unit_name,
                            "user_name" => $userdata['name'],
                            "target" => $target,
                            "gross_sum" => $grosssum,
                            "reversal" => $reversal,
                            "refunds" => $refunds + $chargeback,
                            "chargeback" => $chargeback,
                            "purchase" => $purchase,
                            "net" => $net,
                            "achived" => number_format($achievd, 2, '.', '') . '%',
                            "should_be_at" => "$" . round($should_be_at, 2)
                        ];
                    }
                }
            }
        } else {
            $userreportarray = [];
            $units = Units::all();
            foreach ($units as $unit) {
                foreach ($users as $userDataKey => $userdata) {
                    $grosssum = 0;

                    $target = 0;
                    $refunds = 0;
                    $chargeback = 0;
                    $purchase = 0;
                    $reversal = 0;
                    $net = 0;
                    $achievd = 0;
                    $should_be_at = 0;



                    $userUnits = json_decode($userdata['unit_id'], true);

                    foreach ($userUnits as $userUnitKey => $userUnit) {
                        $userUnits[$userUnitKey] = intval($userUnit);
                    }

                    // dd(array_intersect($req->unit_id, $userUnits),$req->unit_id,$userUnits);

                    if (in_array(intval($unit->id), $userUnits)) {


                        if (!empty($userdata['userleads'])) {
                            foreach ($userdata['userleads'] as $item) {
                                $leads = Leads::where('code', $item['code'])->first();
                                // $item['gross'] = $this->getCurrentLeadAmount($leads);
                                if ($item['unit_id'] == intval($unit->id)) {
                                    if ($month != 'Null' && isset($month) && $month == date('n', strtotime($item['created_at']))) {
                                        $grosssum += $item['gross'];
                                    } elseif (isset($year) && $year != "Null" && $year == date('Y', strtotime($item['created_at']))) {
                                        $grosssum += $item['gross'];
                                    }
                                }
                            }
                        }


                        if (!empty($userdata['user_target'])) {
                            foreach ($userdata['user_target'] as $item) {
                                if ($item['unit_id'] == intval($unit->id)) {
                                    if ($month != 'Null' && isset($month) && $month == $item['month']) {
                                        $target += $item['target'];
                                    } elseif (isset($year) && $year != "Null" && $year == $item['year']) {
                                        $target += $item['target'];
                                    }
                                }
                            }
                        }




                        if (!empty($userdata['userrefunds'])) {
                            foreach ($userdata['userrefunds'] as $item) {
                                if ($item['unit_id'] == intval($unit->id)) {
                                    if ($month != 'Null' && isset($month) && $month == date('n', strtotime($item['created_at']))) {
                                        $refunds += $item['refund_amount'];
                                    } elseif (isset($year) && $year != "Null" && $year == date('Y', strtotime($item['created_at']))) {
                                        $refunds += $item['refund_amount'];
                                    }
                                }
                            }
                        }


                        if (!empty($userdata['userchargeback'])) {
                            foreach ($userdata['userchargeback'] as $item) {
                                if ($item['unit_id'] == intval($unit->id)) {
                                    if ($month != 'Null' && isset($month) && $month == date('n', strtotime($item['created_at']))) {
                                        $chargeback += $item['chargeback_amount'];
                                    } elseif (isset($year) && $year != "Null" && $year == date('Y', strtotime($item['created_at']))) {
                                        $chargeback += $item['chargeback_amount'];
                                    }
                                }
                            }
                        }


                        if (!empty($userdata['userpurchase'])) {
                            foreach ($userdata['userpurchase'] as $item) {
                                if ($item['unit_id'] == intval($unit->id)) {
                                    if ($month != 'Null' && isset($month) && $month == date('n', strtotime($item['created_at']))) {
                                        $purchase += $item['purchase_amount'];
                                    } elseif (isset($year) && $year != "Null" && $year == date('Y', strtotime($item['created_at']))) {
                                        $purchase += $item['purchase_amount'];
                                    }
                                }
                            }
                        }


                        if (!empty($userdata['userreversal'])) {
                            foreach ($userdata['userreversal'] as $item) {
                                if ($item['unit_id'] == intval($unit->id)) {
                                    if ($month != 'Null' && isset($month) && $month == date('n', strtotime($item['created_at']))) {
                                        $reversal += $item['reversal_amount'];
                                    } elseif (isset($year) && $year != "Null" && $year == date('Y', strtotime($item['created_at']))) {
                                        $reversal += $item['reversal_amount'];
                                    }
                                }
                            }
                        }

                        if ($target > 0) {
                            $achievd = ($grosssum / $target) * 100;
                        } else {
                            $achievd = 0;
                        }


                        if ($month != 'Null' && isset($month)) {
                            $should_be_at = ($target / $this->getTotalWorkingDays(date('m'), date('Y'))) *  $this->getWorkdays(date('Y') . "-" . date('M') . "-01", date('Y-M-d'));
                        } elseif (isset($year) && $year) {
                            $should_be_at = ($target / $this->getTotalWorkingDays(date('m'), $year)) *  $this->getWorkdays($year . "-" . date('M') . "-01", date('Y-M-d'));
                        }


                        $net = $grosssum + $reversal - $refunds - $chargeback - $purchase;

                        $total += $net;
                        $userreportarray[] = [
                            "unit_name" => $unit->name,
                            "user_name" => $userdata['name'],
                            "target" => $target,
                            "gross_sum" => $grosssum,
                            "reversal" => $reversal,
                            "refunds" => $refunds + $chargeback,
                            "chargeback" => $chargeback,
                            "purchase" => $purchase,
                            "net" => $net,
                            "achived" => number_format($achievd, 2, '.', '') . '%',
                            "should_be_at" => "$" . round($should_be_at, 2)
                        ];
                    }

                    // dd($userreportarray);





                }
            }
        }



        // if(count($userreportarray) > 0)
        // {
        //     array_push($userreportarray,["total"=>$total]);
        // }




        return response()->json(['status' => true, 'msg' => "reprot get success", 'data' => $userreportarray, 'total' => $total]);
    }
    public function unit_sheets_get_users(Request $req)
    {

        $user_name = $req->user_name;

        $users = User::with(
            'user_target',
            'userleads',
            'userrefunds',
            'userchargeback',
            'userpurchase',
            'userreversal'
        )->where('user_role', '!=', '1')->Where('name', $user_name)->get()->toArray();


        $userreportarray = [];


        $total = 0;
        foreach ($users as $userDataKey => $userdata) {
 
            $userUnits = json_decode($userdata['unit_id'], true);



            $date1 = new DateTime($userdata['join_date']);
            $date2 = ($userdata['leave_date'] == null) ?  date('Y-m-d') : $userdata['leave_date'];
            $date2 = new DateTime($date2);
            
            
            
            
            $date2->modify('first day of next month');
            $interval = new DateInterval('P1M');
            $period = new DatePeriod($date1, $interval, $date2);

            $months = array();
            $months_string = array();
            $years = array();

            foreach ($period as $date) {
                $months[] = $date->format('m');
                $months_string[] = $date->format('M');
                $years[] = $date->format('Y');
            }
          
            $date1 = $date1->format('Y-m-d');
            $date2 = $date2->format('Y-m-d');
            // $start = Carbon::createFromFormat('Y-m', $year. '-'. $month)->firstOfMonth()
            //             ->format('Y-m-d');
                        
                        
                
                //for sum of each row
                $grosssum_total =0;
                $target_total = 0;
                $refunds_total = 0;
                $chargeback_total = 0;
                $purchase_total = 0;
                $reversal_total = 0;
                $net_total = 0;
                $target_total = 0;
                

            foreach ($months as $key => $month) {
              
            //My Code
            
                if(strtotime($years[$key]."-".$month) == strtotime(date('Y-m')))
                {
                    $end = date('Y-m-d');
                }
                else
                {
                    // dd($date1);
                   $end = date("Y-m-t", strtotime($years[$key]."-".$month)); 
                }
                        
                
            //End
                
                
                $month = intval($month);
                $grosssum = 0;
                $target = 0;
                $refunds = 0;
                $chargeback = 0;
                $purchase = 0;
                $reversal = 0;
                $net = 0;
                $achievd = 0;
                $should_be_at = 0;
             
                if (!empty($userdata['userleads'])) {
                    foreach ($userdata['userleads'] as $item) {
                        $leads = Leads::where('code', $item['code'])->first();
                        // $item['gross'] = $this->getCurrentLeadAmount($leads);
                        if (in_array($item['unit_id'], $userUnits)) {
                            if ($month != 'Null' && isset($month) && ($month == date('n', strtotime($item['date'])) && $years[$key] == date('Y', strtotime($item['date'])))) {
                                $grosssum += $item['gross'];
                                $grosssum_total+= $item['gross'];
                               
                            }
                        }
                    }
                }


                if (!empty($userdata['user_target'])) {
                    foreach ($userdata['user_target'] as $item) {
                        if (in_array($item['unit_id'], $userUnits)) {
                            if ($month != 'Null' && isset($month) && ($month == $item['month'] && $years[$key] == $item['year'])) {
                                $target += $item['target'];
                                $target_total+= $item['target'];
                            }
                        }
                    }
                }




                if (!empty($userdata['userrefunds'])) {
                    foreach ($userdata['userrefunds'] as $item) {
                        if (in_array($item['unit_id'], $userUnits)) {
                            if ($month != 'Null' && isset($month) && ($month == date('n', strtotime($item['refund_date'])) && $years[$key] == date('Y', strtotime($item['refund_date'])))) {
                                $refunds += $item['refund_amount'];
                                $refunds_total += $item['refund_amount'];
                                
                            }
                        }
                    }
                }


                if (!empty($userdata['userchargeback'])) {
                    foreach ($userdata['userchargeback'] as $item) {
                        //   dd($item);
                        if (in_array($item['unit_id'], $userUnits)) {
                            if ($month != 'Null' && isset($month) && ($month == date('n', strtotime($item['chargeback_date'])) && $years[$key] == date('Y', strtotime($item['chargeback_date'])))) {
                                $chargeback += $item['chargeback_amount'];
                                $chargeback_total += $item['chargeback_amount'];
                                
                            }
                        }
                    }
                }


                if (!empty($userdata['userpurchase'])) {
                    foreach ($userdata['userpurchase'] as $item) {
                        if (in_array($item['unit_id'], $userUnits)) {


                            if ($month !== 'Null' && isset($month) && ($month == date('n', strtotime($item['purchase_date'])) && $years[$key] == date('Y', strtotime($item['purchase_date'])))) {
                                $purchase += $item['purchase_amount'];
                                $purchase_total += $item['purchase_amount'];
                                
                            }
                        }
                    }
                }


                if (!empty($userdata['userreversal'])) {
                    foreach ($userdata['userreversal'] as $item) {
                        if (in_array($item['unit_id'], $userUnits)) {
                            if ($month != 'Null' && isset($month) && ($month == date('n', strtotime($item['reversal_date'])) && $years[$key] == date('Y', strtotime($item['reversal_date'])))) {
                                $reversal += $item['reversal_amount'];
                                $reversal_total += $item['reversal_amount'];
                                
                            }
                        }
                    }
                }

                if ($target > 0) {
                    $achievd = ($grosssum / $target) * 100;
                } else {
                    $achievd = 0;
                }
                
                
                //  dd($target);
             
                
                 
                // dd($months,$month,$date2,$this->getWorkdays(intval($years[$key]) . "-" . $month . "-01", $date2));
                $should_be_at = ($target / $this->getTotalWorkingDays($month, intval($years[$key]))) *  $this->getWorkdays(intval($years[$key]) . "-" . $month . "-01", $end);


                $net = $grosssum + $reversal - $refunds - $chargeback - $purchase;
                $net_total += $grosssum + $reversal - $refunds - $chargeback - $purchase;

                $total += $net;

                $userreportarray[] = [
                    "date" => $months_string[$key] . " " . $years[$key],
                    "target" => $target,
                    "gross_sum" => $grosssum,
                    "reversal" => $reversal,
                    "refunds" => $refunds + $chargeback,
                    "chargeback" => $chargeback,
                    "purchase" => $purchase,
                    "net" => $net,
                    "achived" => number_format($achievd, 2, '.', '') . '%',
                    "should_be_at" => "$" . round($should_be_at, 2),
                    "end"=>$end
                ];
            }
        }

                
                $total_all=[[
                    'target_total'=> $target_total,
                    'gross_sum_total'=>$grosssum_total,
                    'refunds_total'=> $refunds_total,
                    'reversal_total'=>$reversal_total,
                    'purchase_total'=> $purchase_total,
                    'chargeback_total'=>$chargeback_total,
                    'net_total'=>$net_total
                    
                    ]];



        return response()->json(['status' => true, 'msg' => "reprot get success", 'data' => $userreportarray, 'total' => $total,'total_all'=>$total_all]);
    }
    public function userlisting()
    {
        $user = "";

            
        
        if ((auth()->user()->role->id == 3 && auth()->user()->permission == null) || (auth()->user()->role->id == 4 && auth()->user()->permission == 1)) {
            // $units= json_decode(auth()->user()->unit_id);
            
            $buhUserUnits = UserUnits::where('user_id',auth()->user()->id)->get()->toArray();
            
            
            
            $buhUsers= [];
            
            
            $buhUnits = [];
            
            if(!empty($buhUserUnits)){
                $buhUnits = array_column($buhUserUnits, 'unit_id');
                
                $getSpecificUsers = UserUnits::whereIn('unit_id',$buhUnits)->get()->toArray();
                
                $buhUsers = [];
                     
                if(!empty($getSpecificUsers)){
                    $buhUsers = array_unique(array_column($getSpecificUsers, 'user_id'));
                }
                
                $user = User::whereIn('id', $buhUsers)->with('role')->where('user_role', "!=", 1)->where('user_role', "!=", 2)->where('user_role', "!=", 3)->get();
                foreach ($user as $key => $item) {
                $units = [];
    
    
                $unitItems = json_decode($item->unit_id);
                for ($i = 0; $i < count($unitItems); $i++) {
                    $unit = Units::where('id', $unitItems[$i])->first();
                    if ($unit) {
    
                        $units[] = ['id' => $unitItems[$i], 'name' => $unit->name];
                    }
                }
    
                $user[$key]['unit_id'] = $units;
            }
            }
            
            
      
        } else {
            $user = User::with('role')->where('user_role', "!=", 1)->get();
              foreach ($user as $key => $item) {
                $units = [];
    
    
                $unitItems = json_decode($item->unit_id);
                for ($i = 0; $i < count($unitItems); $i++) {
                    $unit = Units::where('id', $unitItems[$i])->first();
                    if ($unit) {
    
                        $units[] = ['id' => $unitItems[$i], 'name' => $unit->name];
                    }
                }
    
                $user[$key]['unit_id'] = $units;
            }

        }



      
        // $user = $user->toArray();
        return response()->json(['status' => true, 'users' => $user, 'permission' => $this->user_permission(auth()->user())]);
    }
    public function user_delete($user)
    {
        // User::where('id',$user)->update([
        //         'status' => 0
        //     ]);

        $user = User::where('id', $user)->first();

        if ($user) {
            $user->status = ($user->status == 1) ? 0 : 1;
            $user->save();

            return response()->json(['status' => true, 'msg' => "User Delete Success", 'data' => $user]);
        } else {
            return response()->json(['status' => true, 'msg' => "User Didn't Exists Or Can't Be Remove! "]);
        }
    }
    public function useradd(Request $request, User $user)
    {
        $msg = isset($user->id) ? 'Updated Succesfully' : 'Added Succesfully';
        if (isset($user->id)) {
            $validator = Validator::make($request->all(), [
                'name' => 'string|max:255',
                'user_role' => 'required',
                'unit_id' => 'required',
                'email' => 'required',
            ]);
        } else {
            if ($request->user_role == 2 || $request->user_role == 4) {
                $validator = Validator::make($request->all(), [
                    'name' => 'string|max:255',
                    'user_role' => 'required',
                    'unit_id' => 'required',
                    'email' => 'required|unique:users',
                    'permission' => 'required',
                    'password' => 'required',
                    'join_date' => 'required',
                ]);
            } else {
                $validator = Validator::make($request->all(), [
                    'name' => 'string|max:255',
                    'user_role' => 'required',
                    'unit_id' => 'required',
                    'email' => 'required|unique:users',
                    'password' => 'required',
                    'join_date' => 'required',
                ]);
            }
        }
        if ($validator->fails()) {
            $errors = $validator->errors()->toArray();
            return response()->json(['status' => false, 'errors' => $errors], 400);
        }

        // return response()->json(['data'=>$request->unit_id]);

        $user->name = $request->name;
        $user->email = $request->email;
        $user->show_reports= isset($request->show_reports)?$request->show_reports:1;
        $user->password = !empty($request->password) ?  Hash::make($request->password) :  $user->password;
        if ($request->user_role == 1 || $request->user_role == 3) {
            $user->permission = Null;
        } else {
            $user->permission = ($request->permission == "null") ? Null : $request->permission;
        }
        $unitsJson = $request->unit_id;
        $user->join_date = $request->join_date;
        $user->leave_date = (isset($request->leave_date) && $request->leave_date != "null") ? $request->leave_date : null;
        $user->unit_id = $this->unitsJsonConverter($unitsJson);

        $user->user_role = $request->user_role;

        if ($user->save()) {

            UserUnits::where( 'user_id', $user->id )->delete();

            // dd($unitsJson);
            $unitsJson = stripslashes($unitsJson);
                

            if($unitsJson !== ''){

                
                $unitsJson = json_decode($unitsJson, true);

                // dd($unitsJson);

                $unitIds = array_map('strval', array_column($unitsJson, 'value'));

                // dd($unitIds);

                if(!empty($unitIds)){
                    foreach($unitIds as $singleUnit){
                    
                        $unitId = intval($singleUnit);
    
                        $newUnit = new UserUnits();
                        $newUnit->user_id = $user->id;
                        $newUnit->unit_id = $unitId;
                        $newUnit->save();
    
                    }
                }              

            }

            return response()->json(['status' => true, 'msg' => $msg, 'user' => $user]);
        } else {
            return response()->json(['status' => false, 'msg' => 'Error']);
        }
    }

    public function specificleads(Request $request, $leads)
    {
        $leads = Leads::with('getsource', 'unitdetail', 'accountrepdetail', 'salesrep', 'accountrep', 'getbrand')->where('code', $leads)->first();
        if(auth()->user()->user_role==3 || auth()->user()->user_role==4)
        {
            $lead_unit = intval($leads->unit_id);
            $userUnits = UserUnits::where('user_id',auth()->user()->id)->get();
            $check = 0;
            foreach($userUnits as $unit)
            {
                if($unit->unit_id==$lead_unit)
                {
                    $check=1;
                }
            }
            if($check==0)
            {
                return response()->json(['status'=>false,'msg'=>'This Lead donot belong to your unit!']);
            }
        }
        
        
      

        if ($leads) {
            $leads->gross = $this->getCurrentLeadAmount($leads);
            $leads->date = $leads->date;
            $l = $leads->toArray();
            return response()->json(['status' => true, 'leads' => $l, 'permission' => $this->user_permission(auth()->user())]);
        } else {
            return response()->json(['status' => false, 'msg' => "Incorrect Lead Code Or Didn't Exist!"]);
        }
    }
    public function getCurrentLeadAmount($leadCode)
    {

        $chargeBack = ChargeBackModel::where('lead_code', strtolower($leadCode->code))->get()->toArray();
        $refund = RefundModel::where('lead_code', strtolower($leadCode->code))->get()->toArray();
        $purchase = PurchaseModel::where('lead_code', strtolower($leadCode->code))->get()->toArray();
        $reversal = ReversalModel::where('lead_code', strtolower($leadCode->code))->get()->toArray();


        $leadAmount = $leadCode->gross; //Lead Amount
        $chargeBackAmount = (count($chargeBack) > 0) ? array_sum(array_column($chargeBack, 'chargeback_amount')) : 0; //Lead ChargeBack Amount
        $RefundAmount = (count($refund) > 0) ? array_sum(array_column($refund, 'refund_amount')) : 0; //Refund Amount
        $PurchaseAmount = (count($purchase) > 0) ? array_sum(array_column($purchase, 'purchase_amount')) : 0; //Not Now
        $reversalAmount = (count($reversal) > 0) ? array_sum(array_column($reversal, 'reversal_amount')) : 0;
        
        // return floatval($leadAmount - $chargeBackAmount - $RefundAmount - $reversalAmount - $PurchaseAmount);
        return floatval($leadAmount  + $reversalAmount - $RefundAmount - $chargeBackAmount - $PurchaseAmount);
    }
    public function specificuserdata(Request $request, User $user)
    {
        $u = User::with('role')->where('id', $user->id)->first()->toArray();
        $user = User::with('role')->where('id', $user->id)->first()->toArray();
        $u['unit_id'] = json_decode($u['unit_id']);
        foreach ($u['unit_id'] as $key => $data) {
            $unit =  Units::where('id', $data)->first();
            $u['unit_id'][$key] = ["value" => $data, 'label' => $unit->name];
            $u['currently_working_check'] = $user['leave_date'] == null ? true : false;
        }

        return response()->json(['status' => true, 'users' => $u, 'permission' => $this->user_permission(auth()->user())]);
    }
    public function specificbrand(Request $request, Brands $brand)
    {
        $b = $brand->toArray();
        return response()->json(['status' => true, 'brands' => $b, 'permission' => $this->user_permission(auth()->user())]);
    }
    //Roles 
    public function rolelisting()
    {
        $roles = "";

        if (auth()->user()->role->id == 4 && auth()->user()->permission !== null && auth()->user()->permission == 2) {
            $roles = Roles::where('name', '!=', "Admin")->where('name', '!=', "QA")->where('id', '!=', 3)->get();
        } else if ((auth()->user()->role->id == 3 && auth()->user()->permission == null) || (auth()->user()->role->id == 4 && auth()->user()->permission == 1)) {
            // $units= json_decode(auth()->user()->unit_id);
            $roles = Roles::where('name', '!=', "Admin")->where('name', '!=', "QA")->where('id', '!=', auth()->user()->role->id)->get();
        } else if (auth()->user()->user_role == 2) {
            $roles = Roles::where('name', 'QA')->get();
        } else {
            $roles = Roles::where('name', '!=', "Admin")->get();
        }

        $role = $roles->toArray();
        return response()->json(['status' => true, 'roles' => $role, 'permission' => $this->user_permission(auth()->user())]);
    }
    public function RolesAddEdit(Request $request, Roles $role)
    {
        $msg = isset($role->id) ? 'Updated Succesfully' : 'Added Succesfully';

        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'status' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->errors()->toArray();
            return response()->json(['status' => false, 'errors' => $errors], 400);
        }
        $role->name = $request->name;
        $role->status = $request->status;
        if ($role->save()) {
            return response()->json(['status' => true, 'msg' => 'Added Successfully', 'role' => $role]);
        } else {
            return response()->json(['status' => false, 'msg' => 'Error']);
        }
    }
    public function roleview(Roles $role)
    {
        return response()->json(['status' => true, 'roles' => $role->toArray(), 'permission' => $this->user_permission(auth()->user())]);
    }

    //Units
    public function unitlisting()
    {

        if(auth()->user()->user_role==1)
        {
            $units = Units::with('unit_Brands')->get()->toArray();
        }
        else
        {
            $unitid = UserUnits::where('user_id',auth()->user()->id)->get()->toArray();
            
$unitid = array_column($unitid, 'unit_id');
            
            $units = Units::with('unit_Brands')->whereIn('id',$unitid)->get()->toArray();
        }
        

        // if (auth()->user()->role->id == 4 && auth()->user()->permission !== null && auth()->user()->permission == 2) {
        //     $unitsdata = json_decode(auth()->user()->unit_id);

        //     $units = Units::with('unit_Brands')->whereIn('id', $unitsdata)->get();
        // } else if ((auth()->user()->role->id == 3 && auth()->user()->permission == null) || (auth()->user()->role->id == 4 && auth()->user()->permission == 1)) {
        //     $unitsdata = json_decode(auth()->user()->unit_id);

        //     $units = Units::with('unit_Brands')->whereIn('id', $unitsdata)->get();
        // } else {
            
        // }

        // $units = $units->toArray();
        return response()->json(['status' => true, 'units' => $units, 'permission' => $this->user_permission(auth()->user())]);
    }
    public function unitview(Units $unit)
    {
        $brands = Units::where('id', $unit->id)->with('unit_brands')->get();
        return response()->json(['status' => true, 'unit' => $brands->toArray(), 'permission' => $this->user_permission(auth()->user())]);
    }
    public function UnitsAddEdit(Request $request, Units $unit)
    {
        $msg = isset($unit->id) ? 'Updated Succesfully' : 'Added Succesfully';
        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
        ]);
        if ($validator->fails()) {
            $errors = $validator->errors()->toArray();
            return response()->json(['status' => false, 'errors' => $errors], 400);
        }
        $unit->name = $request->name;
        $unit->status = 1;
        if ($unit->save()) {

            //Add Unit Brands Here
            $brands = UnitBrands::where('unit_id', $unit->id)->get();

            foreach ($brands as $brand) {
                $brand->delete();
            }

            foreach ($request->brands as $brand) {
                $unit_brand = new UnitBrands();
                $unit_brand->unit_id = $unit->id;
                $unit_brand->brand_id = $brand['value'];
                $unit_brand->save();
            }

            return response()->json(['status' => true, 'msg' => $msg, 'unit' => $unit]);
        } else {
            return response()->json(['status' => false, 'msg' => 'Error']);
        }
    }
    //brands
    public function brandslist()
    {
        
        if(auth()->user()->user_role==1)
        {
            $brands = Brands::get();    
        }
        else
        {
            $myUnits = UserUnits::where('user_id',auth()->user()->id)->get();
            $unit_ids = [];
            $brand_ids=[];
            foreach($myUnits as $u)
            {
                $unit_ids[] = $u->unit_id;
            }
            $unit_brands = UnitBrands::whereIn('unit_id',$unit_ids)->get();
            if(isset($unit_brands))
            {
                foreach($unit_brands as $ub)
                {
                    $brand_ids[]= $ub->brand_id;
                }
            }
            $brand_ids = array_unique($brand_ids);
            $brands = Brands::whereIn('id',$brand_ids)->get();
        }
        
        $brands = $brands->toArray();
        return response()->json(['status' => true, 'brands' => $brands, 'permission' => $this->user_permission(auth()->user())]);
    }
    // public function unitview(Units $unit)
    // {
    //     return response()->json(['status'=>true,'unit'=>$unit->toArray()]);  
    // }
   public function BrandsAddEdit(Request $request, Brands $brand)
    {
        $msg = isset($brand->id) ? 'Updated Succesfully' : 'Added Succesfully';
        if(!isset($brand->id))
        {
            $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:brands|max:255',
            // 'status' => 'required',
            ]);    
        }
        else
        {
            $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            // 'status' => 'required',
            ]);
        }
        

        if ($validator->fails()) {
            $errors = $validator->errors()->toArray();
            return response()->json(['status' => false, 'errors' => $errors], 400);
        }
        
        $brand->status= isset($request->status)? intval($request->status):1;
        $brand->name = $request->name;
        
        if ($brand->save()) {
            return response()->json(['status' => true, 'msg' => $msg, 'brand' => $brand]);
        } else {
            return response()->json(['status' => false, 'msg' => 'Error']);
        }
 
    }
    public function delete_brand(Brands $brand)
    {
        $brand->delete();

        return response()->json(['status' => true, 'msg' => "Brand Delete Success", 'permission' => $this->user_permission(auth()->user())]);
    }

    //Dashboard Table
    public function dashboardTable(Request $req)
    {
        $month = date('m');
        $monthName = date('F');
        $year = date('Y');

        $currentDay = isset($req->current_day) ?  $req->current_day : "false" ;
        
        
        $leadsData = [];
        
        $units = Units::where('status', 1)->get();
        
                
        if (auth()->user()->role->id !== 1) {
            $units = Units::whereIn('id', json_decode(auth()->user()->unit_id))->where('status',1)->get();
        }
        
        $totals = [
                    "net"=> 0,
                    "unit_target"=> 0,
                    "gross_amount"=> 0,
                    "received"=> 0,
                    "recovery"=> 0,
                    "charge_back"=> 0,
                    "reversal_amount"=>0,
                    "refund_amount"=> 0,
                    "purchase"=> 0,
                ];
        
        if($units !== null){
            foreach($units as $unit){
                
                $unit_id = $unit->id;
                
                
                if($currentDay == 'true'){
                    $start = date('Y-m-d');
                                
                    $end =  date('Y-m-d');
                }
                else{
                    $start = Carbon::createFromFormat('Y-m', $year. '-'. $month)->firstOfMonth()
                                ->format('Y-m-d');
                                
                    $end = Carbon::createFromFormat('Y-m', $year. '-' . $month)
                    ->endOfMonth()
                    ->format('Y-m-d');
                     
                }
                

                
                
                $targetData = UnitTarget::where('unit_id', $unit_id)->where('month', $month)->where('year', $year)->sum('target');
                
                $totals['unit_target']+= $targetData;
                
                $grossData = Leads::where('unit_id', $unit_id)->whereBetween('date', [$start, $end] )->sum('gross');
                
                $totals['gross_amount']+= $grossData;
                
                $recievedData = Leads::where('unit_id', $unit_id)->whereBetween('date', [$start, $end] )->sum('received');
                
                $totals['received']+= $recievedData;
                
                $recoveryData = Leads::where('unit_id', $unit_id)->whereBetween('date', [$start, $end] )->sum('recovery');
                
                $totals['recovery']+= $recoveryData;
        
                $refundsData = RefundModel::where('unit_id', $unit_id)->whereBetween('refund_date', [$start, $end] )->sum('refund_amount');
                
                $totals['refund_amount']+= $refundsData;
        
                $chargeBackData = ChargeBackModel::where('unit_id', $unit_id)->whereBetween('chargeback_date', [$start, $end] )->sum('chargeback_amount');
                
                $totals['charge_back']+= $chargeBackData;
        
                $purchaseData = PurchaseModel::where('unit_id', $unit_id)->whereBetween('purchase_date', [$start, $end] )->sum('purchase_amount');
                
                $totals['purchase']+= $purchaseData;
        
                $reversalData = ReversalModel::where('unit_id', $unit_id)->whereBetween('reversal_date', [$start, $end] )->sum('reversal_amount');
                
                $totals['reversal_amount']+= $reversalData;
                
                $totals['net']+= $grossData + $reversalData - $refundsData - $chargeBackData - $purchaseData;
        
                $leadsData[] = [
                    "unit_name" => $unit->name,
                    "net"=> $grossData + $reversalData - $refundsData - $chargeBackData - $purchaseData,
                    "unit_target"=> floatval($targetData),
                    "gross_amount"=> $grossData,
                    "received"=> $recievedData,
                    "recovery"=> $recoveryData,
                    "charge_back"=> $chargeBackData,
                    "reversal_amount"=>$reversalData,
                    "refund_amount"=> $refundsData,
                    "purchase"=> $purchaseData,
                ];           
            }
        }
        
        
        
       
        return response()->json(['status' => true, 'data' => $leadsData, 'totals'=> $totals, 'month' => $monthName,   'permission' => $this->user_permission(auth()->user())]);
    }
    
     //leads
    public function leadslist(Request $req)
    {
        $month = $req->month;
        $unit_id = $req->unit_id;
        $year = $req->year;
        
        
        if (auth()->user()->role->id == 4 && auth()->user()->permission == 3) {
     

            $leads = Leads::with('getsource', 'unitdetail', 'salesrep', 'accountrepdetail', 'getbrand')->orWhere('sales_rep', auth()->user()->id)->orWhere('account_rep', auth()->user()->id)->orderby('date',"DESC")->get();
        
                //   dd(1);
        } else if ((auth()->user()->role->id == 3 && auth()->user()->permission == null) || (auth()->user()->role->id == 4 && auth()->user()->permission == 1) || (auth()->user()->role->id == 4 && auth()->user()->permission == 2)) {
            $units = json_decode(auth()->user()->unit_id);
            $leads = Leads::with('getsource', 'unitdetail', 'salesrep', 'accountrepdetail', 'getbrand')->whereIn('unit_id', $units)->orderby('date',"DESC")->get();
                    //  dd( 2);
        }else if((auth()->user()->role->id == 2 && auth()->user()->permission == 1) || (auth()->user()->role->id == 2 && auth()->user()->permission == 2) || (auth()->user()->role->id == 2 && auth()->user()->permission == 3)){
            $units = json_decode(auth()->user()->unit_id);
            $leads = Leads::with('getsource', 'unitdetail', 'salesrep', 'accountrepdetail', 'getbrand')->whereIn('unit_id', $units)->orderby('date',"DESC")->get();
        } 
        else {
            $leads = Leads::with('getsource', 'unitdetail', 'salesrep', 'accountrepdetail', 'getbrand')->orderby('date',"DESC")->get();
                    //   dd( 3);
        }
        
        $leadsData = [];
        
        if(isset($month) && isset($year) && isset($unit_id) && $month !== 'undefined' && $year !== 'undefined' && $unit_id !== 'undefined'){
            $start = Carbon::createFromFormat('Y-m', $year. '-'. $month)->firstOfMonth()
                        ->format('Y-m-d');
                        
            $end = Carbon::createFromFormat('Y-m', $year. '-' . $month)
            ->endOfMonth()
            ->format('Y-m-d');
            
            $targetData = UnitTarget::where('unit_id', $unit_id)->where('month', $month)->where('year', $year)->sum('target');
            $grossData = Leads::where('unit_id', $unit_id)->whereBetween('date', [$start, $end] )->sum('gross');
            
            $recievedData = Leads::where('unit_id', $unit_id)->whereBetween('date', [$start, $end] )->sum('received');
            
            $recoveryData = Leads::where('unit_id', $unit_id)->whereBetween('date', [$start, $end] )->sum('recovery');
    
            $refundsData = RefundModel::where('unit_id', $unit_id)->whereBetween('refund_date', [$start, $end] )->sum('refund_amount');
    
            $chargeBackData = ChargeBackModel::where('unit_id', $unit_id)->whereBetween('chargeback_date', [$start, $end] )->sum('chargeback_amount');
    
            $purchaseData = PurchaseModel::where('unit_id', $unit_id)->whereBetween('purchase_date', [$start, $end] )->sum('purchase_amount');
    
            $reversalData = ReversalModel::where('unit_id', $unit_id)->whereBetween('reversal_date', [$start, $end] )->sum('reversal_amount');
    
            $leadsData = [
                "net"=> $grossData + $reversalData - $refundsData - $chargeBackData - $purchaseData,
                "unit_target"=> $targetData,
                "gross_amount"=> $grossData,
                "received"=> $recievedData,
                "recovery"=> $recoveryData,
                "charge_back"=> $chargeBackData,
                "reversal_amount"=>$reversalData,
                "refund_amount"=> $refundsData
            ];
            
           
        }
        
       
        

        $extra_fields = [];
        if ((isset($month) && isset($year) && isset($unit_id)) && $month !== 'undefined' && $year !== 'undefined' && $unit_id !== 'undefined') {
            $extra_fields['net'] = 0;
            $data = [];
                $received = 0;
                $recovery = 0;
                $reversalAmount = 0;
                $purchaseAmount = 0;
                $gross = 0;
                $net = 0;
                $chargeBackAmount = 0;
                $RefundAmount = 0;
                
            foreach ($leads as $key => $lead) {
                if ($month == date("m", strtotime($lead->date)) && $year == date("Y", strtotime($lead->date)) &&  $lead->unit_id == $unit_id ) {
                    $currentmonth = UnitTarget::where(['unit_id' => $unit_id, 'month' => $month])->sum('target');
                    $data[$key] = $lead;

                    $received += $lead->received;
                    $recovery += $lead->recovery;
                    $gross += $lead->gross;
                    $chargeBack = ChargeBackModel::where('lead_code', strtolower($lead->code))->whereMonth('chargeback_date', $month)->get()->toArray();
                    $refund = RefundModel::where('lead_code', strtolower($lead->code))->whereMonth('refund_date','=', $month)->get()->toArray();
                    
                    
                    
                    //  $chargeBack = ChargeBackModel::where('unit_id', $unit_id)->whereMonth('chargeback_date', $month)->get()->toArray();
                    // $refund = RefundModel::where('unit_id', $unit_id)->whereMonth('refund_date','=', $month)->get()->toArray();
                    $purchase = PurchaseModel::where('lead_code', strtolower($lead->code))->whereMonth('purchase_date', $month)->get()->toArray();
                    $reversal = ReversalModel::where('lead_code', strtolower($lead->code))->whereMonth('reversal_date', $month)->get()->toArray();
                    
                    $chargeBackAmount = (count($chargeBack) > 0) ? array_sum(array_column($chargeBack, 'chargeback_amount')) : 0; //Unit and Month wise ChargeBack Amount
                    $RefundAmount = (count($refund) > 0) ? array_sum(array_column($refund, 'refund_amount')) : 0; //Unit and Month wise Refund Amount
                    $purchaseAmount = (count($purchase) > 0) ? array_sum(array_column($purchase, 'purchase_amount')) : 0; //Unit and Month wise Purchase Amount
                    $reversalAmount = (count($reversal) > 0) ? array_sum(array_column($reversal, 'reversal_amount')) : 0; //Unit and Month wise Reversal Amount
                
                    $chargeBack = ChargeBackModel::where('lead_code', strtolower($lead->code))->get()->toArray();
                    $refund = RefundModel::where('lead_code', strtolower($lead->code))->get()->toArray();
                      
                    
                    $data[$key]['refund'] = (count($refund) > 0) ? true : false;
                    $data[$key]['chargeback'] = (count($chargeBack) > 0) ? true : false;
                    
                    $chargeBackAmount = (count($chargeBack) > 0) ? array_sum(array_column($chargeBack, 'chargeback_amount')) : 0; //Unit and Month wise ChargeBack Amount
                    $RefundAmount = (count($refund) > 0) ? array_sum(array_column($refund, 'refund_amount')) : 0; //Unit and Month wise Refund Amount
                    $extra_fields['unit_target'] = floatval($currentmonth);
                  
                }
                // $lead->gross = $this->getCurrentLeadAmount($lead);
                // $lead->date = $lead->created_at->format('jS M Y');
            }
            
            $extra_fields['net'] = floatval($gross + $reversalAmount - $purchaseAmount - $chargeBackAmount - $RefundAmount);
            $extra_fields['gross_amount'] =  (isset($gross)&& $gross>0)?$gross:0;
            $extra_fields['received'] = (isset($received)&& $received>0)?$received:0;
            $extra_fields['recovery'] = (isset($recovery)&& $recovery>0)?$recovery:0;
            $extra_fields['charge_back'] = (isset($chargeBackAmount)&& $chargeBackAmount>0)?$chargeBackAmount:0;
            $extra_fields['refund_amount'] = (isset($RefundAmount)&& $RefundAmount>0)?$RefundAmount:0;
            
          
             
        } else {
            $data = [];
            foreach ($leads as $key => $lead) {
                $data[$key] = $lead;
                
                $chargeBack = ChargeBackModel::where('lead_code', strtolower($lead->code))->get()->toArray();
                $refund = RefundModel::where('lead_code', strtolower($lead->code))->get()->toArray();
                    
                $data[$key]['refund'] = (count($refund) > 0) ? true : false;
                $data[$key]['chargeback'] = (count($chargeBack) > 0) ? true : false;
                // $data[$key]['gross'] =  $this->getCurrentLeadAmount($lead);
                // $data[$key]['date'] = $lead->created_at->format('jS M Y');
                // $lead->gross = $this->getCurrentLeadAmount($lead);

                // $lead->date = $lead->created_at->format('jS M Y');
            }
        }



        // $leads = $leads->toArray();
        return response()->json(['status' => true, 'leads' => array_values($data), 'extra_fileds' => $leadsData, 'permission' => $this->user_permission(auth()->user())]);
    }

   public function searchLeadslist(Request $req)
    {
        $month = $req->month;
        $unit_id = $req->unit_id;
        $year = $req->year;
        $search = $req->search;
        
        if (auth()->user()->role->id == 4 && auth()->user()->permission == 3) {
     

            $leads = Leads::with('getsource', 'unitdetail', 'salesrep', 'accountrepdetail', 'getbrand')->orWhere('sales_rep', auth()->user()->id)->orWhere('account_rep', auth()->user()->id)->orWhere('name', $search)->orWhere('phone', $search)->orWhere('email', $search)->orderby('date',"DESC")->get();
        
                //   dd(1);
        } else if ((auth()->user()->role->id == 3 && auth()->user()->permission == null) || (auth()->user()->role->id == 4 && auth()->user()->permission == 1) || (auth()->user()->role->id == 4 && auth()->user()->permission == 2)) {
            $units = json_decode(auth()->user()->unit_id);
            $leads = Leads::with('getsource', 'unitdetail', 'salesrep', 'accountrepdetail', 'getbrand')->whereIn('unit_id', $units)->orWhere('name', $search)->orWhere('phone', $search)->orWhere('email', $search)->orderby('date',"DESC")->get();
                    //  dd( 2);
        }else if((auth()->user()->role->id == 2 && auth()->user()->permission == 1) || (auth()->user()->role->id == 2 && auth()->user()->permission == 2) || (auth()->user()->role->id == 2 && auth()->user()->permission == 3)){
            $units = json_decode(auth()->user()->unit_id);
            $leads = Leads::with('getsource', 'unitdetail', 'salesrep', 'accountrepdetail', 'getbrand')->whereIn('unit_id', $units)->orWhere('name', $search)->orWhere('phone', $search)->orWhere('email', $search)->orderby('date',"DESC")->get();
        } 
        else {
            $leads = Leads::with('getsource', 'unitdetail', 'salesrep', 'accountrepdetail', 'getbrand')->orWhere('name', $search)->orWhere('phone', $search)->orWhere('email', $search)->orderby('date',"DESC")->get();
                    //   dd( 3);
        }
        
        $leadsData = [];

        
        if(isset($month) && isset($year) && isset($unit_id) && $month !== 'undefined' && $year !== 'undefined' && $unit_id !== 'undefined'){
            $start = Carbon::createFromFormat('Y-m', $year. '-'. $month)->firstOfMonth()
                        ->format('Y-m-d');
                        
            $end = Carbon::createFromFormat('Y-m', $year. '-' . $month)
            ->endOfMonth()
            ->format('Y-m-d');
            
            $targetData = UnitTarget::where('unit_id', $unit_id)->where('month', $month)->where('year', $year)->sum('target');
            $grossData = Leads::where('unit_id', $unit_id)->whereBetween('date', [$start, $end] )->sum('gross');
            
            $recievedData = Leads::where('unit_id', $unit_id)->whereBetween('date', [$start, $end] )->sum('received');
            
            $recoveryData = Leads::where('unit_id', $unit_id)->whereBetween('date', [$start, $end] )->sum('recovery');
    
            $refundsData = RefundModel::where('unit_id', $unit_id)->whereBetween('refund_date', [$start, $end] )->sum('refund_amount');
    
            $chargeBackData = ChargeBackModel::where('unit_id', $unit_id)->whereBetween('chargeback_date', [$start, $end] )->sum('chargeback_amount');
    
            $purchaseData = PurchaseModel::where('unit_id', $unit_id)->whereBetween('purchase_date', [$start, $end] )->sum('purchase_amount');
    
            $reversalData = ReversalModel::where('unit_id', $unit_id)->whereBetween('reversal_date', [$start, $end] )->sum('reversal_amount');
    
            $leadsData = [
                "net"=> $grossData + $reversalData - $refundsData - $chargeBackData - $purchaseData,
                "unit_target"=> $targetData,
                "gross_amount"=> $grossData,
                "received"=> $recievedData,
                "recovery"=> $recoveryData,
                "charge_back"=> $chargeBackData,
                "reversal_amount"=>$reversalData,
                "refund_amount"=> $refundsData
            ];
            
           
        }
        
       
        

        $extra_fields = [];
        if ((isset($month) && isset($year) && isset($unit_id)) && $month !== 'undefined' && $year !== 'undefined' && $unit_id !== 'undefined') {
            $extra_fields['net'] = 0;
            $data = [];
                $received = 0;
                $recovery = 0;
                $reversalAmount = 0;
                $purchaseAmount = 0;
                $gross = 0;
                $net = 0;
                $chargeBackAmount = 0;
                $RefundAmount = 0;
                
            foreach ($leads as $key => $lead) {
                if ($month == date("m", strtotime($lead->date)) && $year == date("Y", strtotime($lead->date)) &&  $lead->unit_id == $unit_id ) {
                    $currentmonth = UnitTarget::where(['unit_id' => $unit_id, 'month' => $month])->sum('target');
                    $data[$key] = $lead;

                    $received += $lead->received;
                    $recovery += $lead->recovery;
                    $gross += $lead->gross;
                    $chargeBack = ChargeBackModel::where('lead_code', strtolower($lead->code))->whereMonth('chargeback_date', $month)->get()->toArray();
                    $refund = RefundModel::where('lead_code', strtolower($lead->code))->whereMonth('refund_date','=', $month)->get()->toArray();
                    
                    
                    
                    //  $chargeBack = ChargeBackModel::where('unit_id', $unit_id)->whereMonth('chargeback_date', $month)->get()->toArray();
                    // $refund = RefundModel::where('unit_id', $unit_id)->whereMonth('refund_date','=', $month)->get()->toArray();
                    $purchase = PurchaseModel::where('lead_code', strtolower($lead->code))->whereMonth('purchase_date', $month)->get()->toArray();
                    $reversal = ReversalModel::where('lead_code', strtolower($lead->code))->whereMonth('reversal_date', $month)->get()->toArray();
                    
                    $chargeBackAmount = (count($chargeBack) > 0) ? array_sum(array_column($chargeBack, 'chargeback_amount')) : 0; //Unit and Month wise ChargeBack Amount
                    $RefundAmount = (count($refund) > 0) ? array_sum(array_column($refund, 'refund_amount')) : 0; //Unit and Month wise Refund Amount
                    $purchaseAmount = (count($purchase) > 0) ? array_sum(array_column($purchase, 'purchase_amount')) : 0; //Unit and Month wise Purchase Amount
                    $reversalAmount = (count($reversal) > 0) ? array_sum(array_column($reversal, 'reversal_amount')) : 0; //Unit and Month wise Reversal Amount
                
                    $chargeBack = ChargeBackModel::where('lead_code', strtolower($lead->code))->get()->toArray();
                    $refund = RefundModel::where('lead_code', strtolower($lead->code))->get()->toArray();
                      
                    
                    $data[$key]['refund'] = (count($refund) > 0) ? true : false;
                    $data[$key]['chargeback'] = (count($chargeBack) > 0) ? true : false;
                    
                    $chargeBackAmount = (count($chargeBack) > 0) ? array_sum(array_column($chargeBack, 'chargeback_amount')) : 0; //Unit and Month wise ChargeBack Amount
                    $RefundAmount = (count($refund) > 0) ? array_sum(array_column($refund, 'refund_amount')) : 0; //Unit and Month wise Refund Amount
                    $extra_fields['unit_target'] = floatval($currentmonth);
                  
                }
                // $lead->gross = $this->getCurrentLeadAmount($lead);
                // $lead->date = $lead->created_at->format('jS M Y');
            }
            
            $extra_fields['net'] = floatval($gross + $reversalAmount - $purchaseAmount - $chargeBackAmount - $RefundAmount);
            $extra_fields['gross_amount'] =  (isset($gross)&& $gross>0)?$gross:0;
            $extra_fields['received'] = (isset($received)&& $received>0)?$received:0;
            $extra_fields['recovery'] = (isset($recovery)&& $recovery>0)?$recovery:0;
            $extra_fields['charge_back'] = (isset($chargeBackAmount)&& $chargeBackAmount>0)?$chargeBackAmount:0;
            $extra_fields['refund_amount'] = (isset($RefundAmount)&& $RefundAmount>0)?$RefundAmount:0;
            
          
             
        } else {
            $data = [];
            foreach ($leads as $key => $lead) {
                $data[$key] = $lead;
                
                $chargeBack = ChargeBackModel::where('lead_code', strtolower($lead->code))->get()->toArray();
                $refund = RefundModel::where('lead_code', strtolower($lead->code))->get()->toArray();
                    
                $data[$key]['refund'] = (count($refund) > 0) ? true : false;
                $data[$key]['chargeback'] = (count($chargeBack) > 0) ? true : false;
                // $data[$key]['gross'] =  $this->getCurrentLeadAmount($lead);
                // $data[$key]['date'] = $lead->created_at->format('jS M Y');
                // $lead->gross = $this->getCurrentLeadAmount($lead);

                // $lead->date = $lead->created_at->format('jS M Y');
            }
        }



        // $leads = $leads->toArray();
        return response()->json(['status' => true, 'leads' => array_values($data), 'extra_fileds' => $leadsData, 'permission' => $this->user_permission(auth()->user())]);
    }


    public function addeditlead(Request $request, $leads = null)
    {
        //   $leads = Leads::get();
        
        // if($leads )

        if ($leads) {
            $leads = Leads::where('code', $leads)->first();
        } else {
            $leads = new Leads();
            $code = $this->generateUniqueId("lead_");

            $lead = Leads::where('code', $code)->first();

            if ($lead) {
                $code = $this->generateUniqueId("lead_");
            }
            $leads->code = $code;
            if(isset($request->date))
            {
                $leads->date = date("Y-m-d", strtotime($request->date));
            }
        }

        $msg = isset($leads->id) ? 'Updated Succesfully' : 'Added Succesfully';

        if (isset($leads->id)) {
            $validator = Validator::make($request->all(), [
                'source' => 'string|max:255',
                'brand' => 'required',
                'product' => 'required',
                'email' => 'required',
                'name' => 'required',
                'phone' => 'required',
                'description' => 'required',
                'quoted_amount' => 'required',
                // 'received' => 'required',
                // 'recovery' => 'required',
                // 'sales_rep' => 'required',
                'unit_id' => 'required',

            ]);
        } else {
            $validator = Validator::make($request->all(), [
                'source' => 'string|max:255',
                'brand' => 'required',
                'product' => 'required',
                'email' => 'required',
                'name' => 'required',
                'phone' => 'required',
                'description' => 'required',
                'quoted_amount' => 'required',
                // 'received' => 'required',
                // 'recovery' => 'required',
                // 'sales_rep' => 'required',
                'unit_id' => 'required',
                'date' => 'required'

            ]);
        }

        if ($validator->fails()) {
            $errors = $validator->errors()->toArray();
            return response()->json(['status' => false, 'errors' => $errors], 400);
        }


        if (intval($request->received) == 0 && intval($request->recovery) == 0) {
            return response()->json(['status' => false, 'msg' => "The Received and Recovery Both Can't Be 0!"]);
        }
        
        // return response()->json(['data'=>$request->account_rep]);

        $leads->source = $request->source;
        $leads->brand = $request->brand;
        $leads->product = $request->product;
        $leads->email = $request->email;
        $leads->name = $request->name;
        $leads->phone = $request->phone;
        $leads->description = $request->description;
        $leads->quoted_amount = $request->quoted_amount;
        $leads->received = ($request->received != null) ? round($request->received) : 0;
        $leads->recovery = ($request->recovery != null) ? round($request->recovery) : 0;
        $leads->sales_rep = $request->sales_rep;
        // $leads->date = $request->date;
        // $leads->code =  $code;
        $leads->account_rep = ($request->account_rep != null && $request->account_rep != "Null") ? $request->account_rep : Null;
        $leads->unit_id = $request->unit_id;



        if ($leads->save()) {
             //Logs
                            $logMsg = 'Lead '.$leads->code.' '.$msg.' by the user';
                            $logs= ['user'=>auth()->user()->id,'unit'=>$leads->unit_id,'type'=>'leads','msg'=>$logMsg];
                            $this->generateLogs($logs);
                        //End Logs
            return response()->json(['status' => true, 'msg' => $msg, 'lead' => $leads]);
        } else {
            return response()->json(['status' => false, 'msg' => 'Error']);
        }


        return response()->json($leads);
    }

    public function delete_lead($leads)
    {


        $leads = Leads::where('code', $leads)->first();

        if ($leads) {
            
            //Logs
                            $logMsg = 'Lead '.$leads->code.' deleted by the user';
                            $logs= ['user'=>auth()->user()->id,'unit'=>$leads->unit_id,'type'=>'leads','msg'=>$logMsg];
                            $this->generateLogs($logs);
                        //End Logs
            $leads->delete();


            return response()->json(['status' => true, 'msg' => "Lead Delete Success", 'permission' => $this->user_permission(auth()->user())]);
        } else {
            return response()->json(['status' => false, 'msg' => "Lead Code Incorrect or Didn't Exists!"]);
        }
    }

    public function totalAmount()
    {
        $sumAmount = Leads::sum('quoted_amount');
        return response()->json(['status' => true, 'totalSum' => $sumAmount, 'permission' => $this->user_permission(auth()->user())]);
    }

    public function totalReceivedAmount()
    {
        $sumReceivedAmount = Leads::sum('received');
        return response()->json(['status' => true, 'totalSumReceivedAmount' => $sumReceivedAmount, 'permission' => $this->user_permission(auth()->user())]);
    }

    public function totalAmountMonth()
    {
        $start = Carbon::now()->startOfMonth()->format('Y-m-d');
        $end = Carbon::now()->endOfMonth()->format('Y-m-d');
        $sumAmountMonthly = Leads::whereBetween('created_at', [$start, $end])->sum('quoted_amount');
        return response()->json(['status' => true, 'sumAmountMonthly' => $sumAmountMonthly, 'permission' => $this->user_permission(auth()->user())]);
    }

    public function totalAmountMonthReceived()
    {
        $start = Carbon::now()->startOfMonth()->format('Y-m-d');
        $end = Carbon::now()->endOfMonth()->format('Y-m-d');
        $sumAmountMonthlyReceived = Leads::whereBetween('created_at', [$start, $end])->sum('received');
        return response()->json(['status' => true, 'sumAmountMonthlyReceived' => $sumAmountMonthlyReceived, 'permission' => $this->user_permission(auth()->user())]);
    }

    // public function unitview(Units $unit)
    // {
    //     return response()->json(['status'=>true,'unit'=>$unit->toArray()]);  
    // }
    // public function BrandsAddEdit(Request $request, Brands $brand)
    // {
    //     $msg = isset($brand->id)?'Updated Succesfully':'Added Succesfully';
    //     $validator = Validator::make($request->all(), [
    //         'name' => 'string|max:255',
    //         'status'=>'required',
    //         ]);
    //         if ($validator->fails()) {
    //             $errors = $validator->errors()->toArray();
    //             return response()->json(['status' => false, 'errors' => $errors], 400);
    //         }
    //     $brand->name= $request->name;
    //     $brand->status = $request->status;
    //     if($brand->save())
    //     {
    //         return response()->json(['status'=>true,'msg'=>$msg,'brand'=>$brand]); 
    //     }
    //     else
    //     {
    //         return response()->json(['status'=>false,'msg'=>'Error']); 
    //     }

    // }
    // 
    public function setUnitTarget(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'unit_id' => 'required|exists:units,id',
            'target' => 'required|numeric',
            'month' => 'required|integer',
            // 'year' => 'required|integer',
            // 'status' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 400);
        }

        $existingUnitTarget = UnitTarget::where('unit_id', $request->unit_id)->where('month', $request->month)->first();

        if ($existingUnitTarget) {
            return response()->json(['status' => false, 'msg' => 'A unit target for this month already been inserted.'], 400);
        }
        $check = UnitTarget::where('unit_id', $request->unit_id)->where('month', $request->month)->first();

        // if($check)
        // {
        $unitTarget = new UnitTarget([
            'unit_id' => $request->unit_id,
            'target' => $request->target,
            'month' => $request->month,
            'year' => date('Y'),
        ]);

        if ($unitTarget->save()) {
            return response()->json(['status' => true, 'msg' => 'Unit target created successfully', 'unit_target' => $unitTarget]);
        } else {
            return response()->json(['status' => false, 'msg' => 'Failed to create unit target'], 500);
        }

        // }
        // else
        // {
        //     return resposne()->json(['status'->false,'msg'=>"Current Month Target Already Exist"]);
        // }

    }


    public function getUnitTargets($unitId)
    {

        $unit_target = UnitTarget::where('id',$unitId)->first();
       
        $unit = Units::select('id', 'name')->with('MonthTarget')->where('id', $unit_target->unit_id)->where('status', 1)->first();

       
        if (!$unit) {
            return response()->json(['message' => 'Unit not found'], 404);
        }
        $currentmonth = 0;
        $current = date('m');
        $year = date('y');
        foreach ($unit->MonthTarget as $k => $v) {
            $data = $this->MonthlyScore(['unitid' => $unit->id, 'month' => $v->month, 'year' => $v->year]);
            $unit['MonthTarget'][$k]['target_score'] = $data;
            if ($unit['MonthTarget'][$k]['month'] == $current && $unit['MonthTarget'][$k]['year']) {
                $currentmonth = $unit['MonthTarget'][$k];
            }

            // $currentmonth = $unit['MonthTarget'][$k];
        }

        $data = $this->getSalesbyUnit($unit->id);
        // dd($data);



        $unit['total_sales'] = $data['total_amount'];
        $unit['current_month_target'] = $currentmonth;

        return response()->json(['data' => $unit, 'permission' => $this->user_permission(auth()->user())]);
    }


    public function getUnitTargetsedit(Request $request, UnitTarget $unitTarget)
    {


        $unitTarget->update($request->all());
        return response()->json(['message' => 'Unit Target updated successfully']);
    }

    public function getUnitTargetsdelete(unitTarget $unitTarget)
    {
        $unitTarget->delete();
        return response()->json(['message' => 'Unit target deleted successfully']);
    }

    public function unit_user($unit_id = 2)
    {
        //   $data = "";



        if (auth()->user()->role->id == 4 && auth()->user()->permission !== null && (auth()->user()->permission == 2 || auth()->user()->permission == 3)) {
            $data = User::with(
                'user_target',
                'userleads',
                'userrefunds',
                'userchargeback',
                'userpurchase',
                'userreversal'
            )
                ->where('user_role', '!=', '1')->where('show_reports',1)->where('status', 1)->where('id', auth()->user()->id)->get()->toArray();
        } else {

            $data = User::with(
                'user_target',
                'userleads',
                'userrefunds',
                'userchargeback',
                'userpurchase',
                'userreversal'
            )
                ->where('user_role', '!=', '1')->where('status', 1)->get()->toArray();
        }




        $user_data = [];
        foreach ($data as $value) {
            if($value['user_role']==2)
            {
                continue;
            }
            $grosssum = 0;
            $target = 0;
            $refunds = 0;
            $chargeback = 0;
            $purchase = 0;
            $reversal = 0;
            $net = 0;

            $user_unit = json_decode($value['unit_id']);
            if (in_array($unit_id, $user_unit)) {
                $leads = array_values($value['userleads']);

                if (!empty($leads)) {




                    $net =  array_sum(array_column($value['userleads'], 'gross'));
                }


                $user_data[] = ["id" => $value['id'], "name" => $value['name'], 'net' => $net, 'email' => $value['email']];
            }
        }


        // return response()->json(compact('token'))->header("Access-Control-Allow-Origin",  "*");
        return response()->json(['status' => true, "data" => $user_data, 'permission' => $this->user_permission(auth()->user())]);
    }
    public function unit_brands($unit_id)
    {

        $data = UnitBrands::where('unit_id', $unit_id)->get();

        if (count($data) == 0) {
            return response()->json(['status' => true, "data" => []]);
        }
        $unit_data = [];
        foreach ($data as $value) {
            $brand = Brands::where('id', $value->brand_id)->first();
            $unit_data[] = ["id" => $brand->id, "name" => $brand->name];
        }



        return response()->json(['status' => true, "data" => $unit_data, 'permission' => $this->user_permission(auth()->user())]);
    }
    // public function unitTargetList()
    // {
    //     $units = Units::with('currentMonthTarget')->where('status',1)->get()->toArray();

    //     // $unitTargets = UnitTarget::with('unit:id,name')->get()->toArray();
    //     foreach($units as $k => $v)
    //     {
    //         $data = $this->getSalesbyUnit($v['id']);
    //         $units[$k]['total_sales']= $data['total_sales'];
    //         $units[$k]['target_score']= $data['target_score'];
    //     }



    //     return response()->json(['data' => $units]);
    // }
    public function unitTargetList()
    {
        $units_data = [];


        if ((auth()->user()->role->id == 3 && auth()->user()->permission == null) || (auth()->user()->role->id == 4 && auth()->user()->permission == 1)) {
            $units = json_decode(auth()->user()->unit_id);
            $units = Units::where('status', 1)->whereIn('id', $units)->get();

            foreach ($units as $k => $v) {


                $unitTargetSpecific =  UnitTarget::where('unit_id', $v->id)->where('month', date('n'))->first();
                if ($unitTargetSpecific) {
                    $units_data[$k] = $unitTargetSpecific;
                    $units_data[$k]['name'] = $v->name;
                    $units_data[$k]['target_amount'] = isset($units_data[$k]['target']) ? $units_data[$k]['target'] : 0;
                    $units_data[$k]['is_achived'] = 0;
                }



                // $leads = Leads::where('unit_id',$v->id)->sum('quoted_amount');
                //  $v['isAschived'] = 1;
                // dd($leads,intval($unit_target));




                // echo $leads->quoted_amount;



                // $data = $this->getSalesbyUnit($v['id']);
                // $units[$k]['total_ammount'] = $data['total_amount'];
            }

            $units_data = array_values($units_data);
        } else {
            $units = Units::where('status', 1)->get();
            foreach ($units as $k => $v) {


                $unitTargetSpecific =  UnitTarget::where('unit_id', $v->id)->where('month', date('n'))->first();
                if ($unitTargetSpecific) {
                    $units_data[$k] = $unitTargetSpecific;
                    $units_data[$k]['name'] = $v->name;
                    $units_data[$k]['target_amount'] = isset($units_data[$k]['target']) ? $units_data[$k]['target'] : 0;
                    $units_data[$k]['is_achived'] = 0;
                }



                // $leads = Leads::where('unit_id',$v->id)->sum('quoted_amount');
                //  $v['isAschived'] = 1;
                // dd($leads,intval($unit_target));




                // echo $leads->quoted_amount;



                // $data = $this->getSalesbyUnit($v['id']);
                // $units[$k]['total_ammount'] = $data['total_amount'];
            }

            $units_data = array_values($units_data);
        }


        // dd($unit_data);


        return response()->json(['data' => $units_data, 'permission' => $this->user_permission(auth()->user())]);
    }
    
    
    public function leadSourcesAddUpdate(Request $req, SourceModel  $leadsource)
    {
        $msg = isset($leadsource->id) ? 'Updated Succesfully' : 'Added Succesfully';
    
    
        if (!isset($leadsource->id)) {
            $validator = Validator::make($req->all(), [
                'name' => 'required'
            ]);

            if ($validator->fails()) {
                $errors = $validator->errors()->toArray();
                return response()->json(['status' => false, 'errors' => $errors], 400);
            }
        }
        
        $verify = SourceModel::where('name', $req->name)->first();
    
        if($verify!==null){
             return response()->json(['status' => false, 'msg' => "Lead source must be unique!"]);
        }
        
        $leadsource->name = $req->name;
        $leadsource->save();
        
        return response()->json(['status' => true, 'msg' => "Lead Source " . $msg, 'data' => $leadsource]);
    }
    
    public function leadsource_view(SourceModel $leadsource)
    {
        $leadsource = SourceModel::where('id', $leadsource->id)->first();

        return response()->json(['status' => true, 'msg' => "Lead Source Get Success", 'data' => $leadsource,'permission'=>$this->user_permission(auth()->user())]);
    }
    
    public function leadsource_listing()
    {
        $data = SourceModel::orderby('name','ASC')->get();
        return response()->json(['status' => true, 'msg' => "Lead Source Get Success", 'data' => $data,'permission'=>$this->user_permission(auth()->user())]);
    }
    
    public function leadsource_delete($leadsource)
    {
        $leadsource = SourceModel::where('id',$leadsource)->first();
        if($leadsource)
        {
            
            $leadsource->delete();
    
            return response()->json(["status" => true, 'msg' => "Lead Source Deleted Success", 'permission'=>$this->user_permission(auth()->user())]);
        }
        else
        {
            return response()->json(["status" => false, 'msg' => "Lead Source Doesn't Exists!"]);
        }
    }
}
