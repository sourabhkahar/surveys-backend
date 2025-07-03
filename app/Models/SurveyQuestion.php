<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SurveyQuestion extends Model
{
    //
    protected $fillable = ['question', 'options', 'type', 'survey_id', 'description','section_id'];
   
    public function survey(){
        return $this->belongsTo(Survey::class);
    }

    public function section(){
        return $this->belongsTo(section::class);
    }
}
