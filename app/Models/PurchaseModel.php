<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseModel extends Model
{
    use HasFactory;

    protected $table = "purchase";

    public function leaddetail()
    {
        return $this->hasOne(Leads::class, 'code', 'lead_code');
    }
    public function purchaseuser()
    {
        return $this->hasOne(User::class, 'id', 'purchase_user_id');
    }
    public function unit()
    {
        return $this->hasOne(Units::class, 'id', 'unit_id');
    }
    
}
