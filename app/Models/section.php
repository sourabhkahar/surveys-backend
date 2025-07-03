<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class section extends Model
{
    protected $fillable = [ 'paper_id', 'section_name', 'section_type', 'total_marks', 'caption'];

    public function paper()
    {
        return $this->belongsTo(Paper::class, 'paper_id');
    }

    public function questions()
    {
        return $this->hasMany(SurveyQuestion::class, 'section_id');
    }
}
