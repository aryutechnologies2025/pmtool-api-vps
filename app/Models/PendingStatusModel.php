<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PendingStatusModel extends Model
{
    use HasFactory;
    protected $table = 'pending_processes';
    protected $fillable=[
        
        'project_id',
        'writer_pending_days',
        'reviewer_pending_days',
        'project_pending_days',
        'writer_payment_due_date',
        'reviewer_payment_due_date',
        'status',
        'is_deleted'
    ];

    public function process(){
        return $this->belongsTo(ProcessStatusModel::class, 'process_id', 'id');
    }


    public function entryProcess()
    {
        return $this->belongsTo(EntryProcessModel::class, 'project_id', 'project_id')
        ->select('id','entry_date','title','project_id','type_of_work','email','institute','department','profession','budget','process_status','hierarchy_level','created_by','project_status','assign_by','assign_date','projectduration');
    }
}


