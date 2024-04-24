<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Units extends Model
{
    use HasFactory;

    
    protected $table = 'units';
    
     public function unit_Brands()
    {
        return $this->hasMany(UnitBrands::class, 'unit_id', 'id')->with('brands')->select(['id','unit_id','brand_id']);
    }
   
    // public function user(): HasOne
    // {
    //     return $this->hasOne(User::class, 'id', 'user_id')->select(['id', 'name']);
    // }
    public function MonthTarget(): HasMany
    {
        
        return $this->hasMany(UnitTarget::class, 'unit_id', 'id');
    }
    
    public function unit_leads()
    {
      return $this->hasMany(Leads::class,'unit_id','id')->select('id','unit_id','gross','created_at');
    }
  
    
  
    
    public function currentMonthTarget(): HasOne
    {
        $month = date('m');
        $year = date('Y');
        
        return $this->hasOne(UnitTarget::class, 'unit_id', 'id')->where('month',$month)->where('year',$year)->select('unit_id','target');
     
    }
}
