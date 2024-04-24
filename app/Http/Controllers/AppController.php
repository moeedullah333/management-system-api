<?php
namespace App\Http\Controllers;
use App\Models\User;
use App\Models\Inquiry;
use Illuminate\Http\Request;
use Mail;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Exception;
use Twilio\Rest\Client;

class AppController extends Controller
{
    
    public function newInquiry(Request $req, Inquiry $inq)
    {
        $requiredValidator = \Validator::make($req->all(), [
        'issue' => 'required',
        'message' => 'required',
        ]);
        
        if ($requiredValidator->fails()) 
        {
            $errors = $requiredValidator->errors()->toArray();
            return response()->json(['status'=>false,'errors'=>$errors]);
        }
        else
        {
            $inq->user_id = Auth::user()->id;
            $inq->status=1;
            $inq->issue = $req->issue;
            $inq->message= $req->message;
            if($inq->save())
            {
                return response()->json(['status'=>true,'message'=>'Issue Submitted Successfully']);
            }
            else
            {
                return response()->json(['status'=>false,'errors'=>'Internal Error']);
            }
        }
    }
    
}