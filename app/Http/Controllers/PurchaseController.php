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

class PurchaseController extends Controller
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
    public function purchase_add_update(Request $req, PurchaseModel $purchase)
    {
        $msg = isset($purchase->id) ? 'Updated Succesfully' : 'Added Succesfully';


        if (!isset($purchase->id)) {
            $validator = Validator::make($req->all(), [
                'lead_code' => 'required',
                'purchase_amount' => 'required',
                "purchase_date" => 'required',
                "purchase_user_id" => 'required',
                "purchase_type" => 'required',
                "reason" => 'required',
            ]);

            if ($validator->fails()) {
                $errors = $validator->errors()->toArray();
                return response()->json(['status' => false, 'msg' => $errors], 400);
            }
        }

      
        $lead = Leads::where('code', strtolower($req->lead_code))->first();
 
         if($lead) 
         {
            if($this->UpdatedAmountOfLead(floatval($req->purchase_amount),$req->lead_code,isset($purchase->id) ? "Edit" : "Add",isset($purchase) ? $purchase->id : null))
            {
                
                $purchase->lead_code = isset($req->lead_code) ? strtolower($req->lead_code) : $purchase->lead_code;
                $purchase->purchase_amount = isset($req->purchase_amount) ? $req->purchase_amount : $purchase->purchase_amount;
                $purchase->purchase_user_id = $req->purchase_user_id;
                // $purchase->purchase_user_id = isset($req->purchase_user_id) ? $req->purchase_user_id : $purchase->purchase_user_id;
                $purchase->unit_id = $lead->unit_id;
                $purchase->purchase_type = isset($req->purchase_type) ? $req->purchase_type : $purchase->purchase_type;
                $purchase->purchase_date = isset($req->purchase_date) ? $req->purchase_date : $purchase->purchase_date;
                $purchase->reason = isset($req->reason) ? $req->reason : $purchase->reason;
                $lead->status = "Purchase";
                if ($purchase->save() && $lead->save()) {
                    
                    
                    //Logs
                            $logMsg = 'Lead '.$purchase->lead_code.' '.$msg.' by the user';
                            $logs= ['user'=>auth()->user()->id,'unit'=>$purchase->unit_id,'type'=>'purchases','msg'=>$logMsg];
                            $this->generateLogs($logs);
                        //End Logs
        
                    return response()->json(['status' => true, 'msg' => "Purchase " . $msg, 'data' => $purchase]);
                } else {
                    return response()->json(['status' => false, 'msg' => "Something Went Wrong!"]);
                }
            }
            else
            {
                 return response()->json(['status' => false,'check'=>false ,'msg' => "The Purchase Amount is Greater Than Lead Net Amount!"]);
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
            // $total = ($leadAmount + $reversalAmount - $chargeBackAmount - $RefundAmount - $PurchaseAmount - $newAmount);
            
            // $total = $PurchaseAmount - $newAmount;
            
            if($id !== null){
                $cAmount = PurchaseModel::where('id', $id)->first();
                if($cAmount){
                    $total = ($leadAmount + $reversalAmount + $cAmount->purchase_amount - $chargeBackAmount - $RefundAmount - $PurchaseAmount - $newAmount);
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
    public function purchase_view(PurchaseModel $purchase)
    {
        $purchase = PurchaseModel::with(
            'leaddetail',
            'purchaseuser'
        )->where('id', $purchase->id)->first();
        
         $purchase->leaddetail->gross = $this->getCurrentLeadAmount($purchase->leaddetail);

        return response()->json(['status' => true, 'msg' => "Purchase Get Success", 'data' => $purchase,'permission'=>$this->user_permission(auth()->user())]);
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
    public function purchase_listing()
    {
        $data = "";
        
         if(auth()->user()->role->id == 4 && auth()->user()->permission !== null && (auth()->user()->permission==2 || auth()->user()->permission==3))
        {
            $data = PurchaseModel::with(
            'leaddetail',
            'purchaseuser',
            'unit'
            )->Where('purchase_user_id',auth()->user()->id)->orderby('purchase_date','DESC')->get();
        }
        else if((auth()->user()->role->id == 3 && auth()->user()->permission==null) || (auth()->user()->role->id == 4 && auth()->user()->permission==1) )
        {
            $units= json_decode(auth()->user()->unit_id);
            $data = PurchaseModel::with(
            'leaddetail',
            'purchaseuser'
             )->whereIn('unit_id',$units)->orderby('purchase_date','DESC')->get();
        }
        else
        {
            $data = PurchaseModel::with(
            'leaddetail',
            'purchaseuser',
            'unit'
            )->orderby('purchase_date','DESC')->get();
        }

        return response()->json(['status' => true, 'msg' => "Purchase List Get Success", 'data' => $data,'permission'=>$this->user_permission(auth()->user())]);
    }

    public function purchase_delete($purchase)
    {
       $purchase = PurchaseModel::where('id',$purchase)->first();
        
        if($purchase)
        {
            //Logs
                            $logMsg = 'Purchase for '.$purchase->lead_code.' deleted by the user';
                            $logs= ['user'=>auth()->user()->id,'unit'=>$purchase->unit_id,'type'=>'purchases','msg'=>$logMsg];
                            $this->generateLogs($logs);
                        //End Logs
            
            $purchase->delete();
    
            return response()->json(["status" => true, 'msg' => "Purchase Delete Success", 'data' => $purchase,'permission'=>$this->user_permission(auth()->user())]);
        }
        else
        {
            return response()->json(["status" => false, 'msg' => "Purchase Didn't Exists!"]);
        }
    }
}
