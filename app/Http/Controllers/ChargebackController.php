<?php

namespace App\Http\Controllers;

use App\Models\ChargeBackModel;
use App\Models\ReversalModel;
use App\Models\RefundModel;
use App\Models\PurchaseModel;
use App\Models\Leads;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\UserPermissionModel;

class ChargebackController extends Controller
{
    // public function user_permission($user)
    // {
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
    public function chargeback_add_update(Request $req, ChargeBackModel $chargeback)
    {
        $msg = isset($chargeback->id) ? 'Updated Succesfully' : 'Added Succesfully';


        if (!isset($chargeback->id)) {
            $validator = Validator::make($req->all(), [
                'lead_code' => 'required',
                'chargeback_amount' => 'required',
                "chargeback_user_id" => 'required',
                "chargeback_date" => 'required',
                "chargeback_type" => 'required',
                "merchant_id" => 'required',
                "reason" => 'required',
                "description" => 'required'
            ]);

            if ($validator->fails()) {
                $errors = $validator->errors()->toArray();
                return response()->json(['status' => false, 'errors' => $errors], 400);
            }
        }

        $lead = Leads::where('code', strtolower($req->lead_code))->first();
       
         if($lead) 
        {
            
            // return response()->json(['data'=> $lead->gross,'chargebackamount'=>$req->chargeback_amount]); 
            // intval($req->chargeback_amount) < $lead->gross
            
          
            
            if($this->UpdatedAmountOfLead(floatval($req->chargeback_amount),$req->lead_code,isset($chargeback->id) ? "Edit" : "Add", isset($chargeback->id) ? $chargeback->id : null))
            {
                
              $chargeback->lead_code = isset($req->lead_code) ? strtolower($req->lead_code) : $chargeback->lead_code;
              $chargeback->chargeback_amount = isset($req->chargeback_amount) ? $req->chargeback_amount : $chargeback->chargeback_amount;
              $chargeback->chargeback_user_id = $req->chargeback_user_id;
              // $chargeback->chargeback_user_id = isset($req->chargeback_user_id) ? $req->chargeback_user_id : $chargeback->chargeback_user_id;
              $chargeback->unit_id = $lead->unit_id;
              $chargeback->chargeback_type = isset($req->chargeback_type) ? $req->chargeback_type : $chargeback->chargeback_type;
              $chargeback->chargeback_date = isset($req->chargeback_date) ? $req->chargeback_date : $chargeback->chargeback_date;
              $chargeback->merchant_id = isset($req->merchant_id) ? $req->merchant_id : $chargeback->merchant_id;
              $chargeback->reason = ($req->reason !== "Null") ? $req->reason : $chargeback->reason;
              $chargeback->description = isset($req->description) ? $req->description : $chargeback->description;
              $lead->status = "ChargeBack";
      
      
              if ($chargeback->save() && $lead->save()) {
                  
                        //Logs
                            $logMsg = 'Lead '.$chargeback->lead_code.' '.$msg.' by the user';
                            $logs= ['user'=>auth()->user()->id,'unit'=>$chargeback->unit_id,'type'=>'chargeback','msg'=>$logMsg];
                            $this->generateLogs($logs);
                        //End Logs
      
                  return response()->json(['status' => true, 'msg' => "Charge Back " . $msg, 'data' => $chargeback]);
              } else {
                        return response()->json(['status' => false, 'msg' => "Something Went Wrong!"]);
              }
            }
            else
            {
                 return response()->json(['status' => false,'check'=>false ,'msg' => "The ChargeBack Amount is Greater Than Lead Net Amount!"]);
            }
        }
        else
        {
              return response()->json(['status' => false, 'msg' => "Lead Not Found"]);
        }
    }
    public function UpdatedAmountOfLead($newAmount, $leadCode,$msg, $id = null){
       
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
        $total = 0;
        if($msg === "Edit")
        {
            if($id !== null){
                $cAmount = ChargeBackModel::where('id', $id)->first();
                if($cAmount){
                    $total = ($leadAmount + $reversalAmount + $cAmount->chargeback_amount - $chargeBackAmount - $RefundAmount - $PurchaseAmount - $newAmount);
                }
            }

        }
        else
        {
            $total = ($leadAmount + $reversalAmount - $chargeBackAmount - $RefundAmount - $PurchaseAmount - $newAmount);
        }
      
        
        
        
        if($total >=0)
        {
            return true;
        }
        else
        {
            return false;
        }
        
    }
    
    public function chargeback_view(ChargeBackModel $chargeback)
    {
       $chargeback = ChargeBackModel::with(
            'leaddetail',
            'chargebackuser',
            'merchantdetail'
        )->where('id', $chargeback->id)->first();
        
         $chargeback->leaddetail->gross = $this->getCurrentLeadAmount($chargeback->leaddetail);

        return response()->json(['status' => true, 'msg' => "Charge Back Get Success", 'data' => $chargeback,'permission'=>$this->user_permission(auth()->user())]);
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

    public function chargeback_listing()
    {
       $data = "";
       
      
        if(auth()->user()->role->id == 4 && auth()->user()->permission !== null && auth()->user()->permission==2 )
        {
             $data = ChargeBackModel::with(
            'leaddetail',
            'chargebackuser',
            'merchantdetail',
            'unit'
            )->Where('chargeback_user_id',auth()->user()->id)->orderby('chargeback_date','DESC')->get();
        }
        else if((auth()->user()->role->id == 3 && auth()->user()->permission==null) || (auth()->user()->role->id == 4 && auth()->user()->permission==1))
        {
            $units= json_decode(auth()->user()->unit_id);

             $data = ChargeBackModel::with(
            'leaddetail',
            'chargebackuser',
            'merchantdetail',
            'unit'
            )->whereIn('unit_id',$units)->orderby('chargeback_date','DESC')->get();
        
        }
        else
        {
             $data = ChargeBackModel::with(
            'leaddetail',
            'chargebackuser',
            'merchantdetail',
            'unit'
            )->orderby('chargeback_date','DESC')->get();
        }
        return response()->json(['status' => true, 'msg' => "Charge Back List Get Success", 'data' => $data,'permission'=>$this->user_permission(auth()->user())]);
    }

    public function chargeback_delete($chargeback)
    {
        $chargeback = ChargeBackModel::where('id',$chargeback)->first();
        if($chargeback)
        {
            //Logs
                            $logMsg = 'Chargeback for '.$chargeback->lead_code.' deleted by the user';
                            $logs= ['user'=>auth()->user()->id,'unit'=>$chargeback->unit_id,'type'=>'chargeback','msg'=>$logMsg];
                            $this->generateLogs($logs);
                        //End Logs
            
            $chargeback->delete();
    
            return response()->json(["status" => true, 'msg' => "Charge Back Delete Success", 'data' => $chargeback,'permission'=>$this->user_permission(auth()->user())]);
        }
        else
        {
            return response()->json(["status" => false, 'msg' => "Charge Back Didn't Exists!"]);
        }
    }
}
