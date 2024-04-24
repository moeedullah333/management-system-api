<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Leads extends Model
{
    use HasFactory;

    protected $table = 'leads';
     public function unitdetail()
    {
        return $this->hasOne(Units::class,'id','unit_id');
    }
    public function salesrep(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'sales_rep')->select(['id', 'name']);
    }
    public function accountrep(): HasOne
    {
        return $this->hasOne(Units::class, 'id', 'unit_id')->with('currentMonthTarget')->select(['id', 'name']);
    }
     public function accountrepdetail()
    {
        return $this->hasOne(User::class, 'id', 'account_rep')->select(['id', 'name']);
    }
    public function getbrand(): HasOne
    {
        return $this->hasOne(Brands::class, 'id', 'brand')->select(['id', 'name']);
    }
    public function getsource(): HasOne
    {
        return $this->hasOne(SourceModel::class,'id','source')->select(['id','name']);
    }
}
