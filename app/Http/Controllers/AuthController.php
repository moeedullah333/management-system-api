<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Token;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\ForgetOtp;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\UserPermissionModel;
use Carbon\Carbon;
use Exception;
use Twilio\Rest\Client;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    use ApiResponser;

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
    public function register(Request $request)
    {

        // $requiredValidator = \Validator::make($request->all(), [
        // 'name' => 'required',
        // 'email' => 'required',
        // 'password' => 'required',
        // 'user_role'=>'required',
        // 'unit_id'=>'required',
        // ]);
        // if ($requiredValidator->fails()) {
        //     $responseError = [];
        //     $errors = $requiredValidator->errors()->toArray();
        //         foreach ($errors as $field => $message) {
        //             if (strpos(trim($field), "_") !== false) {
        //                 $fieldName =  ucfirst(implode(" ", explode("_", trim($field))));
        //             } else {
        //                 $fieldName =  ucfirst(trim($field));
        //             }
        //             $errorFields[] = $fieldName;
        //         }
        //         $numFields = count($errorFields);
        //         $fieldNames = implode(", ", $errorFields);
        //         $responseError['email'] = "The " . ($numFields > 1 ? $fieldNames . " fields are" : $fieldNames . " field is") . " required.";
        //     return response()->json(['status' => false, 'errors' => $responseError], 400);
        // }
        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'email' => 'string|email|unique:users,email',
            'password' => 'string',
            'user_role' => 'required',
            'unit_id' => 'required',
        ]);

        //string validation not working 
        if ($validator->fails()) {
            $errors = $validator->errors()->toArray();
            return response()->json(['status' => false, 'errors' => $errors], 400);
        }

        $user = new User();
        $user->name = $request->name;
        $user->password = $request->password;
        $user->email = $request->email;
        $user->user_role = $request->user_role;
        $user->unit_id = $request->unit_id;
        $user->status = 1;
        if ($user->save()) {
            return $this->success([
                'token' => $user->createToken('API Token')->plainTextToken
            ]);
        } else {
            return response()->json(['status' => false, 'errors' => 'some error'], 500);
        }
    }

    public function login(Request $request)
    {


        $attr = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string'
        ]);

        if (!Auth::attempt($attr)) {
            return $this->error('Login credentials do not match', 401);
        }

        if (auth()->user()->status == 0) {
            return response()->json(['status' => false, 'msg' => 'This Account is In Active ! Kindly Contact Administrator']);
        }
        //   dd(auth()->user());
        return $this->success([
            'token' => auth()->user()->createToken('API Token')->plainTextToken,
            'role' => auth()->user()->user_role,
            'permission' => $this->user_permission(auth()->user())
        ]);
    }

    public function logout()
    {
        auth()->user()->tokens()->delete();

        return [
            'message' => 'Tokens Revoked',
            'status' => true
        ];
    }

    public function nouser()
    {
        return $this->error('Login credentials do not match', 401);
    }

    public function useredit(Request $req)
    {
        $user = User::where('id', Auth::user()->id)->first();
        $user->name = (isset($req->name) ? $req->name : Auth::user()->name);
        $user->email = (isset($req->email) ? $req->email : Auth::user()->email);
        $user->user_role = (isset($req->user_role) ? $req->user_role : Auth::user()->user_role);
        $user->unit_id = (isset($req->unit_id) ? $req->unit_id : Auth::user()->unit_id);
        if ($user->save()) {
            return [
                'message' => 'Profile Updated',
                'status' => true
            ];
        }
        return $this->error('Error In saving data', 500);
    }
    public function checktoken(Request $req)
    {


        $parts = explode('|', $req->token);
        $numberBeforePipe = $parts[0];


        $token =  Token::where('id', $numberBeforePipe)->first();


        if ($token == null) {
            return Response()->json(['status' => false, 'message' => 'user did not exists!']);
        } elseif ($token->exists()) {
            return Response()->json(['status' => true, 'message' => 'user token already exists!']);
        }
    }

    public function forgot_password(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required',
        ]);

        if ($validator->fails()) {
            return Response(['status' => false, 'error' => $validator->errors()], 401);
        }

        $opt = rand(1000, 9999);
       
        // dd($opt);
        // $currentDate = Carbon::now()->format('d-M-Y');

        $check_user = User::where('email', $request->email)->select('id', 'email')->first();
        // dd($check_user);
        if (!isset($check_user)) {
            return response()->json(['status' => false, 'message' => "User not found"]);
        }

        ForgetOtp::updateOrCreate(
            ['email' => $request->email],
            [
                'email'     => $request->email,
                'otp'      => $opt
            ]
        );

        $data = [
            'email' => $request->email,
            'details' => [
                'heading' => 'Forget Password Opt',
                'content' => 'Your forget password otp : ' . $opt,
                'WebsiteName' => 'MtRecords'
            ]

        ];
        // Mail::to($request->email)->send(new ForgotOtpMail($data));
        $datamail = Mail::send('mail.sendopt', $data, function ($message) use ($data) {
            $message->to($data['email'])->subject($data['details']['heading']);
        });

      
        return response()->json(['status' => true, 'data' => $check_user, 'message' => "OTP send on your email address"]);
    }
    public function otp_verification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required',
            'otp' => 'required',
        ]);

        if ($validator->fails()) {
            return Response(['status' => false, 'error' => $validator->errors()], 401);
        }

        $user = ForgetOtp::where(['email' => $request->email, 'otp' => $request->otp])->first();
        if (!isset($user)) {
            return response()->json(['status' => false, 'message' => "Otp is wrong"]);
        }
        $data['email'] = $user->email;
        $data['code'] = $user->otp;

        return response()->json(['status' => true, 'data' => $data]);
    }

    public function reset_password(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required',
            'password' => 'required|confirmed',
        ]);

        if ($validator->fails()) {
            return Response(['status' => false, 'error' => $validator->errors()], 401);
        }

        // dd(uniqid());
        $get_otp = ForgetOtp::where(['email' => $request->email, 'otp' => $request->otp])->first();
        if (!isset($get_otp)) {
            return response()->json(['status' => false, 'message' => "Otp is wrong"]);
        } else {
            $get_otp->delete();
        }
        $user = User::where('email', $request->email)->first();
        $user->password = bcrypt($request['password']);

        if ($user->save()) {
            return response()->json(['status' => true, 'message' => "Password Reset"]);
        }
    }
}
