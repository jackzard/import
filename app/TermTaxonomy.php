<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TermTaxonomy extends Model
{
    protected $table = 'kibarer_term_taxonomy';

    public function Terms(){
        return $this->belongsTo('App\Terms','term_id','term_id');
    }
}
