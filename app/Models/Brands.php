<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Brands extends Model
{
    use HasFactory;

    
    protected $table = 'brands';
    
    // public function user(): HasOne
    // {
    //     return $this->hasOne(User::class, 'id', 'user_id')->select(['id', 'name']);
    // }
    
    //  public function unit()
    // {
    //     return $this->belongsTo(UnitBrands::class, 'brand_id', 'id');
    // }
    
    
    
    // new relaction
     public function brands()
    {
        return $this->belongsTo(UnitBrands::class, 'brand_id', 'id');
    }
}
