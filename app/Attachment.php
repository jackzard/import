<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    protected $table = 'kibarer_posts';

    public function translate(){
        return $this->belongsTo('App\Translate','post_parent','element_id');
    }
}
