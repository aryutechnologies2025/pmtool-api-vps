<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Author extends Model
{
    use HasFactory;
    protected $table = 'publication_author_forms';
    protected $fillable = [
        'project_id',
        'initial',
        'first_name',
        'last_name',
        'profession_id',
        'department_id',
        'institute_id',
        'state',
        'country',
        'email',
        'phone',
        'created_by'
    ];
    
    public function project(){
        return $this->belongsTo(EntryProcessModel::class, 'project_id', 'id');
    }
    
    public function department(){
        return $this->belongsTo(DepartmentModel::class, 'department_id', 'id')->select('id', 'name', 'is_deleted');
    }
    
    public function institute(){
        return $this->belongsTo(InstitutionModel::class, 'institute_id', 'id')->select('id', 'name', 'is_deleted');
    }
    public function profession(){
        return $this->belongsTo(ProfessionModel::class, 'profession_id', 'id')->select('id', 'name', 'is_deleted');
    } 
}