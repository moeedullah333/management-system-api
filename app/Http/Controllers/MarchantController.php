<?php

namespace App\Http\Controllers;

use App\Models\MerchantModel;
use Facade\FlareClient\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\UserPermissionModel;

class MarchantController extends Controller
{
    //  public function user_permission($user)
    // {
    //     $data = UserPermissionModel::where('role',$user->role)->orWhere('child_role',$user->permission)->first();

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
    public function merchant_add_update(Request $req, MerchantModel $merchant)
    {
        $msg = isset($merchant->id) ? 'Updated Succesfully' : 'Added Succesfully';

        if (!isset($merchant->id)) {
            $validator = Validator::make($req->all(), [
                'name' => 'required',
            ]);

            if ($validator->fails()) {
                $errors = $validator->errors()->toArray();
                return response()->json(['status' => false, 'errors' => $errors], 400);
            }
        }
        
        $check = MerchantModel::where('name',$req->name)->first();
        
        if(!$check)
        {
            
            $merchant->name = isset($req->name) ? $req->name : $merchant->name;
            
            if ($merchant->save()) {
    
                return response()->json(['status' => true, 'msg' => "Merchant " . $msg, 'data' => $merchant]);
            } else {
                return response()->json(['status' => false, 'msg' => "Something Went Wrong!" ]);
            }
        }
        else
        {
            return response()->json(['status' => false, 'msg' => "It's Already Exist!" ]);
        }
    }

    public function merchant_view(MerchantModel $merchant)
    {
        return response()->json(['status' => true, 'msg' => "Merchant Get Success", 'data' => $merchant,'permission'=>$this->user_permission(auth()->user())]);
    }

    public function merchant_listing()
    {
        $data = MerchantModel::all();

        return response()->json(['status' => true, 'msg' => "Merchant List Get Success", 'data' => $data,'permission'=>$this->user_permission(auth()->user())]);
    }

    public function merchant_delete(MerchantModel $merchant)
    {
        $merchant->delete();

        return response()->json(["status" => true, 'msg' => "Merchant Delete Success", 'data' => $merchant]);
    }
}
