<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $table = 'kibarer_posts';

    public function TermRelationships(){
        return $this->hasMany('App\TermRelationships','object_id', 'ID');
    }

    public function Users(){
        return $this->belongsTo('App\Users','post_author','ID');
    }

    public function Attachment(){
        return $this->hasMany('App\Post','post_parent','ID');
    }

    public function PostMeta(){
        return $this->hasMany('App\PostMeta','post_id','ID');
        
    }

    public function translate(){
        return $this->belongsTo('App\Translate','ID','element_id');
    }
}
