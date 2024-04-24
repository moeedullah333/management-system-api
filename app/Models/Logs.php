<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Logs extends Model
{
    use HasFactory;

    
    protected $table = 'logs';
    
    
    public function user()
    {
       return $this->hasOne(User::class,'id','user_id');
    }
    
    public function unit()
    {
       return $this->hasOne(Units::class,'id','unit_id');
    }
    
}
