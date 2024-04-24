<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use SebastianBergmann\CodeCoverage\Report\Xml\Unit;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
    public function role()
    {
        return $this->hasOne(Roles::class,'id','user_role')->select('id','name');
    }

    public function user_target()
    {
       return $this->hasMany(UserTargetsModel::class,'user_id','id')->select('user_id','target','month','year','unit_id');
    }

    public function userleads()
    {
        return $this->hasMany(Leads::class,'sales_rep','id')->select('sales_rep','unit_id','gross','created_at','code','date');
    }
    public function userrefunds()
    {
        return $this->hasMany(RefundModel::class,'refund_user_id','id')->select('refund_user_id','refund_amount','refund_type','unit_id','created_at','refund_date');
    }
    public function userchargeback()
    {
        return $this->hasMany(ChargeBackModel::class,'chargeback_user_id','id')->select('chargeback_user_id','chargeback_amount','unit_id','created_at','chargeback_date');
    }
    public function userpurchase()
    {
        return $this->hasMany(PurchaseModel::class,'purchase_user_id','id')->select('purchase_user_id','purchase_amount','unit_id','created_at','purchase_date');
    }
    public function userreversal()
    {
        return $this->hasMany(ReversalModel::class,'reversal_user_id','id')->select('reversal_user_id','reversal_amount','unit_id','created_at','reversal_date');
    }
   
}
