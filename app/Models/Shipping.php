<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shipping extends Model
{
    use HasFactory;
    public function relationTocountry(){
        return $this->hasOne(Country::class, 'id' , 'country_id');
    }
}
