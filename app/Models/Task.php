<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $fillable = [  //allows mass assignment for these fields
        'title',
        'description',
        'status',
        'due_date',
    ];
}

//without mass assignment, you would have to assign each attribute individually, like this:
// with mass assignment, assigning multiple database fields at once using array.