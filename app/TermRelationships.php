<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TermRelationships extends Model
{
    protected $table = 'kibarer_term_relationships';
    

    public function TermTaxonomy(){
        return $this->belongsTo('App\TermTaxonomy','term_taxonomy_id','term_taxonomy_id');
    }
}
