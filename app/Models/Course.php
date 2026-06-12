<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    protected $fillable = ['title', 'instructor_id'];

    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }
}
