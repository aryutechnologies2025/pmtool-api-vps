<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommentsList extends Model
{
    use HasFactory;

    protected $table='comments_list';

    protected $fillable = [
        'project_id',
        'comment_id',
        'commend_type',
        'created_by',
        'is_read',
        'created_date',
    ];


    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by', 'id')
        ->with(['createdByUser'])
        ->select('id','employee_name','employee_type','position','department','date_of_joining','phone_number','email_address','profile_image')
        ->where('status','1');
    }
}
