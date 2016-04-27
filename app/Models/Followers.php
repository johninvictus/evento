<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Followers extends Model
{
    protected $table='following';

    public function user(){
        return $this->belongsTo('App\Models\User','user_id');
    }



}
