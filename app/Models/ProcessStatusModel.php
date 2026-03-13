<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProcessStatusModel extends Model
{
    use HasFactory;
    protected $table = 'processes';
    protected $fillable=[
        'entry_process_id',
        'process_title',
        'process_status',
        'process_status_date',
        'process_commands',
        'status',
        'is_deleted'
    ];

    public function entryProcess()
    {
        return $this->belongsTo(EntryProcessModel::class, 'process_title', 'id')
        ->select('id','entry_date','title','project_id','type_of_work','email','institute','department','profession','budget','process_status','journal','journal_status_date','journal_assigned_date','journal_status','hierarchy_level','writer','writer_status','writer_assigned_date','writer_status_date','reviewer','reviewer_assigned_date','reviewer_status','reviewer_status_date','statistican','statistican_assigned_date','statistican_status','statistican_status_date','created_by','project_status','assign_by','assign_date','projectduration');
    }
    
    public function entryProcesses(){
        return $this->belongsTo(EntryProcessModel::class)
        ->select('id','entry_date','title','project_id','type_of_work','email','institute','department','profession','budget','process_status','journal','journal_status_date','journal_assigned_date','journal_status','hierarchy_level','writer','writer_status','writer_assigned_date','writer_status_date','reviewer','reviewer_assigned_date','reviewer_status','reviewer_status_date','statistican','statistican_assigned_date','statistican_status','statistican_status_date','created_by','project_status','assign_by','assign_date','projectduration');
    }

   
    public function pendingProcess(){
        return $this->hasOne(PendingStatusModel::class);
    }

}
