<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EntryProcessModel;
use App\Models\People;
use App\Models\ProjectAssignDetails;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PublicationManagerController extends Controller
{
    public function publication_dashboard(Request $request)
    {
        $entries = EntryProcessModel::select('id', 'type_of_work', 'project_id', 'process_status', 'hierarchy_level', 'projectduration', 'created_by')
            ->where('is_deleted', 0)
            ->where('process_status', '!=', 'completed')
            ->get();

        $totalproject = $entries->count();
        $projectids = $entries->pluck('id')->toArray();
        $peopleIds_pm = People::where('position', '28')
            ->pluck('id')
            ->filter()
            ->values()
            ->toArray();

        $publication_count = [
            'submit_to_journal' => 0,
            'peer_review' => 0,
            'pending_author' => 0,
            'rejected' => 0,
            'reviewer_comments' => 0,
            'resubmission' => 0,
            'withdrawal' => 0,
            'submitted' => 0,
            'published' => 0,
        ];


        $urgentImportantCount = 0;
        $quickReview = 0;
        $reviewerComments = 0;
        $resubmission = 0;
        $publication_count = $publication_count ?? [];

        $projectcompleted = ProjectAssignDetails::with('projectData')
            // ->whereIn('project_id', $projectids)
            // ->whereIn('created_by', $peopleIds_pm)
            ->where('type', 'publication_manager')
            ->whereHas('projectData', fn ($q) => $q->where('process_status', '!=', 'completed'))
            ->get()
            ->unique('project_id');

        if ($projectcompleted->isEmpty()) {
            return response()->json(['error' => 'No projects found in ProjectAssignDetails']);
        }

        //inprogress
        $in_progress = ProjectAssignDetails::with(['projectDatas:id,project_id,hierarchy_level'])
            ->whereIn('project_id', $projectids)
            ->whereIn('created_by', $peopleIds_pm)
            ->orderBy('created_at', 'desc')
            ->where('type', 'publication_manager')
            ->whereIn('status', ['submit_to_journal', 'pending_author', 'rejected', 'withdrawal'])
            ->get()
            ->unique('project_id')
            ->values(); 

        $in_progress_count = $in_progress->count(); 



        //resubmission

        $resubmission_list = ProjectAssignDetails::with(['projectDatas:id,project_id,hierarchy_level'])
            ->whereIn('project_id', $projectids)
            ->whereIn('created_by', $peopleIds_pm)
            ->orderBy('created_at', 'desc')
            ->where('type', 'publication_manager')
            ->whereIn('status', ['resubmission'])
            ->get()
            ->unique('project_id');
        $resubmission_list_count = ProjectAssignDetails::with(['projectDatas:id,project_id,hierarchy_level'])
            ->whereIn('project_id', $projectids)
            // ->whereIn('created_by', $peopleIds_pm)
            ->orderBy('created_at', 'desc')
            ->where('type', 'publication_manager')
            ->whereIn('status', ['resubmission'])
            ->get()
            ->unique('project_id');

        // dd("test",$resubmission_list);
        Log::info($resubmission_list);
        $resubmission_count = $resubmission_list_count->count();

        //reviewerComments

        $reviewerComments_list = ProjectAssignDetails::with(['projectDatas:id,project_id,hierarchy_level'])
            ->whereIn('project_id', $projectids)
            ->whereIn('created_by', $peopleIds_pm)
            ->orderBy('created_at', 'desc')
            ->where('type', 'publication_manager')
            ->whereIn('status', ['reviewer_comments'])
            ->get()
            ->unique('project_id');
        $reviewerComments_list_count = ProjectAssignDetails::with(['projectDatas:id,project_id,hierarchy_level'])
            ->whereIn('project_id', $projectids)
            // ->whereIn('created_by', $peopleIds_pm)
            ->orderBy('created_at', 'desc')
            ->where('type', 'publication_manager')
            ->whereIn('status', ['reviewer_comments'])
            ->get()
            ->unique('project_id');
        $reviewerComments_count = $reviewerComments_list_count->count();

        foreach ($projectcompleted as $project) {
            // if (!empty($project->projectData) && $project->projectData->hierarchy_level === 'urgent_important') {
            //     $urgentImportantCount++;
            // }
            // if ($project->review == 'quickReview') {
            //     $quickReview++;
            // }
            if ($project->status == 'reviewer_comments') {
                $reviewerComments++;
            }
            if ($project->status == 'resubmission') {
                $resubmission++;
            }





            $publication_count[$project->status] = ($publication_count[$project->status] ?? 0) + 1;
        }

        //urgentImportant_list
        // $urgentImportant_list = ProjectAssignDetails::with(['projectData'])
        //     ->whereIn('project_id', $projectids)
        //     // ->whereIn('created_by', $peopleIds_pm)
        //     ->where('type', 'publication_manager')
        //     ->whereHas('projectData', function ($query) {
        //         $query->where('hierarchy_level', 'urgent_important');
        //     })
        //     ->select('id', 'status', 'project_id', 'assign_user', 'assign_date', 'type', 'assign_date', 'comments', 'type_of_article', 'review')
        //     ->get()
        //     ->unique('project_id');

        // $urgentImportant_list_count = $urgentImportant_list->count();

        
        $allList = collect()
        ->merge( $in_progress)
        ->merge($resubmission_list)
        ->merge($reviewerComments_list);

        $urgentImportant_list = $allList->filter(function ($item) {
            return $item->projectData && $item->projectData->hierarchy_level === 'urgent_important';
        });
        $urgentImportant_ids = $urgentImportant_list->pluck('project_id')->toArray();
        $urgentImportant_list_count = $urgentImportant_list->count();

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
            ->whereIn('project_id', $projectids)
            ->where('type', 'publication_manager')
            ->where('review', 'quickReview')
            ->select('id', 'status', 'project_id', 'assign_user', 'assign_date', 'type', 'comments', 'type_of_article', 'review')
            ->get();
        $quickReview_list_count = $quickReview_list->count();
        // dd($quickReview_list_count);

        return response()->json([
            'data' => [
                'totalproject' => $totalproject,
                'publication_count' => $publication_count,
                'urgentImportantCount' => $urgentImportant_list_count,
                'urgentImport_ids' => $urgentImportant_ids,
                'quickReview' => $quickReview_list_count,
                //'quickReview_list' => $quickReview_list,
                // 'quickReview_list_count'=>$quickReview_list_count,
                'reviewerComments' => $reviewerComments,
                'resubmission' =>  $resubmission,
                'in_progress_count' => $in_progress_count,
                'in_progress' => $in_progress,
                'resubmission_list' =>  $resubmission_list->values()->toArray(),
                'resubmission_count' => $resubmission_count,
                'reviewerComments_list' => $reviewerComments_list,
                'reviewerComments_count' => $reviewerComments_count,
            ]
        ]);
    }
}
