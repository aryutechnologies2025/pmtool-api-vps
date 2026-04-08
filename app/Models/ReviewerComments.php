<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReviewerComments extends Model
{
    use HasFactory;
    protected $table = 'reviewer_comments';
    protected $fillable = [
        'project_id',
        'record_date',
        'journal_name',
        'comments',
        'created_by',
        'file'
    ];
    
    public function reviewerEntryProcess(){
        return $this->belongsTo(EntryProcessModel::class, 'project_id', 'id');
    }
}