<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DepartmentModel extends Model
{
    use HasFactory;
    protected $table = 'department_types';
    
    protected $fillable = [
        'name',  
        'status',
        'is_deleted'
    ];
}
