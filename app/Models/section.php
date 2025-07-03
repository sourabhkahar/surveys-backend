<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    protected $fillable = [ 'paper_id', 'section_name', 'section_type', 'total_marks', 'caption'];

    public function papers()
    {
        return $this->belongsTo(Paper::class, 'paper_id');
    }

    public function questions()
    {
        return $this->hasMany(SurveyQuestion::class, 'section_id');
    }
}
