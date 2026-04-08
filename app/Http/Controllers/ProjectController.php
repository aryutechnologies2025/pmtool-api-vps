<?php

namespace App\Http\Controllers;

use App\Mail\RejectEmail;
use App\Models\Activity;
use App\Models\ActivityDocuments;
use App\Models\ActivityReplies;
use App\Models\AssigneeStatus;
use App\Models\CommentsList;
use App\Models\EmployeePaymentDetails;
use App\Models\EntryProcessModel;
use App\Models\NotificationList;
use App\Models\NotificationLog;
use App\Models\PaymentStatusModel;
use App\Models\People;
use App\Models\ProjectActivity;
use App\Models\ProjectAssignDetails;
use App\Models\ProjectLogs;
use App\Models\ProjectStatus;
use App\Models\RejectReason;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ProjectController extends Controller
{
    public function store(Request $request)
    {
        try {
            $currentdate = (new \DateTime)->format('Y-m-d H:i:s');
            $activity = new Activity;
            $activity->project_id = $request->project_id;
            $activity->activity = $request->activity;
            $activity->created_by = $request->createdby;
            $activity->createdby_name = $request->createdby_name;
            $activity->created_date = $currentdate;
            $activity->save();

            // $activityp  = new ProjectActivity();
            // $activityp->project_id = $request->project_id;
            // $activityp->activity = 'Project created ';
            // $activityp->created_by = $request->createdby;
            // $activityp->created_date = date('Y-m-d H:i:s');
            // $activityp->save();

            // $activityR = new ActivityReplies();
            // $activityR->activity_id = $activity->id;
            // $activityR->reply_content = $request->content;

            // $activityR->project_id = $request->project_id;
            // $activityR->created_by = $request->createdby;
            // $activityR->createdby_name =  $request->createdby_name;
            // $activityR->created_date = $currentdate;
            // $activityR->is_read = 0;
            // $activityR->save();

            Log::info('Checking request data', ['request_data' => $request->all()]);

            if ($request->hasFile('activity_file')) {
                $files = $request->file('activity_file');
                if (! is_array($files)) {
                    $files = [$files];
                }

                // foreach ($files as $file) {
                //     if ($file->isValid()) {
                //         $originalName = $file->getClientOriginalName();
                //         $originalExtension = $file->getClientOriginalExtension();

                //         $cleanedName = preg_replace('/[^a-z0-9]+/i', '-', pathinfo($originalName, PATHINFO_FILENAME));
                //         // $cleanedName = str_replace('_', '', $cleanedName);
                //         $uniqueName = $cleanedName.'.'.$originalExtension;

                //         $path = public_path('activity_files');

                //         if (! is_dir($path)) {
                //             mkdir($path, 0775, true);
                //         }
                //         $file->move($path, $uniqueName);

                //         $activityd = new ActivityDocuments;
                //         $activityd->activity_id = $activity->id;
                //         $activityd->files = $uniqueName;
                //         $activityd->original_name = $cleanedName.'.'.$originalExtension;
                //         $activityd->created_by = $request->createdby;
                //         $activityd->type = 'activity';
                //         $activityd->created_date = date('Y-m-d H:i:s');
                //         $activityd->save();
                //     } else {
                //         Log::warning('Invalid file detected', ['filename' => $file->getClientOriginalName()]);
                //     }
                // }
                foreach ($files as $file) {
                    if ($file->isValid()) {

                        $originalName = $file->getClientOriginalName();

                        // Create unique folder for each upload
                        $folderName = time().rand(100, 999);
                        $path = public_path('activity_files/'.$folderName);

                        mkdir($path, 0775, true);

                        // Save file with exact same name (no overwrite!)
                        $file->move($path, $originalName);

                        $activityd = new ActivityDocuments;
                        $activityd->activity_id = $activity->id;
                        $activityd->files = $folderName.'/'.$originalName;
                        $activityd->original_name = $originalName;
                        $activityd->created_by = $request->createdby;
                        $activityd->type = 'activity';
                        $activityd->created_date = date('Y-m-d H:i:s');
                        $activityd->save();
                    }
                }

            } else {
                Log::info('No files detected in request');
            }

            return response()->json([
                'message' => 'Data stored successfully!',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to store data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function rejectReason(Request $request)
    {
        $currentdate = (new \DateTime)->format('Y-m-d H:i:s');
        if ($request->type == 'revertComment') {
            $revert = new RejectReason;
            $revert->project_id = $request->project_id;
            $revert->content = $request->reason;
            $revert->status = $request->type;
            $revert->created_by = $request->createdby;
            $revert->createdby_name = $request->createdby_name;
            $revert->created_date = $currentdate;
            $revert->save();
        }

        try {

            // Update the database record
            $createdby = $request->createdby;

            $activity = new RejectReason;
            $activity->project_id = $request->project_id;
            $activity->content = $request->reason;
            $activity->status = $request->type;
            $activity->created_by = $request->createdby;
            $activity->createdby_name = $request->createdby_name;
            $activity->created_date = $currentdate;

            $activity->save();

            //reject status
            $projectstatus = ProjectStatus::where('project_id', $request->project_id)->where('assign_id', $createdby)->first();
            $projectstatus->status = $request->type;
            $projectstatus->save();

            //reject status
            $projectassign = ProjectAssignDetails::where('project_id', $request->project_id)->where('assign_user', $createdby)->first();
            $projectassign->status = $request->type;
            $projectassign->save();

            //reject status
            // $projectassign = ProjectLogs::where('project_id', $request->project_id)->where('employee_id', $createdby)->first();
            // $projectassign->status = $request->type;
            // $projectassign->save();

            $projectassign = new ProjectLogs;
            $projectassign->project_id = $request->project_id;
            $projectassign->employee_id = $createdby;
            $projectassign->assigned_date = $currentdate;
            $projectassign->status = $request->type;
            $projectassign->status_type = $request->project_status;
            $projectassign->status_date = $currentdate;
            $projectassign->created_by = $createdby;
            $projectassign->created_date = $currentdate;
            $projectassign->save();

            Log::info('Checking request data', ['request_data' => $request->project_id]);
            Log::info('Checking request data', ['request_data' => $request->createdby]);
        
            //project activity
            $activity = new ProjectActivity;
            $activity->project_id = $request->project_id;
            $activity->activity = 'Rejected';
            $activity->role = $request->project_status;
            $activity->created_by = $request->createdby;
            $activity->created_date = date('Y-m-d H:i:s');
            $activity->save();
            // Update project status if needed

            // $emails = [
            //     'projectManager' => User::where('position', 13)->first()?->email_address,
            //     'teamManager' => User::where('position', 14)->first()?->email_address,
            //     'adminEmail' => User::where('position', 'Admin')->first()?->email_address,
            // ];

            // $employeedetails = User::with(['createdByUser'])->where('id', $request->createdby)->first();
            // // dd($employeedetails);
            // $projectDetails = EntryProcessModel::where('id', $request->project_id)->first();
            // $reasonList = RejectReason::where('created_by', $request->createdby)
            //     ->where('project_id', $request->project_id)
            //     ->first();

            // if (!empty($emails['projectManager']) && !empty($emails['teamManager']) && !empty($emails['adminEmail']) && !empty($employeedetails->email_address)) {
            //     try {
            //         // Send email to writer with CC to others
            //         Mail::to($emails['projectManager'], $emails['teamManager'])
            //             ->cc($emails['adminEmail'])
            //             ->send(new RejectEmail([
            //                 'projectManagerEmail' => $emails['projectManager'],
            //                 'teamManagerEmail' => $emails['teamManager'],
            //                 'adminEmail' => $emails['adminEmail'],
            //                 'rejectReason' => $reasonList->content,
            //                 'writer_status' => $projectDetails->process_status,
            //                 'employee_name' => $employeedetails->employee_name,
            //                 'role' => $employeedetails->createdByUser?->name,
            //                 'phone_number' => $employeedetails->phone_number,
            //                 'project_id' => $projectDetails->project_id,
            //             ]));

            //         return response()->json(['success' => true, 'message' => 'Email sent successfully.']);
            //     } catch (\Exception $e) {
            //         Log::error('Mail failed: ' . $e->getMessage());

            //         return response()->json(['success' => false, 'message' => 'Failed to send email.']);
            //     }
            // } else {
            //     Log::error('One or more email addresses are missing.');
            //     return response()->json(['success' => false, 'message' => 'One or more email addresses are missing.']);
            // }

            return response()->json([
                'message' => 'Data stored successfully!',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to store data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function rejectReasonView(Request $request)
    {
        $createdby = $request->created_by;
        $projectid = $request->project_id;

        $reasonlist = RejectReason::where('created_by', $createdby)->where('project_id', $projectid)->get();

        // Return the response as JSON
        return response()->json([
            'success' => true,
            'reasonlist' => $reasonlist,
        ]);
    }

    public function documentUpload(Request $request)
    {
        try {
            $activity = new Activity;
            $activity->project_id = $request->project_id;
            $activity->activity = 'Completed';
            $activity->created_by = $request->createdby;
            $activity->createdby_name = $request->createdby_name;
            $activity->created_date = date('Y-m-d H:i:s');
            $activity->save();

            if ($request->hasFile('activity_file')) {
                $files = $request->file('activity_file');
                if (! is_array($files)) {
                    $files = [$files];
                }

                foreach ($files as $file) {
                    if ($file->isValid()) {
                        $originalName = $file->getClientOriginalName();
                        $originalExtension = $file->getClientOriginalExtension();

                        $cleanedName = strtolower(preg_replace('/[^a-z0-9]+/i', '-', pathinfo($originalName, PATHINFO_FILENAME)));
                        $cleanedName = str_replace('_', '', $cleanedName);

                        $uniqueName = $cleanedName.'.'.$originalExtension;

                        $path = public_path('activity_files');
                        if (! is_dir($path)) {
                            mkdir($path, 0775, true);
                        }
                        $file->move($path, $uniqueName);

                        $activityd = new ActivityDocuments;
                        $activityd->activity_id = $activity->id;
                        $activityd->files = $uniqueName;
                        $activityd->original_name = $cleanedName.'.'.$originalExtension;
                        $activityd->created_by = $request->createdby;
                        $activityd->type = 'status';
                        $activityd->created_date = now();
                        $activityd->save();
                    } else {
                        Log::warning('Invalid file detected', ['filename' => $file->getClientOriginalName()]);
                    }
                }

                return response()->json([
                    'message' => 'Files uploaded successfully',
                    'files' => $files,
                ], 200);
            } else {
                return response()->json(['error' => 'No files were uploaded'], 400);
            }
        } catch (\Exception $e) {
            // Log the exception if an error occurs during file upload
            Log::error('File upload failed', [
                'error_message' => $e->getMessage(),
                'stack_trace' => $e->getTraceAsString(),
            ]);

            // Return an error response
            return response()->json([
                'error' => 'An error occurred during the file upload.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function getProjectActivity(Request $request)
    {
        $created_by = $request->input('createdby');

        $commentsList_P = CommentsList::where('created_by', $created_by)->pluck('project_id')->toArray();
        $commentsList_id = CommentsList::where('created_by', $created_by)->pluck('id')->toArray();

        $activity_id = NotificationList::whereIn('project_id', $commentsList_P)->whereIn('comment_id', $commentsList_id)->where('notification_type', 'activity')->pluck('notification_id')->toArray();

        $activityreply_id = NotificationList::whereIn('project_id', $commentsList_P)->whereIn('comment_id', $commentsList_id)->where('notification_type', 'reply')->pluck('notification_id')->toArray();

        $activities = Activity::with(['activityData', 'createdByUser'])
            ->where('is_read', 0)
            ->orderBy('id', 'desc');

        if (! empty($activity_id)) {
            $activities->whereNotIn('id', $activity_id);
        }
        if (! empty($created_by)) {
            $activities->where('created_by', '!=', $created_by);
        }
        // Fetch the results
        $activities = $activities->get();

        $activitiesCount = $activities->count();

        $repliesQuery = ActivityReplies::with('activity');
        if (! empty($activityreply_id)) {
            $repliesQuery->whereNotIn('id', $activityreply_id);
        }
        if (! empty($created_by)) {
            $repliesQuery->where('created_by', '!=', $created_by);
        }
        $replies = $repliesQuery->get();

        $repliesCount = $replies->count();

        $statuslist = AssigneeStatus::with(['statusData'])->where('is_read', 0)->get();

        $statusCount = $statuslist->count();

        $totalcount = $activitiesCount + $repliesCount + $statusCount;

        return response()->json([
            'success' => true,
            'data' => [
                'activities' => $activities,
                'replies' => $replies,
                'totalcount' => $totalcount,
                'statuslist' => $statuslist,
            ],
        ]);
    }

    public function getProjectReply(Request $request)
    {
        $createdBy = $request->input('createdby');

        // $commentsList = CommentsList::where('created_by', $createdBy)
        //     ->get(['project_id', 'activity_list', 'activity_reply_list'])
        //     ->map(function ($comment) {
        //         return [
        //             'project_id' => $comment->project_id,
        //             'activity_list' => json_decode($comment->activity_list, true), // Decode JSON
        //             'activity_reply_list' => json_decode($comment->activity_reply_list, true), // Decode JSON
        //         ];
        //     })
        //     ->toArray();

        // dd($commentsList);

        // $commentsList = collect($commentsList);

        // if ($commentsList->isNotEmpty()) {
        //     $acivityid = $commentsList->first()['activity_list'] ?? [];
        //     $acivityreplyid = $commentsList->first()['activity_reply_list'] ?? [];
        //     $projectid = isset($commentsList->first()['project_id']) ? [(int) $commentsList->first()['project_id']] : [];
        // } else {
        //     $acivityid = [];
        //     $acivityreplyid = [];
        //     $projectid = [];
        // }

        $commentsList_P = CommentsList::where('created_by', $createdBy)->pluck('project_id')->toArray();
        $commentsList_id = CommentsList::where('created_by', $createdBy)->pluck('id')->toArray();

        $activity_id = NotificationList::whereIn('project_id', $commentsList_P)->whereIn('comment_id', $commentsList_id)->where('notification_type', 'activity')->pluck('notification_id')->toArray();

        $activityreply_id = NotificationList::whereIn('project_id', $commentsList_P)->whereIn('comment_id', $commentsList_id)->where('notification_type', 'reply')->pluck('notification_id')->toArray();

        $project = EntryProcessModel::where(function ($query) use ($createdBy) {
            $query->where('writer', $createdBy)
                ->orWhere('reviewer', $createdBy)
                ->orWhere('statistican', $createdBy);
        })->pluck('id')->toArray();

        $activitiesQuery = Activity::with(['activityData', 'createdByUser'])
            ->whereIn('project_id', $project)
            ->orderBy('id', 'desc');

        if (! empty($activity_id)) {
            $activitiesQuery->whereNotIn('id', $activity_id);
        }

        if (! empty($createdBy)) {
            $activitiesQuery->where('created_by', '!=', (int) $createdBy);
        }
        $activities = $activitiesQuery->get();

        $activitiesCount = $activities->count();
        $repliesQuery = ActivityReplies::whereIn('project_id', $project)->with('activity');

        if (! empty($activityreply_id)) {
            $repliesQuery->whereNotIn('id', $activityreply_id);
        }

        if (! empty($createdBy)) {
            $repliesQuery->where('created_by', '!=', (int) $createdBy);
        }

        $replies = $repliesQuery->get();

        $repliesCount = $replies->count();

        $totalcount = $activitiesCount + $repliesCount;

        return response()->json([
            'success' => true,
            'data' => [
                'activities' => $activities,
                'replies' => $replies,
                'totalcount' => $totalcount,
            ],
        ]);
    }

    public function storeReplyActivity(Request $request)
    {
        try {
            $currentdate = (new \DateTime)->format('Y-m-d H:i:s');
            // Update the database record
            $activity = new ActivityReplies;
            $activity->activity_id = $request->activity_id;
            $activity->reply_content = $request->content;
            $activity->project_id = $request->project_id;
            $activity->created_by = $request->createdby;
            $activity->createdby_name = $request->createdby_name;
            $activity->created_date = $currentdate;

            $activity->save();

            $activity = new ProjectActivity;
            $activity->project_id = $request->project_id;
            $activity->activity = 'Activity Replied';
            $activity->created_by = $request->createdby;
            $activity->created_date = date('Y-m-d H:i:s');
            $activity->save();

            return response()->json([
                'message' => 'Data stored successfully!',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to store data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateActivity(Request $request)
    {
        // Ensure you get the activity ID from the request
        $activityId = $request->input('activity_id');
        $typeVal = $request->input('type');

        if ($typeVal === 'activity') {
            // Find the activity by ID
            $activity = Activity::find($activityId);

            if ($activity) {
                // Set the 'is_read' field to 1
                $activity->is_read = 1;
                $activity->save();

                return response()->json([
                    'success' => true,
                    'message' => 'Activity marked as read successfully',
                ]);
            }
        } else {
            // Find the activity by ID
            $activity = ActivityReplies::find($activityId);

            if ($activity) {
                // Set the 'is_read' field to 1
                $activity->is_read = 1;
                $activity->save();

                return response()->json([
                    'success' => true,
                    'message' => 'Activity marked as read successfully',
                ]);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'Activity not found',
        ], 404);
    }

    public function updateActivityReply(Request $request)
    {
        // Ensure you get the activity ID from the request
        $activityId = $request->input('activity_id');

        // Find the activity by ID
        $activity = ActivityReplies::find($activityId);

        if ($activity) {
            // Set the 'is_read' field to 1
            $activity->is_read = 1;
            $activity->save();

            return response()->json([
                'success' => true,
                'message' => 'Activity marked as read successfully',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Activity not found',
        ], 404);
    }

    // public function markReplyAsRead(Request $request)
    // {
    //     $projectId = $request->projectid;
    //     $replyId = $request->activity_id;
    //     $createdby = $request->createdby;
    //     $project_number = $request->project_number;
    //     $type = $request->type;

    //     if ($type === 'statusassign') {
    //         // $activity = AssigneeStatus::find($replyId);

    //         // if ($activity) {
    //         //     // Set the 'is_read' field to 1
    //         //     $activity->is_read = 1;
    //         //     $activity->save();
    //         //     return response()->json([
    //         //         'success' => true,
    //         //         'message' => 'Activity marked as read successfully'
    //         //     ]);
    //         // }
    //         $updated = AssigneeStatus::where('project_id', $project_number)
    //             // ->where('created_by', $createdby)
    //             ->update(['is_read' => 1]);

    //         $updated_comments = Activity::where('project_id', $project_number)
    //             ->update(['is_read' => 1]);

    //         if ($updated && $updated_comments) {
    //             return response()->json([
    //                 'success' => true,
    //                 'message' => 'All related activities marked as read successfully'
    //             ]);
    //         }
    //     } else {
    //         // $activityreply_id = ActivityReplies::where('project_id', $projectId)->get();

    //         $commentslist = new CommentsList();
    //         $commentslist->project_id = $projectId;
    //         $commentslist->comment_id = $replyId;
    //         $commentslist->commend_type = $type;
    //         $commentslist->created_by = $createdby;
    //         $commentslist->is_read = 1;
    //         //$commentslist->created_date = date('Y-m-d H:i:s');
    //         $commentslist->created_date = date('Y-m-d H:i:s');
    //         $commentslist->save();

    //         $activityreply_id = ActivityReplies::select('id')->where('project_id', $projectId)->get();
    //         $activity_id = Activity::select('id')->where('project_id', $projectId)->get();

    //         foreach ($activity_id as $act) {
    //             $activitylist = new NotificationList();
    //             $activitylist->project_id = $projectId;
    //             $activitylist->comment_id = $commentslist->id;
    //             $activitylist->notification_type = 'activity';
    //             $activitylist->notification_id = $act->id;
    //             $activitylist->created_by = $createdby;
    //             $activitylist->save();
    //         }

    //         foreach ($activityreply_id as $actReply) {
    //             $activityReplylist = new NotificationList();
    //             $activityReplylist->project_id = $projectId;
    //             $activityReplylist->comment_id = $commentslist->id;
    //             $activityReplylist->notification_type = 'reply';
    //             $activityReplylist->notification_id = $actReply->id;
    //             $activityReplylist->created_by = $createdby;
    //             $activityReplylist->save();
    //         }
    //     }

    //     return response()->json(['message' => 'Reply marked as read']);
    // }

    public function markReplyAsRead(Request $request)
    {
        $action = $request->input('action'); // 'statusassign', 'notification', or 'clear_all'

        if ($action === 'statusassign') {
            $project_number = $request->input('project_number');

            AssigneeStatus::where('project_id', $project_number)->update(['is_read' => 1]);
            Activity::where('project_id', $project_number)->update(['is_read' => 1]);

            return response()->json([
                'success' => true,
                'message' => 'All related statusassign activities marked as read successfully',
            ]);
        } elseif ($action === 'notification') {
            $notificationId = $request->input('project_number');

            // $notification = NotificationLog::find($notificationId);

            // if (!$notification) {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'Notification not found.',
            //     ], 404);
            // }
            NotificationLog::where('entry_process_id', $notificationId)->update(['status' => 'read']);

            // $notification->update(['status' => 'unread']);

            return response()->json([
                'success' => true,
                'message' => 'Notification marked as unread successfully',
            ]);
        } elseif ($action === 'clear_all') {
            $projectId = $request->input('projectid');

            if ($projectId) {
                AssigneeStatus::where('project_id', $projectId)->update(['is_read' => 1]);
                Activity::where('project_id', $projectId)->update(['is_read' => 1]);
                NotificationLog::where('project_id', $projectId)->update(['status' => 'read']);
            } else {
                AssigneeStatus::query()->update(['is_read' => 1]);
                Activity::query()->update(['is_read' => 1]);
                NotificationLog::query()->update(['status' => 'read']);
            }

            return response()->json([
                'success' => true,
                'message' => 'All notifications and activities marked as read',
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Invalid action'], 400);
    }

    public function getNotification_task(Request $request)
    {
        $assign_user = '138';
        Log::info('Fetching user data', ['request' => $request->all()]);

        $peopleIds_pm = People::where('position', '13')
            ->pluck('id')
            ->filter()
            ->values()
            ->toArray();

        $peopleIds_sme = People::where('position', '28')
            ->pluck('id')
            ->filter()
            ->values()
            ->toArray();

        $peopleIds_admin = People::where('position', 'Admin')
            ->pluck('id')
            ->filter()
            ->values()
            ->toArray();

        $entryStatuses = ['in_progress', 'pending_author', 'completed'];
        $entryData = EntryProcessModel::with(['userData1'])
            ->select('id', 'project_id', 'title', 'process_status', 'created_by', 'created_at')
            ->whereIn('process_status', $entryStatuses)
            ->when(! empty($peopleIds_admin), function ($query) use ($peopleIds_admin) {
                return $query->whereIn('created_by', $peopleIds_admin);
            })
            ->get()
            ->groupBy('process_status');

        Log::info('Entry Data:', ['entryData' => $entryData]);

        $entryStatuses = ['pending_author', 'completed'];
        $entryPMData = EntryProcessModel::with(['userData1'])
            ->whereIn('process_status', $entryStatuses)
            ->when(! empty($peopleIds_pm), function ($query) use ($peopleIds_pm) {
                return $query->whereIn('created_by', $peopleIds_pm);
            })
            ->select('id', 'project_id', 'title', 'process_status', 'created_by', 'created_at')
            ->get()
            ->groupBy('process_status');

        Log::info('Entry PM Data:', ['entryPMData' => $entryPMData]);

        $roles = ['writer', 'reviewer', 'statistican'];

        $adminTasks = ProjectAssignDetails::with(['UserDate'])
            ->whereIn('type', $roles)
            ->where('created_by', 9)
            ->whereIn('status', ['to_do', 'client_review', 'correction'])
            ->get()
            ->groupBy(['type', 'status']);

        $pmStatuses = ['to_do', 'client_review', 'correction', 'plag_correction'];
        $pmTasks = ProjectAssignDetails::with(['UserDate'])
            ->whereIn('type', $roles)
            ->when(! empty($peopleIds_pm), function ($query) use ($peopleIds_pm) {
                return $query->whereIn('created_by', $peopleIds_pm);
            })
            ->whereIn('status', $pmStatuses)
            ->get()
            ->groupBy(['type', 'status']);

        $smeStatuses = ['correction', 'client_review', 'completed'];
        $smeTasks = ProjectAssignDetails::with(['UserDate'])
            ->when(! empty($peopleIds_sme), function ($query) use ($peopleIds_sme) {
                return $query->whereIn('created_by', $peopleIds_sme);
            })
            ->whereIn('status', $smeStatuses)
            ->get()
            ->groupBy(['type', 'status']);

        $publicationStatuses = [
            'pending_author',
            'submitted',
            'rejected',
            'resubmission',
            'reviewer_comments',
            'published',
            'peer_review',
            'withdrawal',
            'submit_journal',
        ];
        $publicationTasks = ProjectAssignDetails::with(['UserDate'])
            ->whereIn('type', ['publication_manager'])
            ->whereIn('status', $publicationStatuses)
            ->get()
            ->groupBy(['created_by', 'status']);

        $paymentStatuses = ['completed', 'advance_pending', 'partial_payment_pending', 'final_payment_pending'];
        $payments = PaymentStatusModel::with(['userData'])
            ->whereIn('payment_status', $paymentStatuses)
            ->get()
            ->groupBy('payment_status');

        // Send Notifications
        $this->sendNotificationsForEntryProcess($entryData, $assign_user);
        $this->sendNotificationsForEntryProcess($entryPMData, $assign_user);
        $this->sendNotificationsForAdminTasks($adminTasks, $assign_user);
        $this->sendNotificationsForPMTasks($pmTasks, $assign_user);
        $this->sendNotificationsForSMETasks($smeTasks, $assign_user);
        $this->sendNotificationsForPublicationTasks($publicationTasks, $assign_user);
        $this->sendNotificationsForPayments($payments, $assign_user);

        return response()->json([
            'message' => 'Notifications sent successfully',
            'data' => compact(
                'entryData',
                'entryPMData',
                'adminTasks',
                'pmTasks',
                'smeTasks',
                'publicationTasks',
                'payments'
            ),
        ]);
    }

    private function sendNotificationsForEntryProcess($entryData, $assign_user)
    {
        foreach ($entryData as $status => $entries) {
            foreach ($entries as $entry) {
                $employeeName = optional($entry->userData1)->employee_name ?? 'Unknown';
                $message = "Entry process task is $status for $employeeName";
                $this->sendNotificationToPosition([$entry], [13, 28], $message, $assign_user);
            }
        }
    }

    private function sendNotificationsForAdminTasks($adminTasks, $assign_user)
    {
        foreach ($adminTasks as $role => $statuses) {
            foreach ($statuses as $status => $entries) {
                foreach ($entries as $entry) {
                    $employeeName = optional($entry->UserDate)->employee_name ?? 'Unknown';
                    $message = "Admin ($role) task is $status for $employeeName";
                    $this->sendNotificationToPosition([$entry], [14, 13, 28], $message, $assign_user);
                }
            }
        }
    }

    private function sendNotificationsForPMTasks($pmTasks, $assign_user)
    {
        foreach ($pmTasks as $role => $statuses) {
            foreach ($statuses as $status => $entries) {
                foreach ($entries as $entry) {
                    $employeeName = optional($entry->UserDate)->employee_name ?? 'Unknown';
                    $message = "PM ($role) task is $status for $employeeName";
                    $this->sendNotificationToPosition([$entry], [14, 13, 28], $message, $assign_user);
                }
            }
        }
    }

    private function sendNotificationsForSMETasks($smeTasks, $assign_user)
    {
        foreach ($smeTasks as $role => $statuses) {
            foreach ($statuses as $status => $entries) {
                foreach ($entries as $entry) {
                    $employeeName = optional($entry->UserDate)->employee_name ?? 'Unknown';
                    $message = "SME ($role) task is $status for $employeeName";
                    $this->sendNotificationToPosition([$entry], [13, 28], $message, $assign_user);
                }
            }
        }
    }

    private function sendNotificationsForPublicationTasks($publicationTasks, $assign_user)
    {
        foreach ($publicationTasks as $createdBy => $statuses) {
            foreach ($statuses as $status => $entries) {
                foreach ($entries as $entry) {
                    $employeeName = optional($entry)->assign_user ?? 'Unknown';
                    $message = "Publication task is $status for $employeeName";
                    $this->sendNotificationToPosition([$entry], [13, 28], $message, $assign_user);
                }
            }
        }
    }

    private function sendNotificationsForPayments($payments, $assign_user)
    {
        foreach ($payments as $status => $entries) {
            foreach ($entries as $entry) {
                $employeeName = optional($entry->userData)->employee_name ?? 'Unknown';
                $message = "Payment task is $status for $employeeName";
                $this->sendNotificationToPosition([$entry], [13, 28], $message, $assign_user);
            }
        }
    }

    // private function sendNotificationToPosition($entries, array $positionIds, string $message)
    // {
    //     foreach ($entries as $entry) {
    //         $assignName = optional($entry->UserDate)->employee_name ?? 'Unknown';

    //         foreach ($positionIds as $positionId) {
    //             $exists = EntryProcessModel::where('id', $entry->id)->exists();

    //             if ($exists) {

    //                 // $alreadyExists = NotificationLog::where([
    //                 //     'entry_process_id' => $entry->id,
    //                 //     'project_id'       => $entry->project_id,
    //                 //     'assign_id'        =>  "14",
    //                 //     'position_id'      => $positionId,
    //                 //     'message'          => $message,
    //                 //     'employee_name'    => $assignName,
    //                 // ])->exists();
    //                 $alreadyExists = NotificationLog::where('entry_process_id', $entry->id)->exists();

    //                 if (!$alreadyExists) {
    //                     NotificationLog::create([
    //                         'entry_process_id' => $entry->id,
    //                         'project_id'       => $entry->project_id,
    //                         'assign_id'        => $fallbackAssignId ?? "14",
    //                         'position_id'      => $positionId,
    //                         'message'          => $message,
    //                         'employee_name'    => $assignName,
    //                         'status'           => 'unread',
    //                     ]);
    //                 }
    //             } else {
    //                 Log::warning("Entry process ID {$entry->id} does not exist. Skipping notification log creation.");
    //             }
    //         }
    //     }
    // }
    private function sendNotificationToPosition($entries, array $positionIds, string $message, $fallbackAssignId)
    {
        foreach ($entries as $entry) {
            $assignName = optional($entry->UserDate)->employee_name ?? 'Unknown';

            foreach ($positionIds as $positionId) {
                $exists = EntryProcessModel::where('id', $entry->id)->exists();

                if ($exists) {
                    //     $alreadyExists = NotificationLog::where([
                    //         'entry_process_id' => $entry->id,
                    //         'project_id'       => $entry->project_id,
                    //         'assign_id'        => "null",
                    //         'position_id'      => $positionId,
                    //         'message'          => $message,
                    //         'employee_name'    => $assignName,
                    //     ])->exists();
                    $alreadyExists = NotificationLog::where('entry_process_id', $entry->id)
                        ->where('project_id', $entry->project_id)
                        ->where('position_id', $positionId)
                        ->where('message', $message)
                        ->exists();

                    if (! $alreadyExists) {
                        NotificationLog::create([
                            'entry_process_id' => $entry->id,
                            'project_id' => $entry->project_id,
                            'assign_id' => 'null',
                            'position_id' => $positionId,
                            'message' => $message,
                            'employee_name' => $assignName,
                            'status' => 'unread',
                        ]);
                    }
                } else {
                    Log::warning("Entry process ID {$entry->id} does not exist. Skipping notification log creation.");
                }
            }
        }
    }

    public function unreadNotification($id)
    {
        $notification = NotificationLog::findorFail($id);
        $notification->update(['status' => 'read']);

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read successfully',
        ]);
    }

    public function getNotification(Request $request)
    {
        $userId = $request->position_id;

        $notifications = NotificationLog::with([
            'project:id,project_id,process_status',
            'statusData',
            // 'projects:id,project_id,process_status',
            'entryProcess:id,project_id,title,process_status',
            'assignedPosition' => function ($q) {
                $q->select('id', 'status', 'assign_user')
                    ->with([
                        'userDate:id,employee_name,employee_type,created_by',
                        'userDate.createdByUser:id,name',

                    ]);
            },
            'assignedPositions' => function ($q) {
                $q->select('id', 'status', 'assign_user')
                    ->with([
                        'userDate:id,employee_name,employee_type,created_by',
                        'userDate.createdByUser:id,name',

                    ]);
            },

        ])
            ->where('position_id', $userId)
            ->where('status', 'unread')
            ->get()
            ->map(function ($notification) {
                return [
                    'project_id' => $notification->statusData ?? null,
                    'message' => $notification->message,
                    'assigned_position_status' => $notification->assignedPosition->status ?? null,
                    // 'employee_name' => $notification->assignedPosition->userDate->employee_name ?? null,
                    // 'employee_type' => $notification->assignedPosition->userDate->employee_type ?? null,
                    // 'created_by_name' => $notification->assignedPosition->userDate->createdByUser->name ?? null,
                    // 'process_status' => $notification->project->process_status ?? null,
                    // 'project_ids'=> $notification->project->project_id ?? null,
                    // 'employee_names'=> $notification->assignedPosition->userDate->employee_name ?? null,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'notification' => $notifications,
            ],
        ]);
    }

    public function getTypeOfWorkByBudget(Request $request)
    {

        $month = $request->input('month', date('m')); // Default to current month if not provided
        $year = $request->input('year', date('Y')); // Default to current year if not provided

        $rawDetails = EntryProcessModel::select('type_of_work', DB::raw('SUM(budget) as total_budget'))
            ->whereMonth('entry_date', $month)
            ->whereYear('entry_date', $year)
            ->groupBy('type_of_work')
            ->get();

        // Convert to key-value pair array
        $details = [];
        foreach ($rawDetails as $item) {
            $details[$item->type_of_work] = (int) $item->total_budget;
        }

        return response()->json([
            'details' => $details,
        ]);
    }

    //     public function getIncome_Expense(Request $request)
    //     {
    //         $month = $request->input('month', date('m')); // Default to current month if not provided
    //         $year = $request->input('year', date('Y')); // Default to current year if not provided

    //         $journal_details = EmployeePaymentDetails::where('type', 'publication_manager')
    //             ->whereMonth('created_at', $month)
    //             ->whereYear('created_at', $year)
    //             ->sum('payment');

    //         $freelancer_details = EmployeePaymentDetails::where('type', '!=', 'publication_manager')
    //             ->whereMonth('created_at', $month)
    //             ->whereYear('created_at', $year)
    //             ->sum('payment');

    //         // $userhrms = DB::connection('mysql_medics_hrms')
    //         //     ->table('salary_information')
    //         //     ->whereMonth('created_at', $month)
    //         //     ->whereYear('created_at', $year)
    //         //     ->sum('salary_amount');

    //         return response()->json([
    //             'journal_details' => $journal_details,
    //             'freelancer_details' => $freelancer_details,
    //             //  'userhrms' => $userhrms
    //         ]);
    //     }

    public function getIncome_Expense(Request $request)
    {
        $month = $request->input('month', date('m'));
        $months = $request->input('months', date('m'));
        $year = $request->input('year', date('Y'));

        // Journal payments (publication managers)
        // $journal_details = EmployeePaymentDetails::where('type', 'publication_manager')
        // ->with('entryProcess')
        //     ->whereMonth('created_at', $months)
        //     ->whereYear('created_at', $year)
        //     ->sum('payment');
        $journal_records = EmployeePaymentDetails::where('type', 'publication_manager')
            ->whereHas('entryProcess', function ($q) use ($months, $year) {
                $q->whereMonth('entry_date', $months)
                    ->whereYear('entry_date', $year);
            })
            ->with('entryProcess')
            ->get();

        // Total payment
        $journal_details = $journal_records->sum('payment');

        log::info('Total Payment: '.$journal_details);

        // Log individual records
        foreach ($journal_records as $record) {
            log::info('ID: '.$record->id);
            log::info('Project ID: '.$record->project_id);
        }

        // Freelancer payments (non-publication managers)
        $freelancer_details = EmployeePaymentDetails::where('type', '!=', 'publication_manager')
            ->whereHas('entryProcess', function ($q) use ($months, $year) {
                $q->whereMonth('entry_date', $months)
                    ->whereYear('entry_date', $year);
            })
            ->with('entryProcess')
            ->sum('payment');

        // Employee payroll (from HRMS API)
        $totalPayroll = 0;
        try {
            $response = Http::get('https://hrmsapi.medicsresearch.com/api/emp-attendances/monthly-report', [
                'month' => $month,
                'year' => $year,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $totalPayroll = collect($data)->sum('total_salary_with_ot');
            }
        } catch (\Exception $e) {
            \Log::error('HRMS Payroll API error: '.$e->getMessage());
        }

        // Office expenses (from HRMS API)
        $officeExpenses = 0;
        try {
            $response = Http::get('https://hrmsapi.medicsresearch.com/api/expense',[
              'month' => $months,
              'year' => $year
              ]);

            if ($response->successful()) {
                $data = $response->json();
                $officeExpenses = collect($data['data'])->sum('amount');
            }
        } catch (\Exception $e) {
            \Log::error('HRMS Expense API error: '.$e->getMessage());
        }

        return response()->json([
            'journal_details' => $journal_details,
            'freelancer_details' => $freelancer_details,
            'total_payroll' => $totalPayroll,
            'office_expenses' => $officeExpenses,
            'total_expense' => $journal_details + $freelancer_details + $totalPayroll + $officeExpenses,
        ]);
    }
}
