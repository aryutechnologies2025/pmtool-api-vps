<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class People extends Model
{
    use HasFactory;

    protected $connection = 'mysql_medics_hrms';
    protected $table = 'employee_details';


    public function createdByUser()
    {
        return $this->belongsTo(Roles::class, 'position', 'id');
    }

    


    public function getCreatedByUsersAttribute()
    {
        $positionIds = explode(',', $this->position);
        return Roles::whereIn('id', $positionIds)->get(['id', 'name']);
    }

}
