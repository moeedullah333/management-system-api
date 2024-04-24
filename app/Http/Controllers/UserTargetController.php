<?php

namespace App\Http\Controllers;

use App\Models\UserTargetsModel;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use App\Models\UserPermissionModel;

class UserTargetController extends Controller
{
    // public function user_permission($user)
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
    public function usertarget_add_update(Request $req, UserTargetsModel $usertarget)
    {
        $msg = isset($usertarget->id) ? 'Updated Succesfully' : 'Added Succesfully';

        if (!isset($usertarget->id)) {
            $validator = Validator::make($req->all(), [
                'unit_id' => 'required',
                'user_id' => 'required',
                "target" => 'required',
                "month" => 'required',
                // "year" => 'required',
                // "status" => 'required',
            ]);

            if ($validator->fails()) {
                $errors = $validator->errors()->toArray();
                return response()->json(['status' => false, 'errors' => $errors], 400);
            }
        $user = User::where('id',$req->user_id)->first();
        
        if($user)
        {
        
        if($user->unit_id !== '' &&  !empty(($user_units = json_decode($user->unit_id, true)))){
            if(!in_array($req->unit_id, $user_units)){
                 return response()->json(['status' => false, 'msg' => "Invalid Unit!"]);
            }
        }
        else{
            return response()->json(['status' => false, 'msg' => "No Units available!"]);
        }
        }
        
        
            
        
         

        
        $usertarget->unit_id = isset($req->unit_id) ? $req->unit_id : $usertarget->unit_id;
        $usertarget->target = isset($req->target) ? $req->target : $usertarget->target;
        $usertarget->user_id = isset($req->user_id) ? $req->user_id : $usertarget->user_id;
        // $usertarget->usertarget_type = isset($req->usertarget_type) ? $req->usertarget_type : $usertarget->usertarget_type;
        $usertarget->month = isset($req->month) ? $req->month : $usertarget->month;
        $usertarget->year = date('Y');
        $usertarget->status = 1;

        if ($usertarget->save()) {

            return response()->json(['status' => true, 'msg' => "User Target " . $msg, 'data' => $usertarget]);
        } else {
            return response()->json(['status' => false, 'msg' => "Something Went Wrong!"]);
        }
        }
        else if(isset($usertarget->id))
        {
           $user = User::where('id',$req->user_id)->first();
        
       
            if($user)
            {
            
            if($user->unit_id !== '' &&  !empty(($user_units = json_decode($user->unit_id, true)))){
                if(!in_array($req->unit_id, $user_units)){
                     return response()->json(['status' => false, 'msg' => "Invalid Unit!"]);
                }
            }
            else{
                return response()->json(['status' => false, 'msg' => "No Units available!"]);
            }
            }
            
            
                
            
             
    
            
            $usertarget->unit_id = isset($req->unit_id) ? $req->unit_id : $usertarget->unit_id;
            $usertarget->target = isset($req->target) ? $req->target : $usertarget->target;
            $usertarget->user_id = isset($req->user_id) ? $req->user_id : $usertarget->user_id;
            // $usertarget->usertarget_type = isset($req->usertarget_type) ? $req->usertarget_type : $usertarget->usertarget_type;
            $usertarget->month = isset($req->month) ? $req->month : $usertarget->month;
            $usertarget->year = date('Y');
            $usertarget->status = 1;
    
            if ($usertarget->save()) {
    
                return response()->json(['status' => true, 'msg' => "User Target " . $msg, 'data' => $usertarget]);
            } else {
                return response()->json(['status' => false, 'msg' => "Something Went Wrong!"]);
            } 
        }
        else
        {
            return response()->json(['status' => false, 'msg' => "Invalid User!"]);
        }
    }

    public function usertarget_view(UserTargetsModel $usertarget)
    {
        $usertarget = UserTargetsModel::with(
            'unit_detail',
            'user_detail',
            'user_lead_detail'
        )->where('id', $usertarget->id)->first();
        
        
            $scoreTarget = 0;
            $month = $usertarget->month;
            $year = $usertarget->year;
            
            if(!empty($usertarget->user_lead_detail))
            {
               
                
                foreach($usertarget->user_lead_detail as $detail)
                {
                   
                    $lead_month = date('n', strtotime($detail->created_at));
            
                    $lead_year = date('Y', strtotime($detail->created_at));
                    if($lead_month == $month && $lead_year == $year)
                    {
                        
                      $scoreTarget += $detail->gross ;
                    }
                }
        
               
                
            }
           $usertarget->score_target = $scoreTarget;

       $data = UserTargetsModel::with(
            'unit_detail',
            'user_detail',
            'user_lead_detail'
            )->Where('user_id',$usertarget->user_id)->get()->toArray();
        
        
        $user_data_target = [];
        
        
        if(!empty($data)){
            foreach($data as $key => $item)
            {
                $user_data_target[$key] = $item;
                $scoreTarget = 0;
                $month = $item['month'];
                $year = $item['year'];
                
                if(!empty($item['user_lead_detail']))
                {
                    $user_data_target[$key]['user_lead_detail'];
                    
                    foreach($user_data_target[$key]['user_lead_detail'] as $detail)
                    {
                        
                        $lead_month = date('n', strtotime($detail['created_at']));
                
                        $lead_year = date('Y', strtotime($detail['created_at']));
                        if($lead_month == $month && $lead_year == $year)
                        {
                            
                          $scoreTarget = (array_sum(array_column($user_data_target[$key]['user_lead_detail'],'gross')));
                        }
                    }
            
                   
                    
                }
                $user_data_target[$key]['score_target'] = $scoreTarget;
            }
        }
        $usertarget->targets = $user_data_target; 

           

        return response()->json(['status' => true, 'msg' => "User Target Get Success", 'data' => $usertarget,'permission'=>$this->user_permission(auth()->user())]);
    }

    public function usertarget_listing()
    {
       $data = "";
        
        if(auth()->user()->role->id == 4 && auth()->user()->permission !== null && (auth()->user()->permission==2 || auth()->user()->permission==3))
        {
             $data = UserTargetsModel::with(
            'unit_detail',
            'user_detail',
            'user_lead_detail'
            )->Where('user_id',auth()->user()->id)->get();
        }
        else if((auth()->user()->role->id == 3 && auth()->user()->permission==null) || (auth()->user()->role->id == 4 && auth()->user()->permission==1))
        {
             $units= json_decode(auth()->user()->unit_id);
              $data = UserTargetsModel::with(
            'unit_detail',
            'user_detail',
            'user_lead_detail'
            )->whereIn('unit_id',$units)->get();
             
        }
        else
        {
             $data = UserTargetsModel::with(
            'unit_detail',
            'user_detail',
            'user_lead_detail'
            )->get();
            
        }
        
        
        $user_data_target = [];
        
        foreach($data->toArray() as $key => $item)
        {
            $user_data_target[$key] = $item;
            $scoreTarget = 0;
            $month = $item['month'];
            $year = $item['year'];
            
            if(!empty($item['user_lead_detail']))
            {
                $user_data_target[$key]['user_lead_detail'];
                
                foreach($user_data_target[$key]['user_lead_detail'] as $detail)
                {
                    
                    $lead_month = date('n', strtotime($detail['created_at']));
            
                    $lead_year = date('Y', strtotime($detail['created_at']));
                    if($lead_month == $month && $lead_year == $year)
                    {
                        
                      $scoreTarget = (array_sum(array_column($user_data_target[$key]['user_lead_detail'],'gross')));
                    }
                }
        
               
                
            }
            $user_data_target[$key]['score_target'] = $scoreTarget;
        }

        return response()->json(['status' => true, 'msg' => "User Target List Get Success", 'data' => $user_data_target,'permission'=>$this->user_permission(auth()->user())]);
    }

    public function usertarget_delete(UserTargetsModel $usertarget)
    {
        $usertarget->delete();

        return response()->json(["status" => true, 'msg' => "User Target Delete Success", 'data' => $usertarget]);
    }
}
