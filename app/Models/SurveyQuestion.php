<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SurveyQuestion extends Model
{
    //
    protected $fillable = ['question', 'options', 'type', 'survey_id', 'description'];
   
    public function survey(){
        return $this->belongsTo(Survey::class);
    }

}
