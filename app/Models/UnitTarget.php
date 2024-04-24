<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Units;
use Carbon\Carbon;

class UnitTarget extends Model
{
    use HasFactory;
    public $table = 'unit_targets';

    protected $fillable = ['unit_id', 'target', 'month', 'year', 'score', 'status'];
    
     public function unit()
    {
        return $this->belongsTo(Units::class);
    }
    
  

}
