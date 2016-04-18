<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Users extends Model
{
    protected $table = 'kibarer_users';


    public function UsersMeta(){
        return $this->hasMany('App\UsersMeta','user_id','term_id');
    }

    public function Favorites(){
        return $this->hasMany('App\Favorites','user_id','ID');
    }
}
