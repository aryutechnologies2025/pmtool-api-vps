<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstitutionModel extends Model
{
    use HasFactory;
protected $table = 'institutions';

protected $fillable = [
    'name',  
    'status',
    'is_deleted'
];
}
