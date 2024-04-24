<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserTargetsModel extends Model
{
    use HasFactory;
    protected $table = "user_targets";

    public function unit_detail()
    {
        return $this->hasOne(Units::class, 'id', 'unit_id');
    }
    public function user_detail()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
    public function user_lead_detail()
    {
        return $this->hasMany(Leads::class, 'account_rep', 'user_id')->select('account_rep','gross','created_at');
    }
    
}
