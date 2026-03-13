<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectModel extends Model
{
    use HasFactory;
    protected $table = 'project-types';
    
    protected $fillable = [
        'institution',  
        'status',
        'is_deleted',
       'created_by'
    ];
}
