<?php

namespace App\Http\Controllers;

use App\Models\EntryProcessModel;
use App\Models\ProjectAssignDetails;
use App\Models\ProjectLogs;
use App\Models\ProjectViewStatus;
use Illuminate\Http\Request;

class OperationalReportsController extends Controller
{
    // public function getProjectDuration(Request $request)
    // {
    //     // Fetching the list of projects
    //     $projectlist = EntryProcessModel::with(['reviewerData'])
    //         ->select('id', 'project_id', 'process_status')
    //         ->where('is_deleted', '0')
    //         ->get();

    //     $projects_duration_data = [];

    //     foreach ($projectlist as $project) {
    //         $project_id = $project->id;
    //         $projectid_unique = $project->project_id;

    //         $reviewer_id = ProjectAssignDetails::where('project_id', $project_id)->where('type', '=', 'reviewer')->pluck('assign_user')->toArray();
    //         $writer_id = ProjectAssignDetails::where('project_id', $project_id)->where('type', '=', 'writer')->pluck('assign_user')->toArray();
    //         $statistican_id = ProjectAssignDetails::where('project_id', $project_id)->where('type', '=', 'statistican')->pluck('assign_user')->toArray();

    //         $projects_duration_data[$projectid_unique] = [
    //             'project_working_time' => [],
    //             'project_waiting_time' => [],
    //             'project_work_duration' => [],
    //             'project_completion_duration' => []
    //         ];

    //         if ($project->process_status == 'in_progress') {
    //             $project_start = projectviewstatus::where('project_id', $project_id)
    //                 ->where('created_by', '14')
    //                 ->where('project_status', 'in_progress')
    //                 ->select('created_date', 'project_id')
    //                 ->latest()
    //                 ->first();

    //             $reviewer_statuses = ProjectLogs::with('userData')
    //                 ->where('project_id', $project_id)
    //                 ->whereIn('employee_id', $reviewer_id)
    //                 ->where('status', 'completed')
    //                 ->select('created_date', 'employee_id')
    //                 ->get();

    //             $reviewer_working_times = [];

    //             foreach ($reviewer_statuses as $status) {
    //                 // $start_time = \Carbon\Carbon::parse($project_start->created_date);
    //                 // $end_time = \Carbon\Carbon::parse($status->created_date);
    //                 // $hours = $start_time->diffInHours($end_time);

    //                 $start_time = strtotime($project_start->created_date);
    //                 $end_time = strtotime($status->created_date);
    //                 $time_diff = $end_time - $start_time;

    //                 $user_name = $status->userData?->employee_name;
    //                 if ($user_name) {
    //                     $reviewer_working_times[$user_name] = $this->formatSecondsToHoursMinutes($time_diff);
    //                 }
    //             }

    //             if (!empty($reviewer_working_times)) {
    //                 $projects_duration_data[$projectid_unique]['project_working_time'] = $reviewer_working_times;
    //             }
    //         }

    //         if ($project->process_status == 'in_progress' || $project->process_status == 'client_review' || $project->process_status == 'completed') {
    //             //project work duration

    //             $project_start = projectviewstatus::where('project_id', $project_id)
    //                 ->whereIn('created_by', ['14', '9'])
    //                 ->where('project_status', 'in_progress')
    //                 ->select('created_date', 'project_id')
    //                 ->latest()
    //                 ->first();

    //             $project_end = projectviewstatus::where('project_id', $project_id)
    //                 ->where('project_status', 'client_review')
    //                 ->select('created_date', 'project_id')
    //                 ->latest()
    //                 ->first();

    //             if ($project_end) {
    //                 if ($project_start) {
    //                     // $start_time = \Carbon\Carbon::parse($project_start->created_date);
    //                     // $end_time = \Carbon\Carbon::parse($project_end->created_date);
    //                     // $hours1 = $start_time->diffInHours($end_time);
    //                     // $start_time = \Carbon\Carbon::parse($project_start->created_date);
    //                     // $end_time = \Carbon\Carbon::parse($project_end->created_date);
    //                     // $seconds_diff = $end_time->diffInSeconds($start_time);

    //                     $start_time = strtotime($project_start->created_date);
    //                     $end_time = strtotime($project_end->created_date);
    //                     $seconds_diff = $end_time - $start_time;

    //                     if ($project_start && $project_end) {
    //                         $projects_duration_data[$projectid_unique]['project_work_duration'] = $this->formatSecondsToHoursMinutes($seconds_diff);
    //                     }
    //                 } else {
    //                     $projects_duration_data[$projectid_unique]['project_work_duration'] = [];
    //                 }
    //             } else {
    //                 $projects_duration_data[$projectid_unique]['project_work_duration'] = [];
    //             }

    //             $project_end1 = projectviewstatus::where('project_id', $project_id)
    //                 ->where('project_status', 'completed')
    //                 ->select('created_date', 'project_id')
    //                 ->latest()
    //                 ->first();

    //             if ($project_end1) {
    //                 if ($project_start) {
    //                     // $start_time = \Carbon\Carbon::parse($project_start->created_date);
    //                     // $end_time = \Carbon\Carbon::parse($project_end1->created_date);
    //                     // $seconds_diff = $end_time->diffInSeconds($start_time);

    //                     $start_time = strtotime($project_start->created_date);
    //                     $end_time = strtotime($project_end1->created_date);
    //                     $seconds_diff = $end_time - $start_time;

    //                     $projects_duration_data[$projectid_unique]['project_completion_duration'] = $this->formatSecondsToHoursMinutes($seconds_diff);
    //                 } else {
    //                     $projects_duration_data[$projectid_unique]['project_completion_duration'] = [];
    //                 }
    //             } else {
    //                 $projects_duration_data[$projectid_unique]['project_completion_duration'] = [];
    //             }
    //         }

    //         //project waiting time
    //         $all_employee_ids = array_merge($writer_id, $reviewer_id, $statistican_id);

    //         $project_statuses = ProjectLogs::with('userData')->where('project_id', $project_id)
    //             ->whereIn('employee_id', $all_employee_ids)
    //             ->select('created_date', 'employee_id', 'status', 'project_id')
    //             ->orderBy('id', 'desc')
    //             ->get();

    //         foreach ($project_statuses as $project_status) {
    //             if ($project_status->status == 'to_do') {
    //                 $current_time = date('Y-m-d H:i:s');
    //                 // $created_date = \Carbon\Carbon::parse($project_status->created_date);
    //                 // $time_diff = $created_date->diffInHours($current_time);

    //                 // $current_time = strtotime($current_time);
    //                 // $created_date = strtotime($project_status->created_date);
    //                 // // $time_diff_seconds = $created_date->diffInSeconds($current_time); // Get total seconds
    //                 // $time_diff_seconds = $end_time - $start_time;

    //                 $start_time = strtotime($current_time);
    //                 $end_time = strtotime($project_status->created_date);
    //                 $time_diff_seconds = $end_time - $start_time;

    //                 $user_name = $project_status->userData?->employee_name;
    //                 if ($user_name) {
    //                     $project_working_times[$user_name] = $this->formatSecondsToHoursMinutes($time_diff_seconds);
    //                 }
    //                 // Store the result in the projects_duration_data
    //                 $projects_duration_data[$projectid_unique]['project_waiting_time'] = $project_working_times;
    //             }

    //             if ($project_status->status == 'on_going') {
    //                 $previous_to_do_status = ProjectLogs::where('project_id', $project_id)
    //                     ->where('employee_id', $project_status->employee_id)
    //                     ->where('status', 'to_do')
    //                     // ->where('created_date', '<', $project_status->created_date) // Ensure we are looking at an earlier 'to_do' status
    //                     ->orderBy('id', 'desc')
    //                     ->first();

    //                 if ($previous_to_do_status) {
    //                     // $previous_to_do_created_date = \Carbon\Carbon::parse($previous_to_do_status->created_date);
    //                     // $current_to_ongoing_created_date = \Carbon\Carbon::parse($project_status->created_date);
    //                     // $time_diff = $previous_to_do_created_date->diffInHours($current_to_ongoing_created_date);

    //                     $start_time = strtotime($previous_to_do_status->created_date);
    //                     $end_time = strtotime($project_status->created_date);
    //                     $time_diff = $end_time - $start_time;

    //                     $user_name = $project_status->userData?->employee_name;
    //                     if ($user_name) {
    //                         $project_working_times[$user_name] = $time_diff;
    //                     }

    //                     // Store the result in the projects_duration_data
    //                     $projects_duration_data[$projectid_unique]['project_waiting_time'] = $project_working_times;
    //                 }
    //             }
    //         }
    //     }

    //     return response()->json([
    //         'status' => 'success',
    //         'data' => $projects_duration_data,
    //     ]);
    // }

    // public function getProjectDuration(Request $request)
    // {
    //     $projectlist = EntryProcessModel::with('reviewerData')
    //         ->select('id', 'project_id', 'process_status')
    //         ->where('is_deleted', '0')
    //         // ->where('id', '40')
    //         ->get();

    //     $projects_duration_data = [];

    //     foreach ($projectlist as $project) {
    //         $project_id = $project->id;
    //         $projectid_unique = $project->project_id;

    //         $reviewer_ids = ProjectAssignDetails::where('project_id', $project_id)->where('type', 'reviewer')->pluck('assign_user')->toArray();
    //         $writer_ids = ProjectAssignDetails::where('project_id', $project_id)->where('type', 'writer')->pluck('assign_user')->toArray();
    //         $statistician_ids = ProjectAssignDetails::where('project_id', $project_id)->where('type', 'statistican')->pluck('assign_user')->toArray();
    //         $all_employee_ids = array_merge($writer_ids, $reviewer_ids, $statistician_ids);

    //         $projects_duration_data[$projectid_unique] = [
    //             'project_working_time' => [],
    //             'project_waiting_time' => [],
    //             'project_work_duration' => null,
    //             'project_completion_duration' => null,
    //         ];
    //         // -----------------------
    //         // Reviewer Working Time
    //         // -----------------------
    //         // if ($project->process_status === 'in_progress') {
    //             $project_start = projectviewstatus::where('project_id', $project_id)
    //                 ->where('created_by', '14')
    //                 ->where('project_status', 'in_progress')
    //                 ->latest('created_date')
    //                 ->first();

    //             if ($project_start) {
    //                 $reviewer_statuses = ProjectLogs::with('userData')
    //                     ->where('project_id', $project_id)
    //                     ->whereIn('employee_id', $reviewer_ids)
    //                     ->where('status', 'completed')
    //                     ->get();

    //                 foreach ($reviewer_statuses as $status) {
    //                     $start = \Carbon\Carbon::parse($project_start->created_date);
    //                     $end = \Carbon\Carbon::parse($status->created_date);
    //                     $duration = $start->diffInSeconds($end);

    //                     $user_name = $status->userData?->employee_name;
    //                     if ($user_name) {
    //                         $projects_duration_data[$projectid_unique]['project_working_time'][$user_name] = $this->formatSecondsToUnits($duration);
    //                     }
    //                 }
    //             }
    //         // }

    //         // -----------------------
    //         // Work & Completion Duration
    //         // -----------------------
    //         if (in_array($project->process_status, ['in_progress', 'client_review', 'completed'])) {
    //             $project_start = projectviewstatus::where('project_id', $project_id)
    //                 ->whereIn('created_by', ['14', '9'])
    //                 ->where('project_status', 'in_progress')
    //                 ->latest('created_date')
    //                 ->first();

    //             if ($project_start) {
    //                 $start_time = \Carbon\Carbon::parse($project_start->created_date);

    //                 // Work Duration
    //                 $work_end = projectviewstatus::where('project_id', $project_id)
    //                     ->where('project_status', 'client_review')
    //                     ->latest('created_date')
    //                     ->first();

    //                 if ($work_end) {
    //                     $end_time = \Carbon\Carbon::parse($work_end->created_date);
    //                     $duration = $start_time->diffInSeconds($end_time);
    //                     $projects_duration_data[$projectid_unique]['project_work_duration'] = $this->formatSecondsToUnits($duration);
    //                 }

    //                 // Completion Duration
    //                 $completion_end = projectviewstatus::where('project_id', $project_id)
    //                     ->where('project_status', 'completed')
    //                     ->latest('created_date')
    //                     ->first();

    //                 if ($completion_end) {
    //                     $end_time = \Carbon\Carbon::parse($completion_end->created_date);
    //                     $duration = $start_time->diffInSeconds($end_time);
    //                     $projects_duration_data[$projectid_unique]['project_completion_duration'] = $this->formatSecondsToUnits($duration);
    //                 }
    //             }
    //         }

    //         // -----------------------
    //         // Waiting Time
    //         // -----------------------
    //         $project_logs = ProjectLogs::with('userData')
    //             ->where('project_id', $project_id)
    //             ->whereIn('employee_id', $all_employee_ids)
    //             ->orderBy('id', 'desc')
    //             ->get();

    //         $waiting_times = [];

    //         foreach ($project_logs as $log) {
    //             $user_name = $log->userData?->employee_name;

    //             if (!$user_name) continue;

    //             if ($log->status === 'to_do') {
    //                 $start = \Carbon\Carbon::parse($log->created_date);
    //                 $end = \Carbon\Carbon::now();
    //                 $duration = $start->diffInSeconds($end);
    //                 $waiting_times[$user_name] = $this->formatSecondsToUnits($duration);
    //             }

    //             if ($log->status === 'on_going') {
    //                 $prev_to_do = ProjectLogs::where('project_id', $project_id)
    //                     ->where('employee_id', $log->employee_id)
    //                     ->where('status', 'to_do')
    //                     ->orderBy('id', 'desc')
    //                     ->first();

    //                 if ($prev_to_do) {
    //                     $start = \Carbon\Carbon::parse($prev_to_do->created_date);
    //                     $end = \Carbon\Carbon::parse($log->created_date);
    //                     $duration = $start->diffInSeconds($end);
    //                     $waiting_times[$user_name] = $this->formatSecondsToUnits($duration);
    //                 }
    //             }
    //         }

    //         if (!empty($waiting_times)) {
    //             $projects_duration_data[$projectid_unique]['project_waiting_time'] = $waiting_times;
    //         }
    //     }

    //     return response()->json([
    //         'status' => 'success',
    //         'data' => $projects_duration_data,
    //     ]);
    // }

    public function getProjectDuration(Request $request)
    {
        $projectlist = EntryProcessModel::with('reviewerData')
            ->select('id', 'project_id', 'process_status')
            ->where('is_deleted', '0')
            ->get();

        $projects_duration_data = [];

        foreach ($projectlist as $project) {
            $project_id = $project->id;
            $projectid_unique = $project->project_id;

            $reviewer_ids = ProjectAssignDetails::where('project_id', $project_id)
                ->where('type', 'reviewer')
                ->pluck('assign_user')
                ->toArray();

            $writer_ids = ProjectAssignDetails::where('project_id', $project_id)
                ->where('type', 'writer')
                ->pluck('assign_user')
                ->toArray();

            $statistician_ids = ProjectAssignDetails::where('project_id', $project_id)
                ->where('type', 'statistican')
                ->pluck('assign_user')
                ->toArray();

            $all_employee_ids = array_merge($writer_ids, $reviewer_ids, $statistician_ids);

            $projects_duration_data[$projectid_unique] = [
                'project_working_time' => [],
                'project_waiting_time' => [],
                'project_work_duration' => null,
                'project_completion_duration' => null,
            ];

            /* -----------------------------------
           Reviewer Working Time
        ----------------------------------- */
            $project_start = projectviewstatus::where('project_id', $project_id)
                ->where('created_by', '85')
                ->where('project_status', 'in_progress')
                ->latest('created_date')
                ->first();

            if ($project_start) {
                $reviewer_statuses = ProjectLogs::with('userData')
                    ->where('project_id', $project_id)
                    ->whereIn('employee_id', $reviewer_ids)
                    ->where('status', 'completed')
                    ->get();

                foreach ($reviewer_statuses as $status) {
                    $start = \Carbon\Carbon::parse($project_start->created_date);
                    $end = \Carbon\Carbon::parse($status->created_date);
                    $duration = $start->diffInSeconds($end);

                    $user_name = $status->userData?->employee_name;

                    if ($user_name) {
                        $projects_duration_data[$projectid_unique]['project_working_time'][$user_name] =
                            $this->formatSecondsToUnits($duration);
                    }
                }
            }

            /* -----------------------------------
           Work & Completion Duration
        ----------------------------------- */
            if (in_array($project->process_status, ['in_progress', 'client_review', 'completed'])) {

                $project_start = projectviewstatus::where('project_id', $project_id)
                    ->whereIn('created_by', ['85', '9'])
                    ->where('project_status', 'in_progress')
                    ->latest('created_date')
                    ->first();

                if ($project_start) {
                    $start_time = \Carbon\Carbon::parse($project_start->created_date);

                    // Work Duration
                    $work_end = projectviewstatus::where('project_id', $project_id)
                        ->where('project_status', 'client_review')
                        ->latest('created_date')
                        ->first();

                    if ($work_end) {
                        $end_time = \Carbon\Carbon::parse($work_end->created_date);
                        $duration = $start_time->diffInSeconds($end_time);

                        $projects_duration_data[$projectid_unique]['project_work_duration'] =
                            $this->formatSecondsToUnits($duration);
                    }

                    // Completion Duration
                    $completion_end = projectviewstatus::where('project_id', $project_id)
                        ->where('project_status', 'completed')
                        ->latest('created_date')
                        ->first();

                    if ($completion_end) {
                        $end_time = \Carbon\Carbon::parse($completion_end->created_date);
                        $duration = $start_time->diffInSeconds($end_time);

                        $projects_duration_data[$projectid_unique]['project_completion_duration'] =
                            $this->formatSecondsToUnits($duration);
                    }
                }
            }

            /* -----------------------------------
           Waiting Time
        ----------------------------------- */
            $project_logs = ProjectLogs::with('userData')
                ->where('project_id', $project_id)
                ->whereIn('employee_id', $all_employee_ids)
                ->orderBy('id', 'desc')
                ->get();

            $waiting_times = [];

            foreach ($project_logs as $log) {
                $user_name = $log->userData?->employee_name;
                if (! $user_name) {
                    continue;
                }

                // If still in "to_do"
                if ($log->status === 'to_do') {
                    $start = \Carbon\Carbon::parse($log->created_date);
                    $end = \Carbon\Carbon::now();
                    $duration = $start->diffInSeconds($end);

                    $waiting_times[$user_name] = $this->formatSecondsToUnits($duration);
                }

                // If moved from "to_do" → "on_going"
                if ($log->status === 'on_going') {
                    $prev_to_do = ProjectLogs::where('project_id', $project_id)
                        ->where('employee_id', $log->employee_id)
                        ->where('status', 'to_do')
                        ->orderBy('id', 'desc')
                        ->first();

                    if ($prev_to_do) {
                        $start = \Carbon\Carbon::parse($prev_to_do->created_date);
                        $end = \Carbon\Carbon::parse($log->created_date);
                        $duration = $start->diffInSeconds($end);

                        $waiting_times[$user_name] = $this->formatSecondsToUnits($duration);
                    }
                }
            }

            if (! empty($waiting_times)) {
                $projects_duration_data[$projectid_unique]['project_waiting_time'] = $waiting_times;
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => $projects_duration_data,
        ]);
    }

    public function getOperationalDuration(Request $request)
    {
        // Fetching the list of projects
        $projectlist = EntryProcessModel::with(['reviewerData'])
            ->select('id', 'project_id', 'process_status')
            ->where('is_deleted', '0')
            // ->where('id', '17')
            ->get();

        $projects_duration_data = [];

        foreach ($projectlist as $project) {
            $project_id = $project->id;
            $projectid_unique = $project->project_id;

            $reviewer_id = ProjectAssignDetails::where('project_id', $project_id)->where('type', '=', 'reviewer')->pluck('assign_user')->toArray();

            $statistican_id = ProjectAssignDetails::where('project_id', $project_id)->where('type', '=', 'statistican')->pluck('assign_user')->toArray();

            $writer_id = ProjectAssignDetails::where('project_id', $project_id)->where('type', '=', 'writer')->pluck('assign_user')->toArray();

            $projects_duration_data[$projectid_unique] = [
                'team_coordinator' => [],
                'writer' => [],
                'reviewer' => [],
                'statistican' => [],
                'sme' => [],
                'project_work_duration' => [],
            ];

            // if ($project->process_status == 'in_progress') {
            $project_start = projectviewstatus::where('project_id', $project_id)
                ->whereIn('created_by', ['85', '9'])
                ->where('project_status', 'in_progress')
                ->select('created_date', 'project_id')
                ->latest()
                ->first();

            $reviewer_statuses = ProjectLogs::with('userData')
                ->where('project_id', $project_id)
                ->whereIn('employee_id', $reviewer_id)
                ->where('status', 'completed')
                ->select('created_date', 'employee_id')
                ->get();

            $reviewer_working_times = [];

            if ($project_start && $reviewer_statuses->count() > 0) {

                foreach ($reviewer_statuses as $status) {

                    // Also check created_date exists on reviewer log
                    if (! $status->created_date) {
                        continue;
                    }

                    $start_time = \Carbon\Carbon::parse($project_start->created_date);
                    $end_time = \Carbon\Carbon::parse($status->created_date);

                    $hours = $start_time->diffInHours($end_time);

                    $user_name = $status->userData?->employee_name;

                    if ($user_name) {
                        $reviewer_working_times[$user_name] = $hours;
                    }
                }
            }

            if (! empty($reviewer_working_times)) {
                $projects_duration_data[$projectid_unique]['team_coordinator'] = $reviewer_working_times;
            }
            // }

            //writer

            $writer_details = ProjectLogs::with('userData')
                ->where('project_id', $project_id)
                ->where('status_type', '=', 'writer')
                ->whereIn('employee_id', $writer_id)
                ->whereIn('status', ['on_going', 'correction', 'completed', 'plag_correction', 'need_support'])
                ->get();

            $writer_details = $writer_details->sortBy('created_date');

            $writer_working_times = [];
            foreach ($writer_details as $log) {
                $employee_id = $log->employee_id;
                $user_name = $log->userData?->employee_name;

                if (! isset($writer_working_times[$user_name])) {
                    $writer_working_times[$user_name] = 0; // Initialize with 0 seconds
                }

                // Calculate time for need_support status
                if ($log->status === 'need_support') {
                    $preview_details = ProjectLogs::with('userData')
                        ->where('id', $log->assing_preview_id)
                        ->first();

                    if ($preview_details) {
                        $start_time = strtotime($preview_details->created_date);
                        $end_time = strtotime($log->created_date);
                        $time_diff = $end_time - $start_time;

                        // Add to the writer's total time only if positive
                        if ($time_diff > 0) {
                            $writer_working_times[$user_name] += $time_diff;
                        }
                    }
                }

                // Calculate time for completed status
                if ($log->status === 'completed') {
                    $preview_details = ProjectLogs::with('userData')
                        ->where('id', $log->assing_preview_id)
                        ->first();

                    if ($preview_details) {
                        $start_time = strtotime($preview_details->created_date);
                        $end_time = strtotime($log->created_date);
                        $time_diff = $end_time - $start_time;

                        // Add to the writer's total time only if positive
                        if ($time_diff > 0) {
                            $writer_working_times[$user_name] += $time_diff;
                        }
                    }
                }

                //revert
                if ($log->status === 'revert') {
                    $preview_details = ProjectLogs::with('userData')
                        ->where('id', $log->assing_preview_id)
                        ->first();

                    if ($preview_details) {
                        $start_time = strtotime($preview_details->created_date);
                        $end_time = strtotime($log->created_date);
                        $time_diff = $end_time - $start_time;

                        // Add to the writer's total time only if positive
                        if ($time_diff > 0) {
                            $writer_working_times[$user_name] += $time_diff;
                        }
                    }
                }

                //plag_correction

                if ($log->status === 'plag_correction') {
                    $preview_details = ProjectLogs::with('userData')
                        ->where('id', $log->assing_preview_id)
                        ->where('status', '!=', 'revert')
                        ->first();

                    if ($preview_details) {
                        $start_time = strtotime($preview_details->created_date);
                        $end_time = strtotime($log->created_date);
                        $time_diff = $end_time - $start_time;

                        // Add to the writer's total time only if positive
                        if ($time_diff > 0) {
                            $writer_working_times[$user_name] += $time_diff;
                        }
                    }
                }
            }

            // Format the working times and prepare final output
            foreach ($writer_working_times as $name => $seconds) {
                $writer_working_times[$name] = $this->formatSecondsToUnits($seconds);
            }

            // Store in projects duration data
            $projects_duration_data[$projectid_unique]['writer'] = $writer_working_times;

            //reviewer

            $reviewer_details = ProjectLogs::with('userData')
                ->where('project_id', $project_id)
                ->where('status_type', '=', 'reviewer')
                ->whereIn('employee_id', $reviewer_id)
                ->whereIn('status', ['on_going', 'correction', 'completed', 'plag_correction', 'need_support'])
                ->get();

            $reviewer_details = $reviewer_details->sortBy('created_date');

            $reviewer_working_times = [];

            foreach ($reviewer_details as $log) {
                $employee_id = $log->employee_id;
                $user_name = $log->userData?->employee_name;

                if (! isset($reviewer_working_times[$user_name])) {
                    $reviewer_working_times[$user_name] = 0; // Initialize with 0 seconds
                }

                // Calculate time for need_support status
                if ($log->status === 'need_support') {
                    $preview_details = ProjectLogs::with('userData')
                        ->where('id', $log->assing_preview_id)
                        ->first();

                    if ($preview_details) {
                        $start_time = strtotime($preview_details->created_date);
                        $end_time = strtotime($log->created_date);
                        $time_diff = $end_time - $start_time;

                        // Add to the writer's total time only if positive
                        if ($time_diff > 0) {
                            $reviewer_working_times[$user_name] += $time_diff;
                        }
                    }
                }

                // Calculate time for completed status
                if ($log->status === 'completed') {
                    $preview_details = ProjectLogs::with('userData')
                        ->where('id', $log->assing_preview_id)
                        ->first();

                    if ($preview_details) {
                        $start_time = strtotime($preview_details->created_date);
                        $end_time = strtotime($log->created_date);
                        $time_diff = $end_time - $start_time;

                        // Add to the writer's total time only if positive
                        if ($time_diff > 0) {
                            $reviewer_working_times[$user_name] += $time_diff;
                        }
                    }
                }

                //revert
                if ($log->status === 'revert') {
                    $preview_details = ProjectLogs::with('userData')
                        ->where('id', $log->assing_preview_id)
                        ->first();

                    if ($preview_details) {
                        $start_time = strtotime($preview_details->created_date);
                        $end_time = strtotime($log->created_date);
                        $time_diff = $end_time - $start_time;

                        // Add to the writer's total time only if positive
                        if ($time_diff > 0) {
                            $reviewer_working_times[$user_name] += $time_diff;
                        }
                    }
                }

                if ($log->status === 'plag_correction') {
                    $preview_details = ProjectLogs::with('userData')
                        ->where(function ($query) use ($log) {
                            $query->where('id', $log->assing_preview_id)
                                ->orWhere('status', '!=', 'revert');
                        })
                        ->where('id', $log->assing_preview_id)
                        ->first();

                    if ($preview_details) {
                        $start_time = strtotime($preview_details->created_date);
                        $end_time = strtotime($log->created_date);
                        $time_diff = $end_time - $start_time;

                        // Add to the writer's total time only if positive
                        if ($time_diff > 0) {
                            $reviewer_working_times[$user_name] += $time_diff;
                        }
                    }
                }
            }

            // Format the working times and prepare final output
            foreach ($reviewer_working_times as $name => $seconds) {
                $reviewer_working_times[$name] = $this->formatSecondsToUnits($seconds);
            }

            // Store in projects duration data
            $projects_duration_data[$projectid_unique]['reviewer'] = $reviewer_working_times;

            //statistican

            $statistican_details = ProjectLogs::with('userData')
                ->where('project_id', $project_id)
                ->where('status_type', '=', 'statistican')
                ->whereIn('employee_id', $statistican_id)
                ->whereIn('status', ['on_going', 'correction', 'completed', 'need_support'])
                ->get();

            $statistican_details = $statistican_details->sortBy('created_date');

            $statistican_working_times = [];

            foreach ($statistican_details as $log) {
                $employee_id = $log->employee_id;
                $user_name = $log->userData?->employee_name;

                if (! isset($statistican_working_times[$user_name])) {
                    $statistican_working_times[$user_name] = 0; // Initialize with 0 seconds
                }

                // Calculate time for need_support status
                if ($log->status === 'need_support') {
                    $preview_details = ProjectLogs::with('userData')
                        ->where('id', $log->assing_preview_id)
                        ->first();

                    if ($preview_details) {
                        $start_time = strtotime($preview_details->created_date);
                        $end_time = strtotime($log->created_date);
                        $time_diff = $end_time - $start_time;

                        // Add to the writer's total time only if positive
                        if ($time_diff > 0) {
                            $statistican_working_times[$user_name] += $time_diff;
                        }
                    }
                }

                // Calculate time for completed status
                if ($log->status === 'completed') {
                    $preview_details = ProjectLogs::with('userData')
                        ->where('id', $log->assing_preview_id)
                        ->first();

                    if ($preview_details) {
                        $start_time = strtotime($preview_details->created_date);
                        $end_time = strtotime($log->created_date);
                        $time_diff = $end_time - $start_time;

                        // Add to the writer's total time only if positive
                        if ($time_diff > 0) {
                            $statistican_working_times[$user_name] += $time_diff;
                        }
                    }
                }

                if ($log->status === 'revert') {
                    $preview_details = ProjectLogs::with('userData')
                        ->where('id', $log->assing_preview_id)
                        ->first();

                    if ($preview_details) {
                        $start_time = strtotime($preview_details->created_date);
                        $end_time = strtotime($log->created_date);
                        $time_diff = $end_time - $start_time;

                        // Add to the writer's total time only if positive
                        if ($time_diff > 0) {
                            $reviewer_working_times[$user_name] += $time_diff;
                        }
                    }
                }
            }

            // Format the working times and prepare final output
            foreach ($statistican_working_times as $name => $seconds) {
                $statistican_working_times[$name] = $this->formatSecondsToUnits($seconds);
            }

            // Store in projects duration data
            $projects_duration_data[$projectid_unique]['statistican'] = $statistican_working_times;

            //sme status

            $sme_details = ProjectLogs::with('userData')
                ->where('project_id', $project_id)
                ->where('created_by', '86')
                ->whereIn('status', ['correction', 'need_support'])
                ->get();

            $sme_details = $sme_details->sortBy('created_date');
            $sme_working_times = [];
            foreach ($sme_details as $log) {
                $employee_id = $log->employee_id;
                $user_name = $log->userData?->employee_name;

                if (! isset($sme_working_times[$user_name])) {
                    $sme_working_times[$user_name] = 0; // Initialize with 0 seconds
                }

                // Calculate time for need_support status
                if ($log->status === 'correction') {
                    $preview_details = ProjectLogs::with('userData')
                        ->where('id', $log->assing_preview_id)
                        ->first();

                    if ($preview_details) {
                        $start_time = strtotime($preview_details->created_date);
                        $end_time = strtotime($log->created_date);
                        $time_diff = $end_time - $start_time;

                        // Add to the writer's total time only if positive
                        if ($time_diff > 0) {
                            $sme_working_times[$user_name] += $time_diff;
                        }
                    }
                }
            }

            // Format the working times and prepare final output
            foreach ($sme_working_times as $name => $seconds) {
                $sme_working_times[$name] = $this->formatSecondsToUnits($seconds);
            }

            // Store in projects duration data
            $projects_duration_data[$projectid_unique]['sme'] = $sme_working_times;

            //total project duration
            $project_start = projectviewstatus::where('project_id', $project_id)
                ->where('project_status', 'in_progress')
                ->select('created_date', 'project_id')
                ->latest()
                ->first();

            $project_end = projectviewstatus::where('project_id', $project_id)
                ->where('project_status', 'completed')
                ->select('created_date', 'project_id')
                ->latest()
                ->first();

            if ($project_start) {
                $start_time = strtotime($project_start->created_date);
                // $end_time = strtotime($project_end->created_date);
                $end_time = $project_end ? strtotime($project_end->created_date) : time();

                $time_diff = $end_time - $start_time; // difference in seconds

                $project_work_duration = $this->formatSecondsToUnits($time_diff);

                $projects_duration_data[$projectid_unique]['project_work_duration'] = $project_work_duration;
            } else {
                $projects_duration_data[$projectid_unique]['project_work_duration'] = [];
            }

            //publication manager
            $project_start = ProjectLogs::where('project_id', $project_id)
                ->where('status_type', 'publication_manager')
                ->where('status', 'submit_to_journal')
                ->select('created_date', 'project_id', 'status')
                ->latest()
                ->first();

            if ($project_start) {

                $project_end = ProjectLogs::with('userData')->where('project_id', $project_id)
                    ->where('status_type', 'publication_manager')
                    ->whereIn('status', ['published', 'rejected', 'submitted'])
                    ->where('id', $project_start->assing_preview_id)
                    ->select('created_date')
                    ->latest()
                    ->first();

                $start_time = strtotime($project_start->created_date);
                // $end_time = strtotime($project_end->created_date);
                $end_time = $project_end ? strtotime($project_end->created_date) : time();

                $time_diff = $end_time - $start_time; // difference in seconds

                $project_work_duration = $this->formatSecondsToUnits($time_diff);

                $projects_duration_data[$projectid_unique]['publication_manager'] = $project_work_duration;
            } else {
                $projects_duration_data[$projectid_unique]['publication_manager'] = [];
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => $projects_duration_data,
        ]);
    }

    // function formatSecondsToHoursMinutes($seconds)
    // {
    //     $hours = floor($seconds / 3600);
    //     $minutes = floor(($seconds % 3600) / 60);
    //     return "{$hours} hr {$minutes} min";
    // }

    // public function formatSecondsToHoursMinutes($seconds)
    // {
    //     // Step 1: Apply the formula V = M / 60
    //     $V = $seconds / 60;

    //     // Step 2: Extract hours and minutes
    //     $hours = floor($V);
    //     $remainingMinutes = round(($V - $hours) * 60);

    //     return "{$hours} hr {$remainingMinutes} min";
    // }

    public function formatSecondsToUnits($seconds, $precision = 2)
{
    // Convert seconds → minutes
    $minutes = $seconds / 60;

    // Convert minutes → units
    $units = $minutes / 60;

    // Round to 2 decimal places (or as needed)
    return round($units, $precision);
}

}
