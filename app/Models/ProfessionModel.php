<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfessionModel extends Model
{
    use HasFactory;
    protected $table = 'profession_types';
    
    protected $fillable = [
        'name',  
        'status',
        'is_deleted'
    ];
}
