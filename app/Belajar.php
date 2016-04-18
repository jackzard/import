<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Belajar extends Model
{

    protected $fillable = ['name','description','nomor'];

    public function pelajaran(){
        return $this->hasMany('App\Pelajaran','belajar_id');
    }
}
