<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Roles;
use App\Models\Units;
use App\Models\Logs;
use App\Models\Brands;
use App\Models\UnitBrands;
use App\Models\UserUnits;
use App\Models\UnitTarget;
use App\Models\Leads;
use App\Models\UserTargetsModel;
use App\Models\UserPermissionModel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\ChargeBackModel;
use App\Models\PurchaseModel;
use App\Models\SourceModel;
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

class ItController extends Controller
{
    
    public function changeData()
    {
        $l = Leads::get();
        foreach($l as $k => $v)
        {
            $v->email = 'abc@gmail.com';
            $v->phone = '12345';
            $v->save();
        }
    }
    
    public function testData() //Account Management
    {
        $all_leads=Leads::get()->toArray();
        $sorted=[];
        $bulk=[];
        foreach($all_leads as $lead)
        {
         
            if(intval($lead['source'])==1)
            {
                $sorted['cu_name'] = $lead['name'];
                $sorted['code'] = $lead['code'];
                $sorted['other']= [];
                $bulk[]=$sorted;
            }
        }
       
        foreach($all_leads as $lead)
        {
            foreach($bulk as $key => $b)
            {
                if(($b['cu_name']==$lead['name']) && intval($lead['source']) !== 1 )
                {
                    $amount['gross'] = $lead['gross'];
                    $amount['cu_name'] = $lead['name'];
                    $amount['code'] = $lead['code'];
                    $b['other'][] = $amount;
                    $bulk[$key] = $b;
                }
            }
        }
        dd($bulk[0],$bulk[1],$bulk[2]);
    }
    
    public function showData()
    {
        $leads=Leads::where('source',12)->get()->toArray();
        
        return view('test-data',compact('leads'));
        
    }
    
    
    public function get_logs()
    {
        $logs = Logs::orderBy('id', 'DESC')->with('user','unit')->get();
        // dd($logs);
        return view('logs',compact('logs'));
    }
    
    public function get_sources()
    {
        $sources = SourceModel::get()->toArray();
        
        return response()->json(['status'=>true, 'sources'=>$sources]);
    }
}
