<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectViewStatus extends Model
{
    use HasFactory;

    protected $table ='projectviewstatus';

    protected $fillable = [
        'project_id',
        'project_status',
       
    ];

    public function projectViews(){
        return $this->belongsTo(EntryProcessModel::class, 'project_id', 'id')
        ->select('id','entry_date','title','project_id','type_of_work','email','institute','department','profession','budget','process_status','hierarchy_level','created_by','project_status','assign_by','assign_date','projectduration');
       
    }
       
}