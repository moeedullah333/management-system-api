<?php 
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class UserUnits extends Model 
{
    use HasFactory;
    
    protected $table = "user_units";


    public function user()
    {
       return $this->hasOne(User::class,'id','user_id');
    }
    public function unit()
    {
       return $this->hasMany(Units::class,'id','unit_id');
    }
    
}