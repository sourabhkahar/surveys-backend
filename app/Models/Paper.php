<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Paper extends Model
{
    protected $fillable = [ 'title','subject','standard','paper_date'];

    public function sections()
    {
        return $this->hasMany(Section::class, 'paper_id');
    }

}
