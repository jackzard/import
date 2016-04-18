<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Terms extends Model
{
    protected $table = 'kibarer_terms';

    public function check2(){
        return $this->belongsTo('App\TermRelationships','term_id','term_taxonomy_id');
    }
}
