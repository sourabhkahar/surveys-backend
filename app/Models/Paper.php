<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Paper extends Model
{
    protected $fillable = [ 'title'];

    public function sections()
    {
        return $this->hasMany(Section::class, 'paper_id');
    }

}
