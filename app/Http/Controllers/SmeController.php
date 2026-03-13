<?php

namespace App\Http\Controllers;

use App\Models\EntryDocument;
use App\Models\EntryProcessModel;
use App\Models\PaymentStatusModel;
use App\Models\People;
use App\Models\ProjectAssignDetails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SmeController extends Controller
{
    public function index(Request $request)
    {
        $entries = EntryProcessModel::select('id', 'type_of_work', 'project_id', 'process_status', 'hierarchy_level', 'projectduration', 'created_by')
            ->where('is_deleted', 0)
            ->get();

        $totalproject = $entries->count();
        $projectids = $entries->pluck('id')->toArray();
        $projectcount = [
            'client_review' => 0,
            'completed' => 0,
        ];

        $urgentImportantCount = 0;

        $projectcompleted = EntryProcessModel::where('process_status', 'completed')
            ->where('is_deleted', 0)
            ->get()
            ->unique('project_id')
            ->values();

        // $urgentImportantCount = EntryProcessModel::where('hierarchy_level', 'urgent_important')
        //     ->where('is_deleted', 0)
        //     ->count();

        $urgentImportantCount = ProjectAssignDetails::with(['projectData' => function ($query) {
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
        $urgentImportantCount = count($urgentImportantCount);

        $quickReview = ProjectAssignDetails::with(['projectData' => function ($query) {
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
            ->whereIn('project_id', $entries->pluck('id')->toArray())
            ->where('type', 'publication_manager')
            ->where('review', 'quickReview')
            ->select('id', 'status', 'project_id', 'assign_user', 'assign_date', 'type', 'comments', 'type_of_article', 'review')
            ->get();

        $quickReviewCount = $quickReview->count();

        $statisticanCount = EntryProcessModel::where('process_status', 'client_review')
            ->where('is_deleted', 0)
            ->get()
            ->unique('project_id');

        $projectcount['completed'] = $projectcompleted->count();
        $projectcount['client_review'] = $statisticanCount->count();

        $journalStatusCounts = [
            'pending_author' => 0,
            'rejected' => 0,
            'reviewer_comments' => 0,
            'resubmission' => 0,
            'submit_to_journal' => 0,
        ];

        $peopleIds_pm = People::where('position', '27')
            ->pluck('id')
            ->filter()
            ->values()
            ->toArray();

        //journalStatusList
        $journalStatusList = ProjectAssignDetails::with(['projectData'])
            // ->whereIn('created_by', $peopleIds_pm)
            ->whereIn('project_id', $projectids)
            ->where('type', 'publication_manager')
            ->whereHas('projectData', fn ($q) => $q->where('process_status', '!=', 'completed'))
            // ->whereIn('status', ['pending_author', 'rejected', 'reviewer_comments', 'resubmission', 'submit_to_journal', 'submitted', 'published'])
            ->get()
            ->unique('project_id');

        // Group the journalStatusList by status
        $journalStatusCounts = $journalStatusList->groupBy('status')->map(function ($group) {
            return $group->count();
        })->toArray();

        $publication_list = ProjectAssignDetails::with(['projectData'])
            ->whereIn('created_by', $peopleIds_pm)
            ->whereIn('project_id', $projectids)
            ->where('type', 'publication_manager')
            ->orderBy('created_at', 'desc')
            ->whereIn('status', ['pending_author', 'rejected', 'reviewer_comments', 'resubmission', 'published', 'submitted'])
            ->whereHas('projectData', function ($query) {
                $query->whereNotIn('process_status', ['completed', 'client_review', 'pending_author', 'withdrawal'])
                    ->whereDoesntHave('writerData', function ($wq) {
                        $wq->whereIn('status', ['correction', 'need_support']);
                    })
                    ->whereDoesntHave('reviewerData', function ($wq) {
                        $wq->whereIn('status', ['correction', 'need_support']);
                    })
                    ->whereDoesntHave('statisticanData', function ($sq) {
                        $sq->whereIn('status', ['correction', 'need_support']);
                    })
                    ->whereDoesntHave('tcData', function ($tq) {
                        $tq->where('status', 'correction')
                            ->where('type', 'team_coordinator')
                            ->where('type_sme', 'Publication Manager');
                    });
            })
            ->get()
            ->unique('project_id')
            ->values();

        $publication_count = $publication_list->count();

        $paymentStatusCounts = [
            'advance_pending' => 0,
            'partial_payment_pending' => 0,
            'final_payment_pending' => 0,
            'journal_payment_pending' => 0,
        ];

        $paymentEntries = PaymentStatusModel::select('payment_status', 'id')
            ->whereIn('payment_status', [
                'advance_pending',
                'partial_payment_pending',
                'final_payment_pending',
                'journal_payment_pending',
            ])
            ->get();

        $paymentStatusCounts = $paymentEntries->groupBy('payment_status')->map(function ($group) {
            return $group->count();
        })->toArray();

        $writerList = ProjectAssignDetails::with([
            'documents',
            'projectData' => function ($query) {
                $query->select(
                    'id',
                    'type_of_work',
                    'project_id',
                    'title',
                    'process_status',
                    'hierarchy_level',
                    'client_name',
                    'entry_date'
                );
            },
        ])
            ->whereIn('project_id', $projectids)
            ->where('type', 'writer')
            ->where('status', 'need_support')
            ->whereHas('projectData', function ($query) {
                $query->where('process_status', '!=', 'completed');
            })
            ->orderBy('created_at', 'desc')
            ->select(
                'id',
                'status',
                'project_id',
                'assign_user',
                'assign_date',
                'type',
                'comments',
                'type_of_article',
                'review'
            )
            ->get()
            ->unique('project_id')
            ->values();

        $writerListCount = $writerList->count();

        $select_document = EntryDocument::where('entry_process_model_id', $projectids)
            ->select('select_document')
            ->first();

        $project_req = null;

        if ($select_document && ! empty($select_document->select_document)) {
            $decoded = json_decode($select_document->select_document, true);

            // Convert array to comma-separated string for comparison
            if (is_array($decoded) && count($decoded) > 0) {
                $select_document = implode(',', $decoded);
            } else {
                $select_document = '';
            }
        }

        // Get the select_document array
        $select_document_entry = EntryDocument::where('entry_process_model_id', $projectids)
            ->select('select_document')
            ->first();

        $hasWritingOrThesis = false;

        if ($select_document_entry && ! empty($select_document_entry->select_document)) {
            $decoded = json_decode($select_document_entry->select_document, true);

            if (is_array($decoded) && (
                in_array('writing', $decoded) ||
                in_array('thesis_statistics_with_text', $decoded) ||

                in_array('supporting_thesis_with_ms', $decoded) ||
                in_array('supporting_thesis_without_ms', $decoded) ||
                in_array('supporting_thesis_part1', $decoded) ||
                in_array('supporting_thesis_part2', $decoded) ||
                in_array('thesis_reviewing', $decoded) ||
                in_array('writing,with_statistics', $decoded) ||
                in_array('writing,with_publication', $decoded) ||
                in_array('writing,with_statistics,with_publication', $decoded)

            )) {
                $hasWritingOrThesis = true;
            }
        }

        // $reviewerist = ProjectAssignDetails::with(['documents', 'projectData'])
        //     ->whereIn('project_id', $projectids)
        //     ->where('type', 'reviewer')
        //     ->whereIn('status', ['need_support', 'completed'])
        //     ->where(function ($q) use ($hasWritingOrThesis) {
        //         $q->where('status', 'need_support')
        //             ->orWhereHas('projectData', function ($query) use ($hasWritingOrThesis) {
        //                 $query->whereNotIn('process_status', ['completed', 'client_review', 'pending_author','withdrawal'])
        //                     ->where(function ($q) {
        //                         $q->where('type_of_work', 'thesis')
        //                             ->orWhere(function ($subQ) {
        //                                 $subQ->whereDoesntHave('writerData', function ($wq) {
        //                                     $wq->whereIn('status', ['correction','need_support']);
        //                                 });
        //                             });
        //                     })

        //                     // ->whereDoesntHave('writerData', function ($wq) {
        //                     //         $wq->where('status', 'correction');
        //                     //     })
        //                     ->whereDoesntHave('reviewerData', function ($rq) {
        //                         $rq->where('status', 'correction');
        //                     })
        //                     ->whereHas('reviewerData', function ($rq) {
        //                         $rq->where('status', 'completed');
        //                     })

        //                     // ✅ Conditional statisticanData filter based on writing OR thesis_statistics_with_text
        //                     // ->when($hasWritingOrThesis, function ($q1) {
        //                     //     $q1->whereHas('reviewerData', function ($sq) {
        //                     //         $sq->where('status', 'completed');
        //                     //     });
        //                     // }, function ($q1) {
        //                     //     $q1->whereDoesntHave('reviewerData', function ($sq) {
        //                     //         $sq->where('status', 'completed');
        //                     //     });
        //                     // })
        //                     ->where(function ($q) {
        //                         $q->where('type_of_work', 'thesis')
        //                             ->orWhere(function ($subQ) {
        //                                 $subQ->whereDoesntHave('statisticanData', function ($wq) {
        //                                     $wq->whereIn('status', ['correction','need_support']);
        //                                 });
        //                             });
        //                     })
        //                     // ->whereDoesntHave('statisticanData', function ($sq) {
        //                     //     $sq->where('status', 'correction');
        //                     // })
        //                     // ->whereDoesntHave('tcData', function ($tq) {
        //                     //     $tq->where('status', 'correction')
        //                     //         ->where('type', 'team_coordinator');
        //                     // ->where('type_sme', 'reviewer');
        //                     // })
        //                     ->whereDoesntHave('tcData', function ($tq) {
        //                         $tq->where('type', 'team_coordinator')
        //                             ->whereIn('type_sme', ['writer', 'Publication Manager', 'reviewer']);
        //                     })
        //                     ->where(function ($query) {
        //                         $query->whereDoesntHave('journalData', function ($jq) {
        //                             $jq->whereIn('status', [
        //                                 'pending_author',
        //                                 'rejected',
        //                                 'reviewer_comments',
        //                                 'resubmission',
        //                                 'submit_to_journal',
        //                                 'published',
        //                                 'submitted',
        //                                 'withdrawal'
        //                             ]);
        //                         })
        //                             ->orWhereHas('reviewerData', function ($sq) {
        //                                 $sq->where('status', 'need_support');
        //                             });
        //                     });
        //             });
        //     })
        //     ->orderBy('created_at', 'desc')
        //     ->get()
        //     ->unique('project_id')
        //     ->values();

        $reviewerist = ProjectAssignDetails::with(['documents', 'projectData'])
            ->whereIn('project_id', $projectids)
            ->where('type', 'reviewer')
            ->whereIn('status', ['need_support', 'completed'])
            ->where(function ($q) {
                $q->where('status', 'need_support')
                    ->orWhereHas('projectData', function ($query) {
                        $query->whereNotIn('process_status', ['completed', 'client_review', 'pending_author', 'withdrawal'])
                            ->whereDoesntHave('writerData', function ($wq) {
                                $wq->whereIn('status', ['to_do','on_going','correction', 'need_support']);
                            })
                            ->whereDoesntHave('reviewerData', function ($rq) {
                                $rq->where('status', 'correction');
                            })
                            ->whereHas('reviewerData', function ($rq) {
                                $rq->where('status', 'completed');
                            })
                            ->whereDoesntHave('statisticanData', function ($wq) {
                                $wq->whereIn('status', ['correction', 'need_support']);
                            })
                            ->whereDoesntHave('tcData', function ($tq) {
                                $tq->where('type', 'team_coordinator')
                                    ->whereIn('type_sme', ['writer', 'Publication Manager', 'reviewer','2nd_writer'])
                                    ->orWhereNull('type_sme');
                            })
                            ->where(function ($query) {
                                $query->whereDoesntHave('journalData', function ($jq) {
                                    $jq->whereIn('status', [
                                        'pending_author',
                                        'rejected',
                                        'reviewer_comments',
                                        'resubmission',
                                        'submit_to_journal',
                                        'published',
                                        'submitted',
                                        'withdrawal',
                                    ]);
                                })
                                    ->orWhereHas('reviewerData', function ($sq) {
                                        $sq->where('status', 'need_support');
                                    });
                            });
                    });
            })
            ->orderBy('created_at', 'desc')
            ->get()
            ->unique('project_id')
            ->values();

        $revieweristCount = $reviewerist->count();

        // Get the select_document array
        $select_document_entries_statistican = EntryDocument::where('entry_process_model_id', $projectids)
            ->pluck('select_document')
            ->toArray();

        $hasWritingOrThesiss = false;

        foreach ($select_document_entries_statistican as $doc) {
            $decoded = json_decode($doc, true);
            if (is_array($decoded) && (
                array_intersect(
                    ['sample_size', 'paper_statistics', 'thesis_statistics_without_text'],
                    $decoded
                ) ||
                in_array('supporting_thesis_with_ms', $decoded) ||
                in_array('supporting_thesis_without_ms', $decoded) ||
                in_array('supporting_thesis_part1', $decoded) ||
                in_array('supporting_thesis_part2', $decoded) ||
                in_array('thesis_reviewing', $decoded)
            )) {
                $hasWritingOrThesiss = true;
            }
        }

        // $statisticanlist = ProjectAssignDetails::with(['documents', 'projectData'])
        //     ->whereIn('project_id', $projectids)
        //     ->where('type', 'statistican')
        //     ->orderBy('created_at', 'desc')
        //     ->where(function ($q) use ($hasWritingOrThesiss) {
        //         $q->whereIn('status', ['need_support', 'completed'])
        //             ->whereHas('projectData', function ($query) use ($hasWritingOrThesiss) {
        //                 $query->whereNotIn('process_status', ['completed', 'client_review', 'pending_author','withdrawal'])
        //                     ->where(function ($q) {
        //                         $q->where('type_of_work', 'thesis')
        //                             ->orWhere(function ($subQ) {
        //                                 $subQ->whereDoesntHave('writerData', function ($wq) {
        //                                     $wq->whereIn('status', ['correction','need_support']);
        //                                 });
        //                             });
        //                     })
        //                     // ->where(function ($q) {
        //                     //     $q->where('type_of_work', 'thesis')
        //                     //         ->orWhere(function ($subQ) {
        //                     //             $subQ->whereDoesntHave('tcData', function ($wq) {
        //                     //                 $wq->where('status', 'correction')
        //                     //                     ->where('type', 'team_coordinator');
        //                     //                     // ->where('type_sme','!=','statistican');
        //                     //             });
        //                     //         });
        //                     // })
        //                     ->where(function ($q) {
        //                         $q->where('type_of_work', 'thesis')
        //                             ->orWhere(function ($subQ) {
        //                                 $subQ->whereDoesntHave('reviewerData', function ($wq) {
        //                                     $wq->whereIn('status', ['correction','need_support']);
        //                                 });
        //                             });
        //                     })
        //                     // ->whereDoesntHave('reviewerData', function ($wq) {
        //                     //     $wq->whereIn('status', ['correction', 'to_do', 'on_going']);
        //                     // })
        //                     ->whereDoesntHave('statisticanData', function ($sq) {
        //                         $sq->where('status', 'correction');
        //                     })
        //                     ->whereDoesntHave('tcData', function ($sq) {
        //                         $sq->where('status', 'correction')
        //                             ->where('type', 'team_coordinator');
        //                     })
        //                     ->where(function ($query) {
        //                         $query->whereDoesntHave('journalData', function ($jq) {
        //                             $jq->whereIn('status', [
        //                                 'pending_author',
        //                                 'rejected',
        //                                 'reviewer_comments',
        //                                 'resubmission',
        //                                 'submit_to_journal',
        //                                 'published',
        //                                 'submitted',
        //                                 'withdrawal'
        //                             ]);
        //                         })
        //                             ->orWhereHas('statisticanData', function ($sq) {
        //                                 $sq->where('status', 'need_support');
        //                             });
        //                     });
        //             });
        //     })
        //     ->get()
        //     ->unique('project_id')
        //     ->values();

        $statisticanlist = ProjectAssignDetails::with(['documents', 'projectData'])
            ->whereIn('project_id', $projectids)
            ->where('type', 'statistican')
            ->orderBy('created_at', 'desc')
            ->where(function ($q) {
                $q->whereIn('status', ['need_support', 'completed'])
                    ->whereHas('projectData', function ($query) {
                        $query->whereNotIn('process_status', ['completed', 'client_review', 'pending_author', 'withdrawal'])
                            ->whereDoesntHave('writerData', function ($wq) {
                                $wq->whereIn('status', ['to_do','correction', 'need_support']);
                            })
                            ->whereDoesntHave('reviewerData', function ($wq) {
                                $wq->whereIn('status', ['correction', 'need_support']);
                            })
                            ->whereDoesntHave('statisticanData', function ($sq) {
                                $sq->where('status', 'correction');
                            })
                            ->whereDoesntHave('tcData', function ($sq) {
                                $sq->where('status', 'correction')
                                    ->where('type', 'team_coordinator');
                            })
                            ->where(function ($query) {
                                $query->whereDoesntHave('journalData', function ($jq) {
                                    $jq->whereIn('status', [
                                        'pending_author',
                                        'rejected',
                                        'reviewer_comments',
                                        'resubmission',
                                        'submit_to_journal',
                                        'published',
                                        'submitted',
                                        'withdrawal',
                                    ]);
                                })
                                    ->orWhereHas('statisticanData', function ($sq) {
                                        $sq->where('status', 'need_support');
                                    });
                            });
                    });
            })
            ->get()
            ->unique('project_id')
            ->values();

        $statisticanlistCount = $statisticanlist->count();

        $smelist = ProjectAssignDetails::with(['projectData'])
            ->whereIn('project_id', $projectids)
            ->where('type', 'sme')
            ->where('status', 'need_support')
            ->whereHas('projectData', function ($query) {
                $query->where('process_status', '!=', 'completed')

                    ->whereDoesntHave('tcData', function ($sq) {
                        $sq->where('status', 'correction')
                            ->where('type', 'team_coordinator');
                    });
            })
            ->orderBy('created_at', 'desc')
            ->get()
            ->unique('project_id')
            ->values();

        $smelistCount = $smelist->count();

        $journalList = ProjectAssignDetails::with('projectData.paymentProcess')
            ->where('type', 'publication_manager')
            ->where('status', 'submitted')
            // ->whereIn('created_by', $peopleIds_sme)
            ->count();

        // Combine all lists
        $allLists = collect()
            ->merge($writerList)
            ->merge($reviewerist)
            ->merge($statisticanlist)
            ->merge($publication_list)
            ->merge($smelist);

        // Filter based on 'urgent_important' hierarchy level in projectData
        $urgentImportantProjects = $allLists->filter(function ($item) {
            return optional($item->projectData)->hierarchy_level === 'urgent_important';
        });

        // Get unique project_ids to avoid duplicates
        $uniqueUrgentImportantProjects = $urgentImportantProjects
            ->unique('project_id')
            ->values();

        $uniqueUrgentImportantCount = $uniqueUrgentImportantProjects->count();
        $uniqueUrgentImportantIds = $uniqueUrgentImportantProjects->pluck('project_id')->values();

        $reviewer_comments_count = ProjectAssignDetails::with(['projectDatas:id,project_id,hierarchy_level'])
            // ->whereIn('project_id', $projectids)
            // ->whereIn('created_by', $peopleIds_pm)
            ->orderBy('created_at', 'desc')
            ->where('type', 'publication_manager')
            ->whereIn('status', ['reviewer_comments'])
            ->get()
            ->unique('project_id');
        $responseData = [
            'total_count' => $totalproject,
            'projectcount' => $projectcount,
            'journal_status' => $journalStatusCounts,
            'journalStatusList' => $journalStatusList,
            'paymentStatusCounts' => $paymentStatusCounts,
            'urgentImportantCount' => $uniqueUrgentImportantCount,
            'urgentImportantIds' => $uniqueUrgentImportantIds,
            'quickReviewCount' => $quickReviewCount,
            'reviewerist' => $reviewerist,
            'statisticanlist' => $statisticanlist,
            'smelist' => $smelist,
            'statisticanlistCount' => $statisticanlistCount,
            'revieweristCount' => $revieweristCount,
            'smelistCount' => $smelistCount,
            'writerList' => $writerList,
            'writerListCount' => $writerListCount,

            'publication_list' => $publication_list,
            'publication_count' => $publication_count,

            'journalList' => $journalList,
            'reviewer_comments_count' => $reviewer_comments_count->count(),

        ];

        return response()->json($responseData);
    }
}
