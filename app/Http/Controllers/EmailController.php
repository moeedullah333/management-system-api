<?php
namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\Request;
use Mail;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Exception;
use Twilio\Rest\Client;

class EmailController extends Controller
{
    
    public function sendEmailCode(Request $req)
    {

        $randomNum = substr(str_shuffle("0123456789"), 0, 5);
        $user = User::where('id',Auth::user()->id)->first();
        $user->email_code = $randomNum;
        if($user->save())
        {
            $details = [
            'code' => $randomNum,
            ];
       $email=  \Mail::to('test@custom.mystagingserver.site')->send(new \App\Mail\EmailVerificationMail($details));
        }
     
        return ['status'=>1,'email'=>$email];
        
    }
    
    public function VerifyCode(Request $req)
    {
      
        if($req->has('code'))
        {
            if($req->type=="email")
            {
            
                if(Auth::user()->email_code!=='' && Auth::user()->email_code == $req->code)
                {
                    $user = User::where('id',Auth::user()->id)->first();
                    $user->email_verified_at = Carbon::now();
                    $user->save();
                    return ['status'=>1,'msg'=>'Email Verified'];
                }
                else
                {
                    return ['status'=>0,'error'=>'Resend Email Code or Code you have entered is worng'];
                }
            }
            if($req->type=="mobile")
            {
                if(Auth::user()->mobile_code!=='' && Auth::user()->mobile_code == $req->code)
                {
                    $user = User::where('id',Auth::user()->id)->first();
                    $user->mobile_verified_at = Carbon::now();
                    $user->save();
                    return ['status'=>1,'msg'=>'Number Verified'];
                }
                else
                {
                    return ['status'=>0,'error'=>'Resend Mobile Code or Code you have entered is worng'];
                } 
            }
            return ['staus'=>0,'error'=>'Type is not defined. (Email/Mobile)'];
            
        }
        else
        {
            return ['status'=>1,'msg'=>'Kindly Enter Code'];
        }
    }


    public function SendOtpMobile()
    {
        $randomNum = substr(str_shuffle("0123456789"), 0, 5);
        $user = User::where('id',Auth::user()->id)->first();
        $user->mobile_code= $randomNum;
        if($user->save())
        {
            $receiverNumber = Auth::user()->number;
            $message = "This is your code ".$randomNum;

            try {

                $account_sid = getenv("TWILIO_SID");
                $auth_token = getenv("TWILIO_TOKEN");
                $twilio_number = getenv("TWILIO_FROM");

                $client = new Client($account_sid, $auth_token);
                $client->messages->create($receiverNumber, [
                    'from' => $twilio_number, 
                    'body' => $message]);

                return ['status'=>1, 'Msg'=>'Msg Sent Successfully'];

            } catch (Exception $e) {
             
                return ['error'=>$e->getMessage()];
            }
        }
        
    }
}