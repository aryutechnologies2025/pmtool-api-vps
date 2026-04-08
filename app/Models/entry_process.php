<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class entry_process extends Model
{
    use HasFactory;
    protected $table = 'entry_process';
    protected $fillable = [
        'statistics',
        'others',
        'name',
        'profession',
        'title',
        'hierarchy_level',
        'select_statistics',
        'institute',
        'contact_number',
        'free_text',
        'department',
        'email',
        'status',
        'created_by',
        'is_deleted',
        'entry_date'
    ];

    public function institution()
    {
        return $this->belongsTo(InstitutionModel::class, 'institute', 'id');
    }
    
    public function department()
    {
        return $this->belongsTo(DepartmentModel::class, 'department', 'id');
    }

    public function profession()
    {
        return $this->belongsTo(ProfessionModel::class, 'profession', 'id');
    }
}
