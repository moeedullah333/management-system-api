<?php

namespace App\Http\Controllers;

use App\Models\Leads;
use App\Models\PurchaseModel;
use App\Models\ChargeBackModel;
use App\Models\ReversalModel;
use App\Models\RefundModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\UserPermissionModel;

class ReversalController extends Controller
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
    public function reversal_add_update(Request $req, ReversalModel $reversal)
    {
        $msg = isset($reversal->id) ? 'Updated Succesfully' : 'Added Succesfully';

        //lead_id	reversal_amount	reversal_date	reversal_user_id	reversal_type	merchant_id	
        if (!isset($reversal->id)) {
            $validator = Validator::make($req->all(), [
                'lead_code' => 'required',
                'reversal_amount' => 'required',
                "reversal_date" => 'required',
                "reversal_user_id" => 'required',
                "reversal_type" => 'required',
                "merchant_id" => 'required',
            ]);

            if ($validator->fails()) {
                $errors = $validator->errors()->toArray();
                return response()->json(['status' => false, 'msg' => $errors], 400);
            }
        }
        // $user_id = Leads::where('id', $req->lead_id)->first('account_rep');
        $lead = Leads::where('code', strtolower($req->lead_code))->first();
        //  $user_id = $user_id->account_rep;
      
      if($lead) 
        {
            
            if($this->UpdatedAmountOfLead(floatval($req->reversal_amount),$req->lead_code,isset($purchase->id) ? "Edit" : "Add"))
            {
                
                $reversal->lead_code = isset($req->lead_code) ? strtolower($req->lead_code) : $reversal->lead_code;
                $reversal->reversal_amount = isset($req->reversal_amount) ? $req->reversal_amount : $reversal->reversal_amount;
                $reversal->reversal_date = isset($req->reversal_date)? $req->reversal_date : $reversal->reversal_date;
                // $reversal->reversal_user_id = isset($req->reversal_user_id) ? $req->reversal_user_id : $reversal->reversal_user_id;
                $reversal->reversal_user_id = $req->reversal_user_id;
                 $reversal->unit_id = $lead->unit_id;
                $reversal->reversal_type = isset($req->reversal_type) ? $req->reversal_type : $reversal->reversal_type;
                $reversal->merchant_id = isset($req->merchant_id) ? $req->merchant_id : $reversal->merchant_id;
                $lead->status = "Reversal";
                if ($reversal->save()) {
                    
                    
                    //Logs
                            $logMsg = 'Lead '.$reversal->lead_code.' '.$msg.' by the user';
                            $logs= ['user'=>auth()->user()->id,'unit'=>$reversal->unit_id,'type'=>'reversals','msg'=>$logMsg];
                            $this->generateLogs($logs);
                        //End Logs
        
                    return response()->json(['status' => true, 'msg' => "Reversal " . $msg, 'data' => $reversal]);
                } else {
                    return response()->json(['status' => false, 'msg' => "Something Went Wrong!"]);
                }
            }
            else
            {
                 return response()->json(['status' => false,'check'=>false, 'msg' => "The Reversal Amount is Greater Than Lead Net Amount!"]);
            }
         }
        else
        {
              return response()->json(['status' => false, 'msg' => "Lead Not Found"]);
        }
      


    }
    public function UpdatedAmountOfLead($newAmount, $leadCode,$msg){
            
             $lead = Leads::where('code', strtolower($leadCode))->first();
             $chargeBack = ChargeBackModel::where('lead_code',strtolower($leadCode))->get()->toArray();
             $refund = RefundModel::where('lead_code',strtolower($leadCode))->get()->toArray();
             $purchase = PurchaseModel::where('lead_code',strtolower($leadCode))->get()->toArray();
             $reversal = ReversalModel::where('lead_code',strtolower($leadCode))->get()->toArray();
             
           
            //   return response()->json(['data'=> $lead->gross,'chargebackamount'=>$req->chargeback_amount]); 
            
            $leadAmount =$lead->gross; //Lead Amount
            $chargeBackAmount= (count($chargeBack) > 0) ? array_sum(array_column($chargeBack,'chargeback_amount')) : 0; //Lead ChargeBack Amount
            $RefundAmount= (count($refund) > 0)? array_sum(array_column($refund,'refund_amount')) : 0; //Refund Amount
            $PurchaseAmount =(count($purchase) > 0)? array_sum(array_column($purchase,'purchase_amount')): 0; //Not Now
            $reversalAmount =(count($reversal) > 0) ? array_sum(array_column($reversal,'reversal_amount')): 0;
                
            // $total = ($leadAmount - $chargeBackAmount - $RefundAmount - $PurchaseAmount - $reversalAmount  - $newAmount);
             $total = ($leadAmount + $reversalAmount - $chargeBackAmount - $RefundAmount - $PurchaseAmount + $newAmount);
            
            
            
            if($total >=0)
            {
                return true;
            }
            else
            {
                return false;
            }
            
        }
    public function reversal_view(ReversalModel $reversal)
    {
        $reversal = ReversalModel::with(
            'leaddetail',
            'reversaluser',
            'merchantdetail'
        )->where('id', $reversal->id)->first();
        
        
        
        $reversal->leaddetail->gross = $this->getCurrentLeadAmount($reversal->leaddetail);

        return response()->json(['status' => true, 'msg' => "Reversal Get Success", 'data' => $reversal,'permission'=>$this->user_permission(auth()->user())]);
    }
    public function getCurrentLeadAmount($leadCode){
        
         $chargeBack = ChargeBackModel::where('lead_code',strtolower($leadCode->code))->get()->toArray();
         $refund = RefundModel::where('lead_code',strtolower($leadCode->code))->get()->toArray();
         $purchase = PurchaseModel::where('lead_code',strtolower($leadCode->code))->get()->toArray();
         $reversal = ReversalModel::where('lead_code',strtolower($leadCode->code))->get()->toArray();
        
        
        $leadAmount =$leadCode->gross; //Lead Amount
        $chargeBackAmount= (count($chargeBack) > 0) ? array_sum(array_column($chargeBack,'chargeback_amount')) : 0; //Lead ChargeBack Amount
        $RefundAmount= (count($refund) > 0)? array_sum(array_column($refund,'refund_amount')) : 0; //Refund Amount
        $PurchaseAmount =(count($purchase) > 0)? array_sum(array_column($purchase,'purchase_amount')): 0; //Not Now
        $reversalAmount =(count($reversal) > 0) ? array_sum(array_column($reversal,'reversal_amount')): 0;
        // return floatval($leadAmount - $chargeBackAmount - $RefundAmount - $reversalAmount - $PurchaseAmount);
        return floatval($leadAmount  + $reversalAmount - $RefundAmount - $chargeBackAmount - $PurchaseAmount);
    
    }
    public function reversal_listing()
    {
       $data = "";
        
        
            
        if(auth()->user()->role->id == 4 && auth()->user()->permission !== null && (auth()->user()->permission==2 || auth()->user()->permission==3))
        {
             $data = ReversalModel::with(
            'leaddetail',
            'reversaluser',
            'merchantdetail',
            'unit'
            )->Where('reversal_user_id',auth()->user()->id)->orderby('reversal_date','DESC')->get();
        }
        else if((auth()->user()->role->id == 3 && auth()->user()->permission==null) || (auth()->user()->role->id == 4 && auth()->user()->permission==1))
        {
            $units= json_decode(auth()->user()->unit_id);
             $data = ReversalModel::with(
            'leaddetail',
            'reversaluser',
            'merchantdetail',
            'unit'
            )->whereIn('unit_id',$units)->orderby('reversal_date','DESC')->get();
        }
        else
        {
             $data = ReversalModel::with(
            'leaddetail',
            'reversaluser',
            'merchantdetail',
            'unit'
            )->orderby('reversal_date','DESC')->get();
        }

        return response()->json(['status' => true, 'msg' => "Reversal List Get Success", 'data' => $data,'permission'=>$this->user_permission(auth()->user())]);
    }

    public function reversal_delete($reversal)
    {
        $reversal = ReversalModel::where('id',$reversal)->first();
        
        if($reversal)
        {
            
             //Logs
                            $logMsg = 'Reversal for '.$reversal->lead_code.' deleted by the user';
                            $logs= ['user'=>auth()->user()->id,'unit'=>$reversal->unit_id,'type'=>'reversals','msg'=>$logMsg];
                            $this->generateLogs($logs);
                        //End Logs
            $reversal->delete();
    
            return response()->json(["status" => true, 'msg' => "Reversal Delete Success", 'data' => $reversal,'permission'=>$this->user_permission(auth()->user())]);
        }
        else
        {
            return response()->json(["status" => false, 'msg' => "Reversal Didn't Exists!"]);
        }
    }
}
