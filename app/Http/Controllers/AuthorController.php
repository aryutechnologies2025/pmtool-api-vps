<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Author;
use App\Models\AuthorSubmissionForm;
use App\Models\DocumentFile;
use App\Models\DocumentsForm;
use App\Models\EntryDocument;
use App\Models\EntryProcessModel;
use App\Models\PaymentStatusModel;
use App\Models\People;
use App\Models\ProjectActivity;
use App\Models\ProjectAssignDetails;
use App\Models\ProjectLogs;
use App\Models\EmployeePaymentDetails;
use App\Models\RejectedForm;
use App\Models\ResubmittedForm;
use App\Models\ReviewerComments;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
class AuthorController extends Controller
{
    public function store(Request $request)
    {

        $entry = EntryProcessModel::select('id', 'project_id')->where('id', $request->project_id)->first();
        if (! $entry) {
            return response()->json(['message' => 'Project not found'], 404);
        }

        $author = new Author;
        $author->project_id = $request->project_id;
        $author->initial = $request->initial ?? null;
        $author->first_name = $request->first_name ?? null;
        $author->last_name = $request->last_name ?? null;
        $author->profession_id = $request->profession_id;
        $author->department_id = $request->department_id;
        $author->institute_id = $request->institute_id;

        $author->state = $request->state ?? null;
        $author->country = $request->country ?? null;
        $author->email = $request->email ?? null;
        $author->phone = $request->phone ?? null;
        $author->created_by = $request->created_by ?? 0;
        $author->save();

        return response()->json($author);
    }

    public function edit(Request $request)
    {

        $entry = EntryProcessModel::select('id', 'project_id')->where('id', $request->project_id)->first();
        if (! $entry) {
            return response()->json(['message' => 'Project not found'], 404);
        }

        $author = Author::where('id', $request->id)->first();
        $author->project_id = $request->project_id;
        $author->initial = $request->initial ?? null;
        $author->first_name = $request->first_name ?? null;
        $author->last_name = $request->last_name ?? null;
        $author->profession_id = $request->profession_id;
        $author->department_id = $request->department_id;
        $author->institute_id = $request->institute_id;

        $author->state = $request->state ?? null;
        $author->country = $request->country ?? null;
        $author->email = $request->email ?? null;
        $author->phone = $request->phone ?? null;
        $author->created_by = $request->created_by ?? 0;
        $author->save();

        return response()->json([$author, 'message' => 'Author form updated successfully'], 200);
    }

    public function delete(Request $request)
    {

        $entry = EntryProcessModel::select('id', 'project_id')->where('id', $request->project_id)->first();
        if (! $entry) {
            return response()->json(['message' => 'Project not found'], 404);
        }

        $author = Author::where('id', $request->id)->first();

        $author->delete();

        return response()->json([$author, 'message' => 'Author form deleted successfully'], 200);
    }

    // public function store(Request $request)
    // {
    //     $author = Author::create($request->all());

    //     return response()->json(['message' => 'Author added successfully', 'data' => $author], 201);
    // }

    public function author_list(Request $request)
    {
        $project_id = $request->query('project_id');

        if (! $project_id) {
            return response()->json([
                'message' => 'Please provide a project_id parameter.',
            ], 400); // 400 Bad Request
        }

        $authors = Author::with(['institute', 'department', 'profession'])
            ->where('project_id', $project_id)
            ->get();

        return response()->json($authors);
    }

    //submission Form

    // public function submission_store(Request $request)
    // {
    //     $entry = EntryProcessModel::select('id', 'project_id')->where('id', $request->project_id)->first();
    //     if (! $entry) {
    //         return response()->json(['message' => 'Project not found'], 404);
    //     }

    //     $type = $request->type_of_article;
    //     $submission = new AuthorSubmissionForm;
    //     $submission->project_id = $request->project_id;
    //     $submission->journal_name = $request->journal_name;
    //     $submission->type_of_article = $type;
    //     $submission->article_id = $request->article_id;
    //     $submission->review = $request->review;
    //     $submission->date_of_submission = $request->date_of_submission;
    //     $submission->journal_fee = $request->journal_fee;
    //     $submission->created_by = $request->created_by;
    //     $submission->save();

    //     $jounalPaymentDetails = EmployeePaymentDetails::where('project_id', $request->project_id)
    //         ->where('type','publication_manager')->first();
    //     if($jounalPaymentDetails){
    //         $jounalPaymentDetails->employee_id = $request->journal_name;
    //         $jounalPaymentDetails->save();
    //     }

    //     $details = ProjectAssignDetails::where('project_id', $request->project_id)
    //         ->where('type', 'publication_manager')
    //         ->first();
    //     if ($details) {
    //         $details->assign_user = $request->journal_name;
    //         $details->review = $request->review;
    //         $details->save();
    //     }

    //     $projectLogDetails = ProjectLogs::where('project_id', $request->project_id)
    //     ->where('type','publication_manager')->first();
    //     if($projectLogDetails){
    //         $projectLogDetails->employee_id = $request->journal_name;
    //         $projectLogDetails->save();
    //     }

    //     return response()->json([$submission, 'message' => 'Submission created successfully'], 200);
    // }

    

    public function submission_store(Request $request)
    {
        $request->validate([
            'project_id' => 'required|integer',
            'journal_name' => 'required|string',
            // 'type_of_article' => 'required|string',
            // 'article_id' => 'required|string',
            // 'review' => 'nullable|string',
            // 'date_of_submission' => 'required|date',
            // 'journal_fee' => 'nullable|numeric',
            // 'created_by' => 'required|integer',
        ]);

        $entry = EntryProcessModel::find($request->project_id);
        if (! $entry) {
            return response()->json(['message' => 'Project not found'], 404);
        }

        DB::transaction(function () use ($request) {

            $submission = new AuthorSubmissionForm;
            $submission->project_id = $request->project_id;
            $submission->journal_name = $request->journal_name;
            $submission->type_of_article = $request->type_of_article;
            $submission->article_id = $request->article_id;
            $submission->review = $request->review;
            $submission->date_of_submission = $request->date_of_submission;
            $submission->journal_fee = $request->journal_fee;
            $submission->created_by = $request->created_by;
            $submission->save();

            $journalPaymentDetails = EmployeePaymentDetails::where('project_id', $request->project_id)
                ->where('type', 'publication_manager')
                ->first();

            if ($journalPaymentDetails) {
                $journalPaymentDetails->employee_id = $request->journal_name;
                $journalPaymentDetails->save();
            }

            $assignDetails = ProjectAssignDetails::where('project_id', $request->project_id)
                ->where('type', 'publication_manager')
                ->first();

            if ($assignDetails) {
                $assignDetails->assign_user = $request->journal_name;
                $assignDetails->review = $request->review;
                $assignDetails->type_of_article = $request->type_of_article;
                $assignDetails->save();
            }

            $projectLogDetails = ProjectLogs::where('project_id', $request->project_id)
                ->where('status_type', 'publication_manager')
                ->first();

            if ($projectLogDetails) {
                $projectLogDetails->employee_id = $request->journal_name;
                $projectLogDetails->save();
            }
        });

        return response()->json([
            'message' => 'Submission created successfully',
        ], 201);
    }

    public function submission_list(Request $request)
    {
        $project_id = $request->query('project_id');
        $submission = AuthorSubmissionForm::orderBy('created_at', 'desc');
        if ($project_id) {
            $submission->where('project_id', $project_id);
        }
        $submission = $submission->get();

        return response()->json($submission);
    }

    //reviewerComments

    public function reviewerComments_store(Request $request)
    {
        $entry = EntryProcessModel::select('id', 'project_id')->where('id', $request->project_id)->first();
        if (! $entry) {
            return response()->json(['message' => 'Project not found'], 404);
        }

        Log::info('Reviewer Comments Store Request:', ['project_id' => $request->project_id]);

        $comments = [];

        if (is_array($request->comments)) {
            $comments = $request->comments;
        } elseif (is_string($request->comments)) {
            $comments = array_map('trim', explode(',', $request->comments));
        }

        
        $comments = array_filter($comments);

      
        $commentsText = implode(', ', $comments);

     
        $filePath = null;
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filename = $file->getClientOriginalName();
            $filePath = 'reviewer_files/'.$filename;
            $file->move(public_path('reviewer_files'), $filename);
        }

    
        $reviewerComments = new ReviewerComments;
        $reviewerComments->project_id = $request->project_id;
        $reviewerComments->record_date = $request->record_date;
        $reviewerComments->comments = $commentsText;
        $reviewerComments->journal_name = $request->journal_name;
        $reviewerComments->created_by = $request->created_by;
        $reviewerComments->file = $filePath;

        $reviewerComments->save();

        return response()->json([
            'reviewerComments' => $reviewerComments,
            'message' => 'Reviewer Comments created successfully',
        ], 200);
    }

    public function reviewer_list(Request $request)
    {
        $project_id = $request->query('project_id');

        $reviewer_comments = ReviewerComments::orderBy('created_at', 'desc');

        if ($project_id) {
            $reviewer_comments->where('project_id', $project_id);
        }

        $reviewer_comments = $reviewer_comments->get();

        foreach ($reviewer_comments as $comment) {
            $comment->comments = json_decode($comment->comments);
        }

        return response()->json($reviewer_comments);
    }

    //rejectedForm

    public function rejectedForm_store(Request $request)
    {
        $entry = EntryProcessModel::select('id', 'project_id')->where('id', $request->project_id)->first();
        if (! $entry) {
            return response()->json(['message' => 'Project not found'], 404);
        }
        $rejected = new RejectedForm;
        $rejected->project_id = $request->project_id;
        $rejected->journal_name = $request->journal_name;
        $rejected->article_id = $request->article_id;
        $rejected->date_of_rejected = $request->date_of_rejected;
        $rejected->comments = $request->comments;
        $rejected->created_by = $request->created_by;
        $rejected->save();

        return response()->json($rejected);
    }

    public function rejected_list(Request $request)
    {
        $project_id = $request->query('project_id');

        if (! $project_id) {
            return response()->json([], 200);
        }

        $rejected = RejectedForm::orderBy('created_at', 'desc');

        $rejected->where('project_id', $project_id);

        $rejected_list = $rejected->get();

        return response()->json($rejected_list);
    }

    //resubmissionForm

    public function resubmitted_store(Request $request)
    {
        $entry = EntryProcessModel::select('id', 'project_id')->where('id', $request->project_id)->first();
        if (! $entry) {
            return response()->json(['message' => 'Project not found'], 404);
        }
        $resubmitted = new ResubmittedForm;
        $resubmitted->project_id = $request->project_id;
        $resubmitted->journal_name = $request->journal_name;
        $resubmitted->review = $request->review;
        $resubmitted->article_id = $request->article_id;
        $resubmitted->date_of_rejected = $request->date_of_rejected;
        $resubmitted->date_of_submission = $request->date_of_submission;
        $resubmitted->created_by = $request->created_by;
        $resubmitted->save();

        return response()->json($resubmitted);
    }

    public function resubmitted_list(Request $request)
    {
        $project_id = $request->query('project_id');
        $resubmitted = ResubmittedForm::orderBy('created_at', 'desc');

        if ($project_id) {
            $resubmitted->where('project_id', $project_id);
        }
        $resubmitted_list = $resubmitted->get();

        return response()->json($resubmitted_list);
    }

    //documentsForm

    // public function documentForm(Request $request)
    // {
    //     // $request->validate([
    //     //     'project_id' => 'required|integer',
    //     //     'created_by' => 'required|integer',
    //     //     'title' => 'required|array',
    //     //     'title.*' => 'required|string',
    //     //     'file' => 'required|array',
    //     //     'file.*' => 'required|file',
    //     // ]);
    //     $entry = EntryProcessModel::select('id','project_id')->where('id',$request->project_id)->first();
    //     if(!$entry){
    //         return response()->json(['message' => 'Project not found'], 404);
    //     }

    //     $documents = [];

    //     foreach ($request->file('file') as $index => $file) {
    //         $filename = time() . '_' . $file->getClientOriginalName();
    //         $filePath = 'author_document_files/' . $filename;

    //         // Store file in the public path
    //         $file->move(public_path('author_document_files'), $filename);

    //         $documents[] = [
    //             'project_id' => $request->project_id,
    //             'title' => $request->title[$index], // Get the corresponding title
    //             'file' => $filePath, // Save public path for access
    //             'created_by' => $request->created_by,
    //             'created_at' => now(),
    //             'updated_at' => now(),
    //         ];
    //     }

    //     DocumentsForm::insert($documents);

    //     return response()->json(['message' => 'Files uploaded successfully!']);
    // }
    // public function document_list(Request $request){
    //     $project_id = $request->query('project_id');
    //     $document = DocumentsForm::orderBy('created_at', 'desc');
    //     if($project_id){
    //         $document->where('project_id', $project_id);
    //     }
    //     $document_list = $document->get();
    //     return response()->json($document_list);
    // }

    public function documentForm(Request $request)
    {
        $request->validate([
            'project_id' => 'required|integer',
            'created_by' => 'required|integer',
            'title' => 'required',
            'other_title' => 'nullable|string',
            'file' => 'required|array',
            'file.*' => 'required|file',
        ]);

        $entry = EntryProcessModel::where('id', $request->project_id)->first();
        if (! $entry) {
            return response()->json(['message' => 'Project not found'], 404);
        }

        if (! $request->hasFile('file')) {
            return response()->json(['message' => 'No files uploaded'], 400);
        }

        // $filePaths = [];
        // foreach ($request->file('file') as $file) {
        //     $filename = time() . '_' . $file->getClientOriginalName();
        //     $filePath = 'author_document_files/' . $filename;
        //     $file->move(public_path('author_document_files'), $filename);
        //     $filePaths[] = $filePath;
        // }

        // If "Others" is selected, use the custom title input
        $finalTitle = $request->title === 'Others' ? $request->other_title : $request->title;

        // Save as JSON
        $document = DocumentsForm::create([
            'project_id' => $request->project_id,
            'title' => $finalTitle,
            // 'file' => json_encode($filePaths), // Store as JSON array
            'created_by' => $request->created_by,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($request->file('file') as $file) {
            $filename = time().'_'.$file->getClientOriginalName();
            $filePath = 'author_document_files/'.$filename;
            $file->move(public_path('author_document_files'), $filename);

            DocumentFile::create([
                'document_id' => $document->id,
                'file_path' => $filePath,
            ]);
        }

        return response()->json(['message' => 'Files uploaded successfully!']);
    }

    public function document_list(Request $request)
    {
        $project_id = $request->query('project_id');
        $documents = DocumentsForm::orderBy('created_at', 'desc')
            ->where('is_deleted', 0)
            ->with('files');

        if ($project_id) {
            $documents->where('project_id', $project_id);
        }

        $document_list = $documents->get()->map(function ($doc) {
            return [
                'id' => $doc->id,
                'project_id' => $doc->project_id,
                'title' => $doc->title,
                'files' => $doc->files->map(function ($file) {
                    return [
                        'id' => $file->id,
                        'file_path' => $file->file_path,
                    ];
                }),
                'created_by' => $doc->created_by,
                'created_at' => $doc->created_at,
                'updated_at' => $doc->updated_at,
            ];
        });

        return response()->json($document_list);
    }

    public function document_delete(Request $request)
    {
        $document = DocumentsForm::find($request->id);
        if ($document) {
            $document->is_deleted = 1;
            $document->save();

            return response()->json(['message' => 'Document marked as deleted successfully']);
        }

        return response()->json(['message' => 'Document not found'], 404);
    }

    public function author_view(Request $request, $id)
    {
        $author = EntryProcessModel::with(['journalData', 'submission', 'projectcomment', 'rejectedForm', 'department:id,name', 'profession:id,name', 'institute:id,name'])
            ->where('is_deleted', 0)
            ->where('project_id', $id)
            ->select('id', 'project_id', 'client_name', 'entry_date', 'title', 'type_of_work', 'process_status', 'hierarchy_level', 'projectduration', 'created_by', 'department', 'institute', 'profession')
            ->first();

        $files = $author->documents->flatMap(function ($doc) {
            return collect($doc->file)->pluck('file');
        });

        $document = EntryDocument::where('entry_process_model_id', $author->id)
            ->pluck('select_document')
            ->map(function ($doc) {
                return json_decode($doc, true);
            })
            ->flatten()
            ->all();

        $peopleIds_sme = People::where('position', '27')
            ->pluck('id')
            ->filter()
            ->values()
            ->toArray();

        $projectLogs = ProjectLogs::where('project_id', $author->id)
            ->orderBy('created_at', 'desc')
            // ->whereIn('created_by', $peopleIds_sme)
            ->pluck('status')
            ->first();

        $formatted = array_map(function ($item) {
            return ['value' => $item];
        }, $document);

        $lastCompletedActivityModel = Activity::with(['file'])
            ->where('project_id', $author->id)
            // ->where('activity', 'completed')
            ->orderBy('created_at', 'desc')
            ->first();

        $lastCompletedActivityModels = $lastCompletedActivityModel && $lastCompletedActivityModel->file
            ? $lastCompletedActivityModel->file->map(function ($doc) {
                return [

                    'files' => $doc->files,
                ];
            })
            : [];

        return response()->json([
            'author' => $author,
            'documents' => $formatted,
            'projectLogs' => $projectLogs,
            'file' => $files,
            'lastCompletedActivity' => $lastCompletedActivityModels,
        ]);
    }

    public function get_publication_list(Request $request)
    {
        $type = $request->input('type');
        $position = $request->input('position');
        $entries = EntryProcessModel::select('id', 'type_of_work', 'project_id', 'process_status', 'hierarchy_level', 'projectduration', 'created_by')
            ->where('is_deleted', 0)
            ->where('process_status', '!=', 'completed')
            ->get();
        $totalProjectsQuery = $entries
            ->pluck('id')->toArray();

        $peopleIds_pm = People::where('position', $position)
            ->pluck('id')
            ->filter()
            ->values()
            ->toArray();

        $projectcompletedQuery = ProjectAssignDetails::with([

            'projectData' => function ($query) {
                $query->with([
                    'writerData:id,status,project_id',
                    'reviewerData:id,status,project_id',
                    'statisticanData:id,status,project_id',
                    'journalData:id,status,project_id,assign_user,type_of_article,review',
                    'journalPaymentDetails:id,project_id,employee_id,status',
                    'paymentProcess',

                ]);
                //->select('id', 'project_id', 'title', 'process_status', 'hierarchy_level', 'type_of_work', 'client_name', 'institute', 'department', 'profession', 'email', 'entry_date', 'projectduration');
            },
        ])

            ->whereIn('project_id', $totalProjectsQuery)
            // ->whereIn('created_by', $peopleIds_pm)
            ->where('type', 'publication_manager');

        $projectcompleted = $projectcompletedQuery->get()->unique('projectData.id');

        $completed_list = EntryProcessModel::with('paymentProcess')
            ->where('process_status', 'completed')
            ->where('is_deleted', 0)
            ->select(
                'id',
                'type_of_work',
                'project_id',
                'title',
                'process_status',
                DB::raw("CONCAT(DATEDIFF(projectduration, created_at), ' days ', MOD(TIMESTAMPDIFF(HOUR, created_at, projectduration), 24), ' hrs') AS projectduration"),
                'hierarchy_level',
                'client_name',
                'entry_date',
                'created_at'
            )
            ->get()
            ->unique('project_id')
            ->map(function ($item) {
                return [
                    'project_id' => $item->project_id ?? null,
                    'entry_date' => $item->entry_date ?? null,
                    'client_name' => $item->client_name ?? null,
                    'title' => $item->title ?? null,
                    'projectduration' => $item->projectduration ?? null,
                    'process_status' => $item->process_status ?? null,
                    'payment_status' => $item->paymentProcess->payment_status ?? null,
                ];
            });

        $urgentImportant_list = ProjectAssignDetails::with(['projectData' => function ($query) {
            $query->select(
                'id',
                'type_of_work',
                'project_id',
                'entry_date',
                'client_name',
                'title',
                'process_status',
                'hierarchy_level',
                DB::raw("CONCAT(DATEDIFF(projectduration, created_at), ' days ', MOD(TIMESTAMPDIFF(HOUR, created_at, projectduration), 24), ' hrs') AS projectduration"),
            );
        }])
            ->whereIn('project_id', $entries->pluck('id')->toArray())
            ->whereHas('projectData', function ($query) {
                $query->where('hierarchy_level', 'urgent_important');
            })
            ->where('type', 'publication_manager')
            ->get()
            ->unique('project_id')
            ->map(function ($item) {
                return (object) [
                    'project_id' => $item->projectData->project_id ?? null,
                    'entry_date' => $item->projectData->entry_date ?? null,
                    'client_name' => $item->projectData->client_name ?? null,
                    'title' => $item->projectData->title ?? null,
                    'projectduration' => $item->projectData->projectduration ?? null,
                    'process_status' => $item->projectData->process_status ?? null,
                    'payment_status' => $item->projectData->paymentProcess->payment_status ?? null,
                ];
            })
            ->values()
            ->toArray();

        $statuses = [
            'advance_pending',
            'partial_payment_pending',
            'final_payment_pending',
            'journal_payment_pending',
        ];

        $paymentEntries = [];

        foreach ($statuses as $status) {
            $paymentEntries[$status] = PaymentStatusModel::with(['projectData' => function ($query) {
                $query->select('id', 'type_of_work', 'project_id', 'title', 'process_status', 'created_at', 'projectduration', 'institute', 'department', 'profession');
            }])
                ->where('payment_status', $status)
                ->get();
        }

        $client_review = EntryProcessModel::where('process_status', 'client_review')
            ->where('is_deleted', 0)
            ->select(
                'id',
                'type_of_work',
                'project_id',
                'title',
                'process_status',
                DB::raw("CONCAT(DATEDIFF(projectduration, created_at), ' days ', MOD(TIMESTAMPDIFF(HOUR, created_at, projectduration), 24), ' hrs') AS projectduration"),
                'hierarchy_level',
                'client_name',
                'entry_date',
                'created_at'
            )
            ->get()
            ->unique('project_id')
            ->map(function ($item) {
                return [
                    'project_id' => $item->project_id ?? null,
                    'entry_date' => $item->entry_date ?? null,
                    'client_name' => $item->client_name ?? null,
                    'title' => $item->title ?? null,
                    'projectduration' => $item->projectduration ?? null,
                    'process_status' => $item->process_status ?? null,
                    'payment_status' => $item->paymentProcess->payment_status ?? null,
                ];
            });

        $data = [
            'submit_to_journal' => [],
            'pending_author' => [],
            'rejected' => [],
            'withdrawal' => [],
            'resubmission' => [],
            'reviewer_comments' => [],
            'peer_review' => [],
            'submitted' => [],
            'published' => [],
            'urgentImportant_list' => $urgentImportant_list,
            'quickReview_list' => [],
            'completed_list' => $completed_list,
            'client_review' => $client_review,
            'payment_status' => $paymentEntries,
        ];

        foreach ($projectcompleted as $projectAssignDetail) {
            $projectData = $projectAssignDetail->projectData;

            if (! $projectData) {
                continue;
            }

            $createdAt = Carbon::parse($projectData->created_at);
            // $projectDurationDate = Carbon::parse($projectData->projectduration);

            // // Get total difference in hours (absolute)
            // $diffInHours = abs($createdAt->diffInHours($projectDurationDate));
            // $diffInDays = floor($diffInHours / 24);
            // $diffInRemHours = $diffInHours % 24;

            // // Format duration like "1 days 3 hrs"
            // $durationText = "$diffInDays days $diffInRemHours hrs";

            $journalData = optional($projectData)->journalData;
            $paymentStatus = optional($projectData)->paymentProcess;

            $writerStatus = optional($projectData)->writerData->pluck('status')->toArray();
            $reviewerStatus = optional($projectData)->reviewerData->pluck('status')->toArray();
            $statisticanStatus = optional($projectData)->statisticanData->pluck('status')->toArray();
            // Removed unused variable $employeeDetails
            $journalStatus = $journalData ? $journalData->map(function ($journal) {
                return [
                    'id' => $journal->id,
                    'project_id' => $journal->project_id,
                    'assign_user' => $journal->assign_user,
                    'status' => $journal->status,
                    'type_of_article' => $journal->type_of_article,
                    'review' => $journal->review,

                ];
            })->toArray() : [];

            $projectDetails = [
                'id' => $projectData->id,
                'project_id' => $projectData->project_id,
                'title' => $projectData->title ?? '-',
                'process_status' => $projectData->process_status ?? '-',
                'hierarchy_level' => $projectData->hierarchy_level ?? '-',
                'type_of_work' => $projectData->type_of_work ?? '-',
                'client_name' => $projectData->client_name ?? '-',
                'institute' => $projectData->instituteInfo ?? '-',
                'department' => $projectData->departmentInfo ?? '-',
                'profession' => $projectData->professionInfo ?? '-',
                'email' => $projectData->email ?? '-',
                'entry_date' => $projectData->entry_date ?? '-',
                // 'projectduration' => $projectData->projectduration ?? '-',
                'employeeDetails' => $projectData->journalPaymentDetails ?? '-',

                'writer_status' => ! empty($writerStatus) ? implode(', ', $writerStatus) : '-',
                'reviewer_status' => ! empty($reviewerStatus) ? implode(', ', $reviewerStatus) : '-',
                'statistican_status' => ! empty($statisticanStatus) ? implode(', ', $statisticanStatus) : '-',
                'journal_status' => $journalStatus,
                'payment_status' => $paymentStatus,

                // 'projectduration'  => $projectData->projectduration,
                'projectduration' => $projectData->projectduration ?? '-',

            ];

            if ($journalData && $journalData->contains('status', 'submit_to_journal')) {
                $data['submit_to_journal'][] = $projectDetails;
            }
            if ($journalData && $journalData->contains('status', 'pending_author')) {
                $data['pending_author'][] = $projectDetails;
            }
            if ($journalData && $journalData->contains('status', 'rejected')) {
                $data['rejected'][] = $projectDetails;
            }
            if ($journalData && $journalData->contains('status', 'withdrawn')) {
                $data['withdrawal'][] = $projectDetails;
            }
            if ($journalData && $journalData->contains('status', 'resubmission')) {
                $data['resubmission'][] = $projectDetails;
            }
            if ($journalData && $journalData->contains('status', 'reviewer_comments')) {
                $data['reviewer_comments'][] = $projectDetails;
            }
            if ($journalData && $journalData->contains('status', 'peer_review')) {
                $data['peer_review'][] = $projectDetails;
            }
            if ($journalData && $journalData->contains('status', 'submitted')) {
                $data['submitted'][] = $projectDetails;
            }
            if ($journalData && $journalData->contains('status', 'published')) {
                $data['published'][] = $projectDetails;
            }
            if ($entries->contains('hierarchy_level', 'urgent_important')) {
                $data['urgentImportant_list'][] = $projectDetails;
            }
            if ($journalData && $journalData->contains('review', 'quickReview')) {
                $data['quickReview_list'][] = $projectDetails;
            }

            if ($journalData && $journalData->contains('status', 'completed')) {
                $data['completed_list'][] = $projectDetails;
            }
            if ($journalData && $journalData->contains('status', 'client_review')) {
                $data['client_review'][] = $projectDetails;
            }
        }

        $quickReview_list = ProjectAssignDetails::with(['projectData' => function ($query) {
            $query->select(
                'id',
                'type_of_work',
                'entry_date',
                'client_name',
                'project_id',
                'title',
                'process_status',
                'created_at',
                DB::raw("CONCAT(DATEDIFF(projectduration, created_at), ' days ', MOD(TIMESTAMPDIFF(HOUR, created_at, projectduration), 24), ' hrs') AS projectduration"),
            );
        }])
            ->whereIn('project_id', $totalProjectsQuery)
            ->where('type', 'publication_manager')
            ->where('review', 'quickReview')
            ->select('id', 'status', 'project_id', 'assign_user', 'assign_date', 'type', 'comments', 'type_of_article', 'review')
            ->get()
            ->unique('project_id')
            ->map(function ($item) {
                return (object) [
                    'project_id' => $item->projectData->project_id ?? null,
                    'entry_date' => $item->projectData->entry_date ?? null,
                    'client_name' => $item->projectData->client_name ?? null,
                    'title' => $item->projectData->title ?? null,
                    'projectduration' => $item->projectData->projectduration ?? null,
                    'process_status' => $item->projectData->process_status ?? null,
                    'payment_status' => $item->projectData->paymentProcess->payment_status ?? null,
                ];
            })
            ->values()
            ->toArray();

        $journalList = ProjectAssignDetails::with('projectData.paymentProcess')
            ->where('type', 'publication_manager')
            ->where('status', 'submitted')
            // ->whereIn('created_by', $peopleIds_sme)
            ->get()
            ->map(function ($item) {
                return [
                    'project_id' => $item->projectData->project_id ?? null,
                    'entry_date' => $item->projectData->entry_date ?? null,
                    'client_name' => $item->projectData->client_name ?? null,
                    'title' => $item->projectData->title ?? null,
                    'process_status' => $item->projectData->process_status ?? null,
                    'payment_status' => $item->projectData->paymentProcess->payment_status ?? null,
                    'projectduration' => $item->projectData->projectduration ?? null,
                ];
            });

        // If a type is provided, return only that specific type
        if ($type && array_key_exists($type, $data)) {
            return response()->json([
                'data' => $data[$type],
                'message' => ucfirst(str_replace('_', ' ', $type)).' projects retrieved successfully',
            ]);
        }

        // Return all data if no specific type is requested
        if ($type === 'urgent_important') {
            return response()->json([
                'data' => $urgentImportant_list,
                'message' => 'Urgent and Important projects retrieved successfully',
            ]);
        }

        if ($type === 'quick_review') {
            return response()->json([
                'data' => $quickReview_list,
                'message' => 'Quick Review projects retrieved successfully',
            ]);
        }

        if ($type === 'completed') {
            return response()->json([
                'data' => $completed_list,
                'message' => 'Completed projects retrieved successfully',
            ]);
        }
        if ($type === 'client_review') {
            return response()->json([
                'data' => $client_review,
                'message' => 'Client Review projects retrieved successfully',
            ]);
        }

        if ($type === 'payment_status') {
            return response()->json([
                'data' => $paymentEntries,
                'message' => 'Payment Status retrieved successfully',
            ]);
        }

        if ($type === 'journal_list') {
            return response()->json([
                'data' => $journalList,
                'message' => 'Journal List retrieved successfully',
            ]);
        }

        return response()->json([
            'data' => $data,
            // 'urgent_important_list' => $urgentImportant_list,
            // 'quickReview_list'      => $quickReview_list,
            // 'completed_list'         => $completed_list,
            // 'client_review'         => $client_review,
            'message' => 'Projects retrieved successfully',
        ]);
    }

    public function get_support_publication(Request $request)
    {
        $project_id = $request->project_id;
        $assign_user = $request->assign_user;
        $type = $request->type;
        $status = $request->status;
        $createdby = $request->created_by;
        $current_date_formatted = (new \DateTime)->format('Y-m-d');

        try {
            $details = ProjectAssignDetails::where('project_id', $project_id)
                ->where('type', 'publication_manager')
                // ->where('assign_user', $assign_user)
                ->first();

            if ($details) {
                // Update the existing record
                $details->status = $status;
                $details->type = $type;
                $details->created_by = $createdby;
                $details->updated_at = now();
                $details->save();

                // $activity = new ProjectActivity;
                // $activity->project_id = $project_id;
                // $activity->activity = 'Publication manager marked as '. $status;
                // $activity->created_by = $createdby;
                // $activity->created_date = date('Y-m-d H:i:s');
                // $activity->save();

                $created = User::with('createdByUser')->find($request->created_by);

                $employee = $created?->employee_name ?? null;
                $creator = $created?->createdByUser?->name ?? null;

                $activity = new ProjectActivity;
                $activity->project_id = $project_id;

                // Build "by ..." text cleanly
                if ($employee && $creator) {
                    $byText = " by {$employee} ({$creator})";
                } elseif ($employee) {
                    $byText = " by {$employee}";
                } elseif ($creator) {
                    $byText = " by {$creator}";
                } else {
                    $byText = '';
                }

                // Final activity text
                $activity->activity = "Publication manager marked as {$status}{$byText}";
                $activity->role = $creator;
                $activity->created_by = $createdby;
                $activity->created_date = now();
                $activity->save();

            } else {
                // Create a new record if none exists
                $details = new ProjectAssignDetails;
                $details->project_id = $project_id;
                $details->assign_user = $assign_user;
                $details->status = $status;
                $details->type = $type;
                $details->created_by = $createdby;
                $details->project_duration = $current_date_formatted;
                $details->assign_date = $current_date_formatted;
                $details->status_date = $current_date_formatted;
                $details->save();
            }

            // Save to ProjectLogs
            $projectLogs = new ProjectLogs;
            $projectLogs->project_id = $project_id;
            $projectLogs->employee_id = $assign_user;
            $projectLogs->status = $status;
            $projectLogs->status_type = $type;
            $projectLogs->assigned_date = $current_date_formatted;
            $projectLogs->status_date = $current_date_formatted;
            $projectLogs->created_by = $createdby;
            $projectLogs->created_date = $current_date_formatted;
            $projectLogs->assing_preview_id = ProjectLogs::where('project_id', $project_id)
                ->where('employee_id', $assign_user)
                ->orderBy('id', 'desc')
                ->value('id');
            $projectLogs->save();

            return response()->json(['message' => 'User assigned successfully'], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to assign user',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function getTemplate(Request $request)
    {
        $project = EntryProcessModel::with(['statusDatas', 'journalPaymentDetails:id,project_id,status,payment'])
            ->where('is_deleted', 0)
            ->where('project_id', $request->project_id)
            ->select('id', 'project_id', 'client_name', 'project_status', 'entry_date', 'contact_number', 'title', 'type_of_work', 'process_status', 'projectduration')
            ->first();

        if (! $project) {
            return response()->json(['error' => 'Project not found'], 404);
        }

        // Prepare details array for Blade template
        $details = [
            'client_name' => $project->client_name,
            'project_id' => $project->project_id,
            'project_title' => $project->title,
            'project_status' => $project->project_status ?? '-',
            'contact_number' => $project->contact_number ?? '-',
            'client_name' => $project->client_name ?? '-',
        ];

        // Render the Blade view with data
        $html = View::make('emails.ClientNotification', ['details' => $details])->render();

        // Remove HTML tags to get plain text
        $plainText = strip_tags($html);

        return response($plainText, 200)->header('Content-Type', 'text/plain');
    }
}
