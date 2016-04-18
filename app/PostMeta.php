<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PostMeta extends Model
{
    protected $table = 'kibarer_postmeta';

    public function Options(){
        return $this->belongsTo('App\Options','meta_key','option_name');
    }
}
