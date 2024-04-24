<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MerchantModel extends Model
{
    use HasFactory;

    protected $table = "merchant";
    
      public function getCreatedAtAttribute($value)
    {
        return date('Y-n-d',strtotime($value));
    }
    
}
