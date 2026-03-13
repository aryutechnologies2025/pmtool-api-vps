<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuthorSubmissionForm extends Model
{
    use HasFactory;
    protected $table ='submission_author_forms';
    protected $fillable = [
        'project_id',
        'journal_name',
        'type_of_article',
        'article_id',
        'review',
        'date_of_submission',
        'journal_fee',
        'created_by'
    ];
    
    public function projectSubmission(){
        return $this->belongsTo(EntryProcessModel::class, 'project_id', 'id');
    }
}