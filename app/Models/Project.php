<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = ['project_id', 'name', 'data'];

    protected $casts = [
        'data' => 'array' // Tells Laravel to treat JSON as a PHP array
    ];
}