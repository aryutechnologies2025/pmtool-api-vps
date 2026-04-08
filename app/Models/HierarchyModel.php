<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HierarchyModel extends Model
{
    use HasFactory;
    protected $table = 'hierarchy-types';
    
    protected $fillable = [
        'institution',  
        'status',
        'is_deleted'
    ];
}
