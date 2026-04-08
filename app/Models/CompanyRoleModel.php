<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyRoleModel extends Model
{
    use HasFactory;
    protected $table = 'company_roles';
    
    protected $fillable = [
        'name',  
        'status',
        'is_deleted',
    ];
}
