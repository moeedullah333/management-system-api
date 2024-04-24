<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class UnitBrands extends Model
{
    use HasFactory;

    
    protected $table = 'unit_brands';
    
    public function unit()
    {
        return $this->belongsTo(Units::class, 'id', 'unit_id');
    }
    
    public function brands()
    {
        return $this->hasOne(Brands::class, 'id', 'brand_id')->select(['id','name']);
    }
    
    //   public function unit_brand(): HasOne
    // {
    //     return $this->hasOne(Brands::class, 'id' , 'brand_id')->select(['id', 'name']);
    // }
    
    //   public function brand_unit(): HasOne
    // {
    //     return $this->hasOne(Units::class, 'id' , 'unit_id')->select(['id', 'name']);
    // }
    
    
}
