<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChargeBackModel extends Model
{
    use HasFactory;
    protected $table = "charge_back";

    public function leaddetail()
    {
        return $this->hasOne(Leads::class, 'code', 'lead_code');
    }
    public function chargebackuser()
    {
        return $this->hasOne(User::class, 'id', 'chargeback_user_id');
    }
    public function merchantdetail()
    {
        return $this->hasOne(MerchantModel::class, 'id', 'merchant_id');
    }
    public function unit()
    {
        return $this->hasOne(Units::class, 'id', 'unit_id');
    }
}
