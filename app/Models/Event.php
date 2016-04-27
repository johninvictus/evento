<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $table = 'events';

    public function tags()
    {
        return $this->belongsToMany('App\Models\Tags');
    }

    public function comments(){
        return $this->hasMany('App\Models\Comments','event_id');
    }
}
