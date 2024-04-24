<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use App\Models\Leads;
use App\Models\Logs;
use App\Models\UserPermissionModel;
use Carbon\Carbon;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
    
     public function user_permission($user)
    {
        
        // dd($user);
        $data = UserPermissionModel::query();
        
        $data->where('role',$user->user_role);
        
        
        if($user->permission){
            $data->where('child_role',$user->permission);
            
        }
        
        $data = $data->first();
        
       
        
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


        return $data;
    }
    
    public function getSalesbyUnit($id)
    {
  
        $data['total_amount'] = Leads::where('unit_id',$id)->sum('gross');
        
        return $data;
    }
    
    public function MonthlyScore($data)
    {
        $start = Carbon::createFromDate($data['year'],$data['month'], 1)->startofMonth()->format('Y-m-d');
        $end = Carbon::createFromDate($data['year'],$data['month'], 1)->endOfMonth()->format('Y-m-d');

        $res = Leads::whereBetween('created_at',[$start,$end])->where('unit_id',$data['unitid'])->sum('gross');
        
        return $res;
        
    }
    
    
    public function generateLogs($logs)
    {
        // dd($logs);
        $addLogs = new Logs();
        $addLogs->user_id = $logs['user'];
        $addLogs->unit_id = $logs['unit'];
        $addLogs->log_type = $logs['type'];
        $addLogs->log_msg = $logs['msg'];
        $addLogs->save();
        return true;
    }
}
