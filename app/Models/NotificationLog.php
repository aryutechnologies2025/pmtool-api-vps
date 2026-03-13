<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Phar;

class NotificationLog extends Model
{
    use HasFactory;
    protected $fillable = [
        'entry_process_id',
        'project_id',
        'position_id',
        'message',
        'status',
       
    ];

    // Either remove the casts completely if not needed:
protected $casts = [
    // no need to cast 'message' at all since it's a string
];

    
    protected $table = 'notification_logs';
    public function entryProcess()
    {
        return $this->belongsTo(EntryProcessModel::class, 'id');
    }
    public function project()
    {
        return $this->belongsTo(EntryProcessModel::class, 'project_id')
            ->select('id', 'project_id','title', 'type_of_work', 'email', 'institute', 'department', 'profession', 'budget', 'process_status', 'hierarchy_level', 'created_by', 'project_status', 'assign_by', 'assign_date', 'projectduration','client_name')
            ->with ([
               'userData1'
            ]);
    }
    public function assignedPosition()
    {
        return $this->belongsTo(ProjectAssignDetails::class, 'id')
            ->select('id', 'project_id', 'assign_user', 'status', 'type', 'created_by')
            ->with([
                'UserDate',
                'UserDateF'
            ]);
    }

    public function assignedPositions()
    {
        return $this->belongsTo(ProjectAssignDetails::class, 'project_id')
            ->select('id', 'project_id', 'assign_user', 'status', 'type', 'created_by')
            ->with([
                'UserDate',
                'UserDateF'
            ]);
    }

    public function people()
    {
        return $this->belongsTo(People::class, 'project_id')
            ->select('id', 'employee_name');
    }

    public function UserDateF()
    {
        return $this->hasOne(ProjectAssignDetails::class, 'id', 'assign_id');
        
    }

    public function statusData()
    {
        return $this->belongsTo(EntryProcessModel::class, 'entry_process_id')
        ->select('id','entry_date','title','project_id');
        
    }

}
