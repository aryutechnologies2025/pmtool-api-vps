<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class EntryProcessModel extends Model
{
    use HasFactory;
    protected $table = 'entry_processes';


    public function institute()
    {
        return $this->belongsTo(InstitutionModel::class, 'institute', 'id')->select('id', 'name', 'is_deleted');
    }

    public function department()
    {
        return $this->belongsTo(DepartmentModel::class, 'department', 'id')->select('id', 'name', 'is_deleted');
    }

    public function profession()
    {
        return $this->belongsTo(ProfessionModel::class, 'profession', 'id')->select('id', 'name', 'is_deleted');
    }

    public function instituteInfo()
    {
        return $this->belongsTo(InstitutionModel::class, 'institute', 'id')->select('id', 'name', 'is_deleted');
    }

    public function departmentInfo()
    {
        return $this->belongsTo(DepartmentModel::class, 'department', 'id')->select('id', 'name', 'is_deleted');
    }

    public function professionInfo()
    {
        return $this->belongsTo(ProfessionModel::class, 'profession', 'id')->select('id', 'name', 'is_deleted');
    }



    public static function generateCustomId(string $typeOfWork): string
    {
        $lastEntry = self::where('type_of_work', $typeOfWork)
            ->orderBy('id', 'desc')
            ->first();

        $increment = $lastEntry
            ? (int)substr($lastEntry->project_id, strlen($typeOfWork) + 1) + 1
            : 1;

        $formattedIncrement = str_pad($increment, 3, '0', STR_PAD_LEFT);

        return $typeOfWork . '-' . $formattedIncrement;
    }

    public static function generateInvoiceNumber()
    {
        // Get the current year and month
        $year = Carbon::now()->format('y'); // last two digits of the year
        $month = Carbon::now()->format('m'); // current month

        // Get the last invoice created in the current month and year
        $lastInvoice = self::where('invoice_number', 'like', '#MR-' . $year . $month . '%')
            ->orderBy('id', 'desc')
            ->first();

        // Set the starting increment to 1 if no invoice exists
        $increment = $lastInvoice ? (intval(substr($lastInvoice->invoice_number, -2)) + 1) : 1;

        // Format increment to always have two digits
        $formattedIncrement = str_pad($increment, 2, '0', STR_PAD_LEFT);

        // Generate the invoice number

        return '#MR-' . $year . $month . $formattedIncrement;
    }



    public function documents()
    {
        return $this->hasMany(EntryDocument::class, 'entry_process_model_id')
            ->where('is_deleted', 0)
            ->select('id', 'entry_process_model_id', 'select_document', 'created_by', 'is_deleted', 'created_at')
            ->with('createdByUser', 'file');
    }

    public function documentsA()
    {
        return $this->hasMany(Activity::class, 'project_id')
            ->select('id', 'project_id', 'created_by', 'created_at', 'activity', 'created_at')
            ->with('createdByUser', 'file');
    }

    public function pendingStatusModel()
    {
        return $this->hasOne(PendingStatusModel::class, 'project_id', 'project_id');
    }

    public function paymentProcess()
    {
        return $this->hasOne(PaymentStatusModel::class, 'project_id', 'id')
            ->with(['paymentData', 'paymentLog']);
    }




    public function rejectReason()
    {
        return $this->hasMany(RejectReason::class, 'project_id')
            ->select('id', 'project_id', 'content', 'created_by', 'created_date', 'createdby_name')
            ->with('createdByUser')
            ->orderBy('id', 'desc');
    }


    public function activityData()
    {
        return $this->hasMany(Activity::class, 'project_id')
            ->with('createdByUser')
            ->orderBy('id', 'desc');
    }

    public function userData()
    {
        return $this->hasOne(User::class, 'id', 'assign_by')
            ->select('id', 'employee_name', 'employee_type', 'position', 'department', 'date_of_joining', 'phone_number', 'email_address', 'profile_image')
            ->with('createdByUser');
    }

    public function userData1()
    {
        return $this->hasOne(User::class, 'id', 'created_by')
            ->select('id', 'employee_name', 'employee_type', 'position', 'department', 'date_of_joining', 'phone_number', 'email_address', 'profile_image')
            ->with('createdByUser');
    }

    public function writerData()
    {
        return $this->hasMany(ProjectAssignDetails::class, 'project_id', 'id')
            ->with(['UserDate'])
            ->where('type', 'writer')
            // ->orderBy('created_at', 'desc')
            ->select('project_id', 'assign_user', 'assign_date', 'status', 'status_date', 'comments', 'type', 'created_by', 'id', 'project_duration','updated_at','created_at');
    }

    public function reviewerData()
    {
        return $this->hasMany(ProjectAssignDetails::class, 'project_id', 'id')
            ->with(['UserDate'])
            ->where('type', 'reviewer')
            // ->orderBy('created_at', 'desc')
            ->select('project_id', 'assign_user', 'assign_date', 'status', 'status_date', 'comments', 'type', 'created_by', 'id', 'project_duration','updated_at','created_at');
    }
    public function employee_rejected()
    {
        return $this->hasMany(ProjectLogs::class, 'project_id', 'project_id');
                  
    }


    public function statisticanData()
    {
        return $this->hasMany(ProjectAssignDetails::class, 'project_id', 'id')
            ->with(['UserDate'])
            ->where('type', 'statistican')
            // ->orderBy('created_at', 'desc')
            ->select('project_id', 'assign_user', 'assign_date', 'status', 'status_date', 'comments', 'type', 'created_by', 'id', 'project_duration','updated_at','created_at');
    }
    public function journalData()
    {
        return $this->hasMany(ProjectAssignDetails::class, 'project_id', 'id')
            ->with(['UserDate'])
            ->where('type', 'publication_manager')
            ->orderBy('created_at', 'desc')
            ->select('project_id', 'assign_user', 'assign_date', 'status', 'status_date', 'comments', 'type', 'created_by', 'id', 'project_duration', 'type_of_article', 'review');
    }

    public function tcData()
    {
        return $this->hasMany(ProjectAssignDetails::class, 'project_id', 'id')
            ->with(['UserDate'])
            // ->where('status', 'correction')
            ->where('type', 'team_coordinator')
            ->select('project_id', 'assign_user', 'assign_date', 'type_sme', 'status', 'status_date', 'comments', 'type', 'created_by', 'id', 'project_duration', 'type_of_article', 'review', 'is_deleted');
    }

    public function smeData()
    {
        return $this->hasMany(ProjectAssignDetails::class, 'project_id', 'id')
            ->with(['UserDate'])
            ->where('type', 'sme')
            ->select('project_id', 'assign_user', 'assign_date', 'status', 'status_date', 'comments', 'type', 'created_by', 'id', 'project_duration', 'type_of_article', 'review');
    }



    public function statusData()
    {
        return $this->hasMany(ProjectAssignDetails::class, 'project_id', 'id')
            ->whereIn('type', ['writer', 'reviewer', 'statistican', 'publication_manager'])
            ->select('project_id', 'type', 'assign_user', 'status', 'status_date');
    }

    public function statusDatas()
    {
        return $this->hasMany(ProjectAssignDetails::class, 'project_id', 'id')
            ->select('project_id', 'type', 'status', 'assign_user');
    }




    public function projectStatus()
    {
        return $this->HasMany(ProjectStatus::class, 'project_id', 'id')
            ->with(['userData'])->select('project_id', 'assign_id', 'status');
    }

    public function projectcomment()
    {
        return $this->HasMany(Commends::class, 'project_id', 'id')
            ->with(['createdByUser', 'assignByUser']);
    }



    public function projectCommentR()
    {
        return $this->hasMany(ProjectAssignDetails::class, 'project_id', 'id')
            ->with(['UserDate'])
            ->where('type', '!=', 'team_coordinator')
            // ->where('status', '!=', 'correction')
            ->select(
                'project_id',
                'assign_user',
                'assign_date',
                'status',
                'status_date',
                'comments',
                'type',
                'created_by',
                'id',
                'project_duration'
            );
    }


    public function projectMailStatus()
    {
        return $this->HasMany(MailNotification::class, 'project_id', 'id');
    }

    public function projectAcceptStatust()
    {
        return $this->HasMany(ProjectStatus::class, 'project_id', 'id')
            ->with(['userData'])->select('project_id', 'assign_id', 'status');
    }

    public function author()
    {
        return $this->hasMany(Author::class, 'project_id', 'id');
    }

    public function submission()
    {
        return $this->hasMany(AuthorSubmissionForm::class, 'project_id', 'id');
    }

    public function reviewerComments()
    {
        return $this->hasMany(ReviewerComments::class, 'project_id', 'id');
    }

    public function rejectedForm()
    {
        return $this->hasMany(RejectedForm::class, 'project_id', 'id');
    }

    public function resubmittedForm()
    {
        return $this->hasMany(ResubmittedForm::class, 'project_id', 'id');
    }

    public function documentsForm()
    {
        return $this->hasMany(DocumentsForm::class, 'project_id', 'id');
    }
    public function employeePaymentDetails()
    {
        return $this->hasMany(EmployeePaymentDetails::class, 'project_id', 'id')
            ->select('id','project_id', 'employee_id', 'payment', 'payment_date', 'status', 'type', 'created_date');
    }
    public function employeePaymentDetail()
    {
        return $this->hasMany(EmployeePaymentDetails::class, 'project_id', 'id')
            ->select('id','project_id', 'employee_id', 'payment', 'payment_date', 'status', 'type', 'created_date')
            ->where('type', '!=', 'publication_manager')
            ->with('UserDate');
    }

    public function journalPaymentDetails()
    {
        return $this->hasMany(EmployeePaymentDetails::class, 'project_id', 'id')
            ->select('id','project_id', 'employee_id', 'payment', 'payment_date', 'status', 'type', 'created_date')
            ->where('type', 'publication_manager')
            ->with('UserDate');
    }



    public function ProjectViewStatus()
    {
        return $this->hasMany(ProjectViewStatus::class, 'project_id', 'id')
            ->select('project_id', 'view_status', 'created_by', 'created_date')
            ->with('userData');
    }

    public function CommentList()
    {
        return $this->hasMany(CommentsList::class, 'project_id', 'id')
            ->select('project_id', 'comment_id', 'commend_type', 'created_by', 'is_read', 'created_date')
            ->with('userData');
    }
}
