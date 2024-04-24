<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReversalModel extends Model
{
    use HasFactory;

    protected $table = "reversal";

    public function leaddetail()
    {
        return $this->hasOne(Leads::class, 'code', 'lead_code');
    }
    public function reversaluser()
    {
        return $this->hasOne(User::class, 'id', 'reversal_user_id');
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
