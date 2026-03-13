<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssigneeStatus extends Model
{
    use HasFactory;

    protected $table = 'assignee_status_list';

    protected $fillable = [
        'project_id',
        'activity',
        'created_by',
        'created_date',
        'createdby_name',
        'is_read',
    ];

    public function statusData()
    {
        return $this->belongsTo(EntryProcessModel::class, 'project_id')
        ->select('id','entry_date','title','project_id','type_of_work','email','institute','department','profession','budget','process_status','hierarchy_level','created_by','project_status','assign_by','assign_date','projectduration');
    }
    
}
