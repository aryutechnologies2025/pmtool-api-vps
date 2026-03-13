<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RejectedForm extends Model
{
    use HasFactory;
    protected $table = 'rejected_form';
    protected $fillable = [
        'project_id','journal_name','article_id', 'date_of_rejected','comments','created_by'
    ];
    
    public function EntryProcess(){
        return $this->belongsTo(EntryProcessModel::class, 'project_id', 'id');
    }
}