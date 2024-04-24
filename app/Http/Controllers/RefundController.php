<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Leads;
use App\Models\RefundModel;
use App\Models\ChargeBackModel;
use App\Models\ReversalModel;
use App\Models\PurchaseModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\UserPermissionModel;

class RefundController extends Controller
{
    public function refund_add_update(Request $req,$refund=null)
    {
         if($refund)
        {
            $refund = RefundModel::where('id',$refund)->first();  
        }
        else
        {
            $refund = new RefundModel();
        }
        
        $msg = isset($refund->id) ? 'Updated Succesfully' : 'Added Succesfully';


       

        if (!isset($refund->id)) {
            $validator = Validator::make($req->all(), [
                'lead_code' => 'required',
                'refund_amount' => 'required',
                "refund_date" => 'required',
                // "refund_user_id" => 'required',
                "refund_type" => 'required',
                "merchant_id" => 'required',
                "reason" => 'required'
            ]);

            if ($validator->fails()) {
                $errors = $validator->errors()->toArray();
                return response()->json(['status' => false, 'msg' => $errors], 400);
            }
            
            // return response()->json(['data'=>strtolower($req->lead_code)]);
              $lead = Leads::where('code', strtolower($req->lead_code))->first();
        
             $user_id = null;

        if($lead) 
        {
            $user_id = Leads::where('code', strtolower($req->lead_code))->first('account_rep'); 
            
            if($user_id->account_rep !== 'null' && $user_id->account_rep !== null)
            {
                $user_id = $user_id->account_rep;
                
                //$req->refund_amount < $lead->gross
               
                if($this->UpdatedAmountOfLead(floatval($req->refund_amount),$req->lead_code,isset($refund->id) ? "Edit" : "Add"))
                {
                    
                    $refund->lead_code = isset($req->lead_code) ? strtolower($req->lead_code) : $refund->lead_code;
                    $refund->refund_amount = isset($req->refund_amount) ? $req->refund_amount : $refund->refund_amount;
                    $refund->refund_date = isset($req->refund_date) ? $req->refund_date : $refund->refund_date;
                    // $refund->refund_user_id = isset($req->refund_user_id) ? $req->refund_user_id : $refund->refund_user_id;
                    $refund->refund_user_id = $user_id;
                    $refund->unit_id = $lead->unit_id;
                    $refund->refund_type = isset($req->refund_type) ? $req->refund_type : $refund->refund_type;
                    $refund->merchant_id = isset($req->merchant_id) ? $req->merchant_id : $refund->merchant_id;
                    $refund->reason = isset($req->reason) ? $req->reason : $refund->reason;
                    $lead->status = "Refund";
                    if ($refund->save()) {
                        
                        
                        //Logs
                            $logMsg = 'Lead '.$refund->lead_code.' '.$msg.' by the user';
                            $logs= ['user'=>auth()->user()->id,'unit'=>$lead->unit_id,'type'=>'refunds','msg'=>$logMsg];
                            $this->generateLogs($logs);
                        //End Logs
                        return response()->json(['status' => true, 'msg' => "Refund " . $msg, 'data' => $refund]);
                    } else {
                        return response()->json(['status' => false, 'msg' => "Something Went Wrong!"]);
                    }
                }
                else
                {
                    return response()->json(['status' => false, 'check'=>false, 'msg' => "The Refund Amount is Greater Than Lead Net Amount!"]);
                }
                
                
             }
            else
            {
                return response()->json(['status' => false, 'msg' => "Unable to Add refund, Please add account rep in the lead!"]);
            }
         
        }
        else
        {
              return response()->json(['status' => false, 'msg' => "Lead Not Found"]);
        }
        }
        else
        {
             $validator = Validator::make($req->all(), [
                'lead_code' => 'required',
                'refund_amount' => 'required',
                "refund_date" => 'required',
                "refund_user_id" => 'required',
                "refund_type" => 'required',
                "merchant_id" => 'required',
                "reason" => 'required'
            ]);

            if ($validator->fails()) {
                $errors = $validator->errors()->toArray();
                return response()->json(['status' => false, 'errors' => $errors], 400);
            }
            
              $lead = Leads::where('code', strtolower($req->lead_code))->first();
        
        $user_id = null;
             if($lead) 
        {
            // $user_id = Leads::where('id', $req->lead_id)->first('account_rep'); 
            
            // if($user_id->account_rep !== 'null' && $user_id->account_rep !== null)
            // {
            // $user_id = $user_id->account_rep;
            
            
           
                // return response()->json(['status'=>false,'data'=>$req->all()]);
                
            if($this->UpdatedAmountOfLead(floatval($req->refund_amount),$req->lead_code,isset($refund->id) ? "Edit" : "Add",isset($refund) ? $refund->id : null))
            {
                
                $refund->lead_code = isset($req->lead_code) ? strtolower($req->lead_code) : $refund->lead_code;
                $refund->refund_amount = isset($req->refund_amount) ? $req->refund_amount : $refund->refund_amount;
                $refund->refund_date = isset($req->refund_date) ? $req->refund_date : $refund->refund_date;
                // $refund->refund_user_id = isset($req->refund_user_id) ? $req->refund_user_id : $refund->refund_user_id;
                $refund->refund_user_id = $req->refund_user_id;
                 $refund->unit_id = $lead->unit_id;
                $refund->refund_type = isset($req->refund_type) ? $req->refund_type : $refund->refund_type;
                $refund->merchant_id = isset($req->merchant_id) ? $req->merchant_id : $refund->merchant_id;
                $refund->reason = isset($req->reason) ? $req->reason : $refund->reason;
                $lead->status = "Refund";
                
                
                if ($refund->save()) {
                    
                    //Logs
                            $logMsg = 'Lead ('.$refund->lead_code.') '.$msg.' by the user';
                            $logs= ['user'=>auth()->user()->id,'unit'=>intval($refund->unit_id),'type'=>'refunds','msg'=>$logMsg];
                            $this->generateLogs($logs);
                        //End Logs
        
                    return response()->json(['status' => true, 'msg' => "Refund " . $msg, 'data' => $refund]);
                } else {
                    return response()->json(['status' => false, 'msg' => "Something Went Wrong!"]);
                }
            }
            else
            {
                    return response()->json(['status' => false,'check'=>false, 'msg' => "The Refund Amount is Greater Than Lead Net Amount!"]);
            }
           
         
        }
        else
        {
              return response()->json(['status' => false, 'msg' => "Lead Not Found"]);
        }
            
        }
        
      
        
         
         
        //  return response()->json(['data'=>$user_id]);
         
       



    }
    public function UpdatedAmountOfLead($newAmount, $leadCode,$msg, $id= null){
        
        
        
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
            // $total = ($leadAmount + $reversalAmount - $chargeBackAmount - $RefundAmount - $PurchaseAmount - $newAmount);
            
            // $total = $RefundAmount - $newAmount;
            
            if($id !== null){
                $cAmount = RefundModel::where('id', $id)->first();
                if($cAmount){
                    $total = ($leadAmount + $reversalAmount + $cAmount->refund_amount - $chargeBackAmount - $RefundAmount - $PurchaseAmount - $newAmount);
                }
            }
        }
        else
        {
            $total = ($leadAmount + $reversalAmount - $chargeBackAmount - $RefundAmount - $PurchaseAmount - $newAmount);
        }
        
        
        // return response()->json(['status'=>false,"data"=>$total]);
        // exit;
        if($total >=0)
        {
            return true;
        }
        else
        {
            return false;
        }
        
    }

    public function refund_view(RefundModel $refund)
    {

        $refund = RefundModel::with(
            'leaddetail',
            'refunduser',
            'merchantdetail'
        )->where('id', $refund->id)->first();
        
        $refund->leaddetail->gross = $this->getCurrentLeadAmount($refund->leaddetail);
        return response()->json(['status' => true, 'msg' => "Refund Get Success", 'data' => $refund,'permission'=>$this->user_permission(auth()->user())]);
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

    public function refund_listing()
    {
        $data = "";
        
         if(auth()->user()->role->id == 4 && auth()->user()->permission !== null && (auth()->user()->permission==2 || auth()->user()->permission==3))
         {
            $data = RefundModel::with(
                'leaddetail',
                'refunduser',
                'merchantdetail',
                'unit'
            )->Where('refund_user_id',auth()->user()->id)->orderby('refund_date','DESC')->get();
             
         }
         else if((auth()->user()->role->id == 3 && auth()->user()->permission==null) || (auth()->user()->role->id == 4 && auth()->user()->permission==1))
         {
              $units= json_decode(auth()->user()->unit_id);

             $data = RefundModel::with(
                'leaddetail',
                'refunduser',
                'merchantdetail',
                'unit'
            )->whereIn('unit_id',$units)->orderby('refund_date','DESC')->get();
         }
         else
         {
              $data = RefundModel::with(
                'leaddetail',
                'refunduser',
                'merchantdetail',
                'unit'
            )->orderby('refund_date','DESC')->get();
         }

        return response()->json(['status' => true, 'msg' => "Refund List Get Success", 'data' => $data,'permission'=>$this->user_permission(auth()->user())]);
    }

    public function refund_delete( $refund)
    {
        $refund = RefundModel::where('id',$refund)->first();
        $logMsg = 'Lead ('.$refund->lead_code.') deleted by the user';
        $logs= ['user'=>auth()->user()->id,'unit'=>intval($refund->unit_id),'type'=>'refunds','msg'=>$logMsg];
        if($refund)
        {
              //Logs
               $this->generateLogs($logs);
              //End Logs
        $refund->delete();

        return response()->json(["status" => true, 'msg' => "Refund Delete Success", 'data' => $refund,'permission'=>$this->user_permission(auth()->user())]);
        }
        else
        {
             return response()->json(["status" => false, 'msg' => "Refund Didn't Exists!"]);
        }
        
    }
}
