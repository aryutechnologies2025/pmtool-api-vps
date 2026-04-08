<?php

namespace App\Http\Controllers;

use App\Models\EntryProcessModel;
use App\Models\People;
use App\Models\PaymentStatusModel;
use App\Models\ProjectAssignDetails;
use App\Models\EmployeePaymentDetails;
use App\Models\InvoiceDetails;
use App\Models\User;
use App\Models\Setting;
use Carbon\Carbon;
use Faker\Provider\ar_EG\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\InvoicePayment;
use Dotenv\Parser\Entry;;

class AccountantController extends Controller
{
    public function payment_task(Request $request)
    {
        $type = strval($request->input('type'));

        // Fetch user and people IDs
        $peopleIds_pm = People::where('position', 28)->pluck('id')->filter()->values()->toArray();
        $peopleIds_sme = People::where('position', 27)->pluck('id')->filter()->values()->toArray();

        $employees = User::with('createdByUser')->select('id', 'employee_name', 'position', 'created_by')->get();
        $employeeIds = $employees->pluck('id');

        // Ensure 'created_at' is selected
        $paymentStatus = EntryProcessModel::with('paymentProcess')
            ->whereIn('created_by', $employeeIds)
            ->where('process_status', '!=', 'completed')
            ->where(function ($q) {
                $q->where('is_deleted', 0)->orWhereNull('is_deleted');
            })
            ->orderByDesc('id')
            ->get([
                'id',
                'entry_date',
                'title',
                'project_id',
                'type_of_work',
                'email',
                'institute',
                'department',
                'profession',
                'budget',
                'process_status',
                'hierarchy_level',
                'created_by',
                'project_status',
                'assign_by',
                'assign_date',
                'created_at', // <-- ADD THIS
                DB::raw("CONCAT(DATEDIFF(projectduration, created_at), ' days ', MOD(TIMESTAMPDIFF(HOUR, created_at, projectduration), 24), ' hrs') AS projectduration")
            ]);

        $to_do_list = $paymentStatus->filter(function ($entry) {
            $payment = $entry->paymentProcess instanceof \Illuminate\Support\Collection
                ? $entry->paymentProcess->first()
                : $entry->paymentProcess;
            return $payment && in_array($payment->payment_status, ['advance_pending','final_payment_pending','partial_payment_pending','completed']);
        });

        $inprocess_list = EntryProcessModel::where('process_status', 'client_review')
            ->where('is_deleted', 0)
            ->orderByDesc('id')
            ->get(['id', 'project_id', 'title', 'process_status', 'hierarchy_level', 'created_at']); // <-- ADD 'created_at'

      
        $to_do_lists = $to_do_list
          
            ->merge($inprocess_list)
          
            ->filter(function ($item) {
                // Exclude if process_status is 'completed'
                return strtolower($item->process_status ?? '') !== 'completed';
            })
            ->filter(function ($item) {
                $paymentProcess = $item->paymentProcess instanceof \Illuminate\Support\Collection
                    ? $item->paymentProcess->first()
                    : $item->paymentProcess;
                $paymentStatus = optional($paymentProcess)->payment_status;

                // Exclude if payment_status is 'completed' or 'advance_received'
                return !in_array(strtolower($paymentStatus), ['completed', 'advance_received']);
            })
            ->map(function ($item) {
                $projectId = $item->project_id ?? optional($item->projectData)->project_id;
                $projectKey = optional($item->projectData)->project_id ?? $item->project_id; // Deduplication key
                return [
                    'original_item' => $item,
                    'dedupe_key' => (string) $projectKey // Ensure string for uniqueness
                ];
            })
            ->unique('dedupe_key') // Deduplicate using project_ids
            ->map(function ($wrap) {
                $item = $wrap['original_item'];

                return [
                    'project_id' => $item->project_id ?? optional($item->projectData)->project_id,
                    'project_ids' => optional($item->projectData)->project_id ?? $item->project_id,
                    'hierarchy_level' => $item->hierarchy_level ?? optional($item->projectData)->hierarchy_level,
                    'hierarchy_levels' => optional($item->projectData)->hierarchy_level ?? $item->hierarchy_level,
                    'created_at' => $item->created_at ?? optional($item->projectData)->created_at,
                    'payment_status' => optional($item->paymentProcess)->payment_status ?? null,
                ];
            })
            ->sortByDesc('created_at')
            ->values();

        $todo_count = $to_do_lists->count();

        // Payment Count and Lists
        $payment_count = ['advance_pending' => 0, 'partial_payment_pending' => 0, 'final_payment_pending' => 0, 'completed' => 0];
        $advance_pending_list = $partial_payment_pending_list = $final_payment_pending_list = [];

        foreach ($to_do_list as $entry) {
            $status = optional($entry->paymentProcess)->payment_status;
            if (isset($payment_count[$status])) {
                $payment_count[$status]++;
                match ($status) {
                    'advance_pending' => $advance_pending_list[] = $entry,
                    'partial_payment_pending' => $partial_payment_pending_list[] = $entry,
                    'final_payment_pending' => $final_payment_pending_list[] = $entry,
                    'completed' => $completed_list[] = $entry,
                };
            }
        }
        $journalPaymentPending = ProjectAssignDetails::with('projectData.paymentProcess')
            ->where('type', 'publication_manager')
            ->whereIn('status', ['submitted', 'published'])
            ->whereHas('projectData', function ($query) {
            $query->whereHas('paymentProcess', function ($wq) {
                $wq->where('payment_status', '!=', 'completed');
            });
            })
            ->orderByDesc('id')
            ->get();
            
        $journalPaymentPendingCount = $journalPaymentPending->count();


        $journalList = ProjectAssignDetails::with(['projectData.paymentProcess'])
            ->where('type', 'publication_manager')
            ->where('status', 'submitted')
            ->whereIn('created_by', $peopleIds_sme)
            ->get()
            ->map(function ($item) {
                return [
                    'project_id' => $item->projectData->project_id ?? null,
                    'entry_date' => $item->projectData->entry_date ?? null,
                    'client_name' => $item->projectData->client_name ?? null,
                    'title' => $item->projectData->title ?? null,
                    'projectduration' => $item->projectData->projectduration ?? null,
                    'process_status' => $item->projectData->process_status ?? null,
                    'payment_status' => $item->projectData->paymentProcess->payment_status ?? null,
                ];
            });
        $journalListCount = $journalList->count();

        // Withdrawals
        $withdrawal_list = EntryProcessModel::where(function ($q) {
            $q->where('is_deleted', 0)->orWhereNull('is_deleted');
        })
            ->whereRaw("LOWER(process_status) = ?", ['withdrawal'])
            ->where('process_status', '!=', 'completed')
             ->whereHas('paymentProcess', function ($query) {
                $query->where('payment_status', '!=', 'completed');
            })
            ->orderByDesc('id')
            ->get(['id', 'project_id', 'title', 'process_status', 'hierarchy_level']);
        $withdrawlList_count = $withdrawal_list->count();

        // Completed Projects
        $completed_process = EntryProcessModel::whereIn('is_deleted', [0, null])
           ->where('process_status', 'completed')
            ->orderByDesc('id')
             ->whereHas('paymentProcess', function ($query) {
                $query->where('payment_status', '!=', 'completed');
            })
            ->get(['id', 'project_id', 'title', 'entry_date', 'client_name', 'projectduration', 'process_status', 'hierarchy_level'])

            
        ->map(function ($item) {
            return [
                'project_id' => $item->project_id,
                'entry_date' => $item->entry_date,
                'client_name' => $item->client_name,
                'title' => $item->title,
                'projectduration' => $item->projectduration,
                'process_status' => $item->process_status,
                'payment_status' => optional($item->paymentProcess)->payment_status,
                'hierarchy_level' => $item->hierarchy_level,
            ];
        })->values();

        $completedList_count = $completed_process->count();
        // Verification List
        $verification_list = PaymentStatusModel::with(['paymentData1', 'projectData'])
        ->whereIn('payment_status', ['partial_payment_pending', 'final_payment_pending'])
        ->whereDoesntHave('paymentData1', function ($query) {
            $query->whereIn('payment_status', ['advance_received', 'partial_payment_received']);
        })
        ->whereHas('projectData', function ($query) {
            $query->where('is_deleted', 0);
        })
        ->orderByDesc('id')
            ->get()
            ->map(function ($item) {
                return [
                    'project_id'      => $item->projectData->project_id ?? null,
                    'id'           => $item->projectData->id ?? null,
                    'hierarchy_level' => $item->projectData->hierarchy_level ?? null,
                ];
            });
        Log::info('verification_list Process Count: ' . $verification_list);

        $verificationList_count = $verification_list->count();

        // Freelancer List
        $freelancerList = People::where('employee_type', 'freelancers')->where('status', 1)->pluck('id')->filter();
        $freelancerLists = ProjectAssignDetails::with(['projectData.employeePaymentDetails'])
            ->where('status', 'completed')
            ->whereIn('assign_user', $freelancerList)
            ->whereHas('projectData', fn($q) => $q->whereIn('type_of_work', ['manuscript', 'thesis']))
            ->orderByDesc('id')
            ->get()
            ->map(function ($item) {
                $project = $item->projectData;
                $duration = $item->project_duration;
                $adjusted = match ($project->type_of_work) {
                    'manuscript' => Carbon::parse($duration)->addDays(30)->toDateString(),
                    'thesis' => Carbon::parse($duration)->addDays(60)->toDateString(),
                    default => $duration,
                };

                return [
                    'project_id' => $project->project_id ?? null,
                    'entry_date' => $project->entry_date ?? null,
                    'client_name' => $project->client_name ?? null,
                    'title' => $project->title ?? null,
                    'projectduration' => $adjusted,
                    'process_status' => $project->process_status ?? null,
                    'payment_status' => optional($project->employeePaymentDetails->first())->status ?? null,
                ];
            });
        $freelancerListCount = $freelancerLists->count();

        // Journal list count
        $journalList = ProjectAssignDetails::with('projectData.paymentProcess')
            ->where('type', 'publication_manager')
            ->whereIn('status', ['submitted','published'])
            ->count();

        // Payment breakdown
        $payments = PaymentStatusModel::with(['paymentData', 'projectData', 'paymentLog'])
            ->whereIn('payment_status', ['advance_pending', 'partial_payment_pending', 'final_payment_pending'])
            ->orderByDesc('created_at')
            ->get()
            ->filter(fn($p) => optional($p->paymentLog)->isNotEmpty())
            ->map(fn($p) => [
                'id' => optional($p->projectData)->id,
                'project_id' => optional($p->projectData)->project_id,
                'title' => optional($p->projectData)->title,
                'institute' => optional($p->projectData)->institute,
                'department' => optional($p->projectData)->department,
                'profession' => optional($p->projectData)->profession,
                'budget' => optional($p->projectData)->budget,
                'process_status' => optional($p->projectData)->process_status,
                'hierarchy_level' => optional($p->projectData)->hierarchy_level,
                'email' => optional($p->projectData)->email,
                'phone' => optional($p->projectData)->contact_number,
                'payment_status' => $p->payment_status,
                'payment' => optional($p->paymentData->first())->payment ?? 0,
                'created_at' => $p->created_at,
            ]);

        $advance_payment = $payments->where('payment_status', 'advance_pending')->values();
        $partial_payment = $payments->where('payment_status', 'partial_payment_pending')->values();
        $final_payment_pending = $payments->where('payment_status', 'final_payment_pending')->count();


        $received_amount = $advance_payment->sum('payment') + $partial_payment->sum('payment');
        $total_budget = $advance_payment->sum('budget') + $partial_payment->sum('budget');
        $balance_amount = $total_budget - $received_amount;

        // Filtered Data Based on `type`
        // $filtered_data = match ($type) {
        //     'advance_pending' => ['advance_pending_list' => $advance_pending_list],
        //     'partial_payment_pending' => ['partial_payment_pending_list' => $partial_payment_pending_list],
        //     'final_payment_pending' => ['final_payment_pending_list' => $final_payment_pending_list],
        //     'withdrawal' => ['withdrawal' => $withdrawal_list],
        //     'completed' => ['completed' => $completed_list],
        //     'verification' => ['verification' => $verification_list],
        //     default => [
        //         'advance_pending_list' => $advance_pending_list,
        //         'partial_payment_pending_list' => $partial_payment_pending_list,
        //         'final_payment_pending_list' => $final_payment_pending_list,
        //         'withdrawal' => $withdrawal_list,
        //         'completed' => $completed_list,
        //         'verification' => $verification_list,
        //     ],
        // };

        return response()->json([
            'data' => [
                'to_do' => $to_do_lists->values(),
                'withdrawal' => $withdrawal_list,

                // 'advance_payment' => $advance_payment, -- no
                // 'partial_payment' => $partial_payment, --no

                'received_amount' => $received_amount,
                'balance_amount' => $balance_amount,
                'withdrawlList_count' => $withdrawlList_count,
                'completedList_count' => $completedList_count,
                'todo_count' => $todo_count,
                'verificationList_count' => $verificationList_count,
                'completed' => $completed_process,
                'verificationList' => $verification_list,
                'payment_count' => $payment_count,

                // 'filtered_data' => $filtered_data,  --no


                'freelancerListCount' => $freelancerListCount,
                'journalList' => $journalList,
                'final_payment_pending' => $final_payment_pending,
                'journalPaymentPendingCount' => $journalPaymentPendingCount,
                'journalPaymentPending' => $journalPaymentPending,

                'journalListCount' => $journalListCount
            ]
        ]);
    }

    public function accountant_count_list(Request $request)
    {
        $params = $request->input('type');

        // Fetch employees
        $employees = User::with('createdByUser')
            ->select('id', 'employee_name', 'position', 'created_by')
            ->get();

        // Fetch entry process data
        $paymentStatus = EntryProcessModel::with('paymentProcess', 'statusData')


            ->whereIn('created_by', $employees->pluck('id'))
            ->where('process_status', '!=', 'completed')
            ->where(function ($query) {
                $query->where('is_deleted', 0)
                    ->orWhereNull('is_deleted');
            })
            ->get([
                'id',
                'entry_date',
                'title',
                'project_id',
                'type_of_work',
                'email',
                'institute',
                'department',
                'profession',
                'budget',
                'process_status',
                'hierarchy_level',
                'created_by',
                'project_status',
                'assign_by',
                'assign_date',
                DB::raw("CONCAT(DATEDIFF(projectduration, created_at), ' days ', MOD(TIMESTAMPDIFF(HOUR, created_at, projectduration), 24), ' hrs') AS projectduration"),
                'client_name',
            ]);

        // To-Do List Filtering
        $to_do_list = $paymentStatus->filter(function ($entry) {
            $paymentProcess = $entry->paymentProcess ?? null;
            return $paymentProcess && in_array($paymentProcess->payment_status, [
                'advance_pending',
                'partial_payment_pending',
                'final_payment_pending'
            ]);
        });

        // Categorize pending payments
        $advance_pending_list = [];
        
        $partial_payment_pending_list = [];
        $final_payment_pending_list = [];

        foreach ($to_do_list as $entry) {
            $payment_status = $entry->paymentProcess->payment_status;
            switch ($payment_status) {
                case 'advance_pending':
                    $advance_pending_list[] = $entry;
                    break;
                case 'partial_payment_pending':
                    $partial_payment_pending_list[] = $entry;
                    break;
                case 'final_payment_pending':
                    $final_payment_pending_list[] = $entry;
                    break;
            }
        }

        // Withdrawal & Completed List

        $withdrawal_list = EntryProcessModel::whereIn('is_deleted', [0, null])
            ->with('statusData', 'paymentProcess')
            ->whereRaw("LOWER(process_status) = ?", ['withdrawal'])
            ->where('process_status', '!=', 'completed')
            ->whereHas('paymentProcess', function ($query) {
                $query->where('payment_status', '!=', 'completed');
            })
            ->select([
                'id',
                'project_id',
                'client_name',
                'title',
                'entry_date',
                'process_status',
                'hierarchy_level',
                DB::raw("CONCAT(DATEDIFF(projectduration, created_at), ' days ', MOD(TIMESTAMPDIFF(HOUR, created_at, projectduration), 24), ' hrs') AS projectduration")
            ])
            ->get();


        // $completed_list = EntryProcessModel::whereIn('is_deleted', [0, null])
        // ->with('statusData','paymentProcess')
        // ->whereRaw("LOWER(process_status) = 'completed'")
        // ->select([
        //     'id',
        //     'project_id',
        //     'client_name',
        //     'title',
        //     'entry_date',
        //     'process_status',
        //     'hierarchy_level',
        //     DB::raw("CONCAT(DATEDIFF(projectduration, created_at), ' days ', MOD(TIMESTAMPDIFF(HOUR, created_at, projectduration), 24), ' hrs') AS projectduration")
        // ])
        // ->get(); $completed_list = $paymentStatus->filter(function ($entry) {

        // $completed_list = $paymentStatus->filter(function ($entry) {
        //     return optional($entry->paymentProcess)->payment_status !== null &&
        //         in_array($entry->paymentProcess->payment_status, [
        //             'advance_pending',
        //             'partial_payment_pending',
        //             'final_payment_pending',
        //         ]);
        // })->values();


        // $completed_process = EntryProcessModel::whereIn('is_deleted', [0, null])
        //     ->whereRaw("LOWER(process_status) = ?", ['completed'])
        //     ->orderBy('id', 'desc')
        //     ->get(['id', 'project_id', 'title', 'process_status', 'hierarchy_level']);

        // $completed_list = $completed_list->merge($completed_process)
        //     ->map(function ($item) {
        //         return [
        //             'project_id' => $item->project_id,
        //             'entry_date' => $item->entry_date,
        //             'client_name' => $item->client_name,
        //             'title' => $item->title,
        //             'projectduration' => $item->projectduration,
        //             'process_status' => $item->process_status,
        //             'payment_status' => optional($item->paymentProcess)->payment_status,
        //         ];
        //     })
        //     ->values();

        // $completedList_count = $completed_list->count();

        $completed_list = EntryProcessModel::whereIn('is_deleted', [0, null])
           ->where('process_status', 'completed')
            ->orderByDesc('id')
             ->whereHas('paymentProcess', function ($query) {
                $query->where('payment_status', '!=', 'completed');
            })
            ->get(['id', 'project_id', 'title', 'entry_date', 'client_name', 'projectduration', 'process_status', 'hierarchy_level'])

            
        ->map(function ($item) {
            return [
                'project_id' => $item->project_id,
                'entry_date' => $item->entry_date,
                'client_name' => $item->client_name,
                'title' => $item->title,
                'projectduration' => $item->projectduration,
                'process_status' => $item->process_status,
                'payment_status' => optional($item->paymentProcess)->payment_status,
                'hierarchy_level' => $item->hierarchy_level,
            ];
        })->values();
        // Verification List
        // $verification_list = PaymentStatusModel::where('is_verify', 0)
        //     ->with(['paymentData', 'projectData'])
        //     ->orderByDesc('created_at')
        //     ->get()
        //     ->map(function ($item) {
        //         return [
                    // 'project_id'       => $item->projectData->project_id ?? null,
                    // 'entry_date'       => $item->projectData->entry_date ?? null,
                    // 'client_name'      => $item->projectData->client_name ?? null,
                    // 'title'            => $item->projectData->title ?? null,
                    // 'projectduration'  => $item->projectData->projectduration ?? null,
                    // 'process_status'   => $item->projectData->process_status ?? null,
                    // 'payment_status'   => $item->payment_status,
        //         ];
        //     });

        $verification_list = PaymentStatusModel::with(['paymentData1', 'projectData'])
        ->whereIn('payment_status', ['partial_payment_pending', 'final_payment_pending'])
        ->whereDoesntHave('paymentData1', function ($query) {
            $query->whereIn('payment_status', ['advance_received', 'partial_payment_received']);
        })
        ->whereHas('projectData', function ($query) {
            $query->where('is_deleted', 0);
        })
        ->orderByDesc('id')
            ->get()
            ->map(function ($item) {
                return [
                    'project_id'       => $item->projectData->project_id ?? null,
                    'entry_date'       => $item->projectData->entry_date ?? null,
                    'client_name'      => $item->projectData->client_name ?? null,
                    'title'            => $item->projectData->title ?? null,
                    'projectduration'  => $item->projectData->projectduration ?? null,
                    'process_status'   => $item->projectData->process_status ?? null,
                    'payment_status'   => $item->payment_status,
                ];
            });


        $peopleIds_sme = People::where('position', '27')
            ->pluck('id')
            ->filter()
            ->values()
            ->toArray();

        $journalList = ProjectAssignDetails::with(['projectData.paymentProcess'])
            ->where('type', 'publication_manager')
            ->where('status', 'submitted')
            ->whereIn('created_by', $peopleIds_sme)
            ->get()
            ->map(function ($item) {
                return [
                    'project_id' => $item->projectData->project_id ?? null,
                    'entry_date' => $item->projectData->entry_date ?? null,
                    'client_name' => $item->projectData->client_name ?? null,
                    'title' => $item->projectData->title ?? null,
                    'projectduration' => $item->projectData->projectduration ?? null,
                    'process_status' => $item->projectData->process_status ?? null,
                    'payment_status' => $item->projectData->paymentProcess->payment_status ?? null,
                ];
            });

        //freelancer 
        $freelancerList = People::where('employee_type', 'freelancers')
            ->where('status', 1)
            ->pluck('id')
            ->filter()
            ->values();




        $freelancerLists = ProjectAssignDetails::with(['projectData.employeePaymentDetails'])
            ->where('status', 'completed')
            ->whereIn('assign_user', $freelancerList)
            ->whereHas('projectData', function ($query) {
                $query->whereIn('type_of_work', ['manuscript', 'thesis']);
            })
            ->get()
            ->map(function ($item) {
                $project = $item->projectData;
                $typeOfWork = $project->type_of_work ?? null;
                $originalDuration = $item->project_duration ?? null;

                // Adjust duration
                if ($originalDuration) {
                    $adjustedDuration = match ($typeOfWork) {
                        'manuscript' => Carbon::parse($originalDuration)->addDays(30)->toDateString(),
                        'thesis' => Carbon::parse($originalDuration)->addDays(60)->toDateString(),
                        default => $originalDuration,
                    };
                } else {
                    $adjustedDuration = null;
                }

                return [
                    'project_id' => $project->project_id ?? null,
                    'entry_date' => $project->entry_date ?? null,
                    'client_name' => $project->client_name ?? null,
                    'title' => $project->title ?? null,
                    'projectduration' => $adjustedDuration,
                    'process_status' => $project->process_status ?? null,
                    'payment_status' => optional($project->employeePaymentDetails->first())->status ?? null,
                ];
            });






        // Filtering Data Based on 'type' Parameter
        $filtered_data = [];

        if ($params == 'advance_pending') {
            $filtered_data = $advance_pending_list;
        } elseif ($params == 'partial_payment_pending') {
            $filtered_data = $partial_payment_pending_list;
        } elseif ($params == 'final_payment_pending') {
            $filtered_data = $final_payment_pending_list;
        } elseif ($params == 'withdrawal') {
            $filtered_data = $withdrawal_list;
        } elseif ($params == 'completed') {
            $filtered_data = $completed_list;
        } elseif ($params == 'verification') {
            $filtered_data = $verification_list;
        } elseif ($params == 'journalPending') {
            $filtered_data = $journalList;
        } elseif ($params == 'freelancer') {
            $filtered_data = $freelancerLists;
        } else {
            // If no specific type is provided, return all lists
            $filtered_data = [
                'advance_pending_list' => $advance_pending_list,
                'partial_payment_pending_list' => $partial_payment_pending_list,
                'final_payment_pending_list' => $final_payment_pending_list,
                'withdrawal' => $withdrawal_list,
                'completed' => $completed_list,
                'verification' => $verification_list,
            ];
        }





        return response()->json([
            'data' => $filtered_data,
        ]);
    }


    //getting the payment invoice
    public function payment_invoice(Request $request)
    {
        $project_id = $request->input('project_id');

        $settings = Setting::all();

        $invoice_payment = EntryProcessModel::with('paymentProcess', 'institute', 'department', 'profession')
            ->where('is_deleted', 0)
            ->where(function ($query) {
                $query->where('is_deleted', 0)
                    ->orWhereNull('is_deleted');
            })
            ->when($project_id, function ($query, $project_id) {
                return $query->where('project_id', $project_id);
            })
            ->get([
                'id',
                'entry_date',
                'title',
                'project_id',
                'type_of_work',
                'email',
                'institute',
                'department',
                'profession',
                'budget',
                'process_status',
                'hierarchy_level',
                'created_by',
                'project_status',
                'assign_by',
                DB::raw("CONCAT(DATEDIFF(projectduration, created_at), ' days ', MOD(TIMESTAMPDIFF(HOUR, created_at, projectduration), 24), ' hrs') AS projectduration"),
                'client_name',
                'contact_number',
                'invoice_number'
            ]);

        $invoice_payment->each(function ($invoice) {
            $advancePendingTotal = 0;
            $partialPaymentPendingTotal = 0;

            if ($invoice->paymentProcess) {
                $paymentData = collect($invoice->paymentProcess['paymentData'] ?? []);

                foreach ($paymentData as $paymentEntry) {
                    $paymentAmount = (float) $paymentEntry['payment'];
                    $paymentType = $paymentEntry['payment_type'] ?? null;

                    if ($paymentType === 'advance_pending') {
                        $advancePendingTotal += $paymentAmount;
                    }
                    if ($paymentType === 'partial_payment_pending') {
                        $partialPaymentPendingTotal += $paymentAmount;
                    }
                }
            }

            // Add calculated totals to response
            $invoice->advance_pending_total = $advancePendingTotal;
            $invoice->partial_payment_pending_total = $partialPaymentPendingTotal;
            $invoice->total_received_amount = $advancePendingTotal + $partialPaymentPendingTotal;

            // Calculate total pending amount (assuming budget - received amount)
            $invoice->total_pending_amount = $invoice->total_received_amount -  $invoice->budget;

            // Calculate received amount
            $invoice->received_amount = $invoice->total_pending_amount;
        });

        return response()->json([
            'settings' => $settings,
            'data' => $invoice_payment
        ]);
    }

    public function storeInvoiceDetails(Request $request)
    {
        try {

            $validatedData = $request->validate([
                'project_id' => 'required|exists:entry_processes,id',
                'is_gst' => 'required',
                //'exclusive_gst' => 'required|in:yes,no',
                'due_date' => 'required|date',
                'invoice_no' => 'required|string',
                'created_by' => 'required|string',
                'created_date' => 'required|date',
                'invoice_doc' => 'required|file'

            ]);

            $existingInvoice = InvoiceDetails::where('project_id', $validatedData['project_id'])->first();

            if ($existingInvoice) {
                return response()->json([
                    'message' => 'Invoice for this project already exists.',
                    'data' => $existingInvoice
                ], 409);
            }

            if ($request->hasFile('invoice_doc')) {
                $file = $request->file('invoice_doc');

                $sanitizedInvoiceNo = preg_replace('/[^A-Za-z0-9_\-]/', '_', $validatedData['invoice_no']);

                $fileName = $sanitizedInvoiceNo . '.' . $file->getClientOriginalExtension();

                $destinationPath = public_path('invoice_doc');

                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0777, true);
                }

                $file->move($destinationPath, $fileName);

                $filePath = 'invoice_doc/' . $fileName;
            } else {
                return response()->json([
                    'message' => 'Invoice document is missing.'
                ], 400);
            }

            // Store the invoice details in DB
            $invoice = InvoiceDetails::create([
                'project_id' => $validatedData['project_id'],
                'is_gst' => $validatedData['is_gst'],
                //'exclusive_gst' => $validatedData['exclusive_gst'],
                'due_date' => $validatedData['due_date'],
                'invoice_no' => $validatedData['invoice_no'],
                'created_by' => $validatedData['created_by'],
                'created_date' => $validatedData['created_date'],
                'invoice_doc' => $filePath,
                'payment_status' => $request->input('payment_status') // Default to 'pending' if not provided
            ]);

            return response()->json([
                'message' => 'Invoice saved successfully.',
                'data' => $invoice
            ], 201); // 201 Created

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred.',
                'error' => $e->getMessage()
            ], 500); // 500 Internal Server Error
        }
    }




    public function getInvoiceDetails()
    {



        $invoices = InvoiceDetails::with(['project:id,id,project_id,client_name'])->get();
        $formatted = $invoices->map(function ($invoice) {
            return [
                'project_id' => $invoice->project->project_id ?? '-',
                'ids' => $invoice->project_id,
                'is_gst' => $invoice->is_gst,
                //'exclusive_gst' => $invoice->exclusive_gst,
                'due_date' => $invoice->due_date,
                'invoice_no' => $invoice->invoice_no,
                'client_name' => $invoice->project->client_name ?? '-',
                'created_by' => $invoice->created_by,
                'created_date' => $invoice->created_date,
                'invoice_doc' => $invoice->invoice_doc,
                'payment_status' => $invoice->payment_status,

            ];
        });

        return response()->json($formatted);
    }



    public function getInvoiceDetailsAndSendMail()
    {
        $project_id = request('project_id');

        $invoices = InvoiceDetails::with([
            'project:id,id,project_id,client_name,title,contact_number,entry_date,budget',
        ])->where('project_id', $project_id)->get();

        $advancePayment = PaymentStatusModel::with('paymentData')
            ->where('project_id', $project_id)
            ->first();

        $advanceAmount = '-';

        if ($advancePayment && $advancePayment->paymentData) {
            $advancePending = $advancePayment->paymentData->where('payment_type', 'advance_pending')->first();
            $advanceAmount = $advancePending->payment ?? '-';
        }

        //partial payment
        $advancePayment = PaymentStatusModel::with('paymentData')
            ->where('project_id', $project_id)
            ->first();

        $PartialAmount = '-';

        if ($advancePayment && $advancePayment->paymentData) {
            $advancePending = $advancePayment->paymentData->where('payment_type', 'partial_payment_pending')->first();
            $PartialAmount = $advancePending->payment ?? '-';
        }


        //final payment
        $advancePayment = PaymentStatusModel::with('paymentData')
            ->where('project_id', $project_id)
            ->first();

        $finalAmount = '-';

        if ($advancePayment && $advancePayment->paymentData) {
            $advancePending = $advancePayment->paymentData->where('payment_type', 'final_payment_pending')->first();
            $finalAmount = $advancePending->payment ?? '-';
        }


        foreach ($invoices as $invoice) {
            $userDetails = EntryProcessModel::where('id', $invoice->project_id)->first();

            if (!$userDetails) {
                Log::warning('User not found for project_id', ['project_id' => $invoice->project_id]);
                continue;
            }

            $mailData = [
                'name' => $invoice->project->client_name ?? '-',
                'role' => 'Assigned Role',
                'project_id' => $invoice->project->project_id ?? '-',
                'project_title' => $invoice->project->title ?? '-',
                'client_name' => $invoice->project->client_name ?? '-',
                'contact_number' => $invoice->project->contact_number ?? '-',
                'invoice_number' => $invoice->invoice_no ?? '-',
                'invoice_date' => $invoice->due_date ?? '-',
                'budget' => $invoice->budget ?? '-',
                'start_date' => $invoice->project->entry_date ?? '-',
                'end_date' => $invoice->due_date ?? '-',
                'advance_pending' => $advanceAmount,
                'partial_payment_pending' => $PartialAmount,
                'final_payment_pending' => $finalAmount,
            ];

            $invoiceDoc = $invoice->invoice_doc; // e.g., 'invoice_doc/_MR-250559.pdf'
            Log::info('Invoice document path', ['path' => $invoiceDoc]);

            try {
                Mail::to($userDetails->email)->send(new InvoicePayment($mailData, $invoiceDoc));
            } catch (\Exception $e) {
                Log::error('Failed to send invoice email', [
                    'email' => $userDetails->email,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Invoice emails processed', ['total' => $invoices->count()]);

        return response()->json(['message' => 'Emails sent with invoice attachments.']);
    }






    // public function getInvoiceDetails(Request $request)
    // {
    //     $projectId = $request->query('project_id');

    //     if (!$projectId) {
    //         return response()->json(['error' => 'project_id is required'], 400);
    //     }

    //     // Fetch invoice for that project_id
    //     $invoice = InvoiceDetails::whereHas('project', function ($query) use ($projectId) {
    //         $query->where('project_id', $projectId);
    //     })->with('project:id,id,project_id,client_name')->first();

    //     if (!$invoice) {
    //         return response()->json(['message' => 'No invoice found for this project_id'], 404);
    //     }

    //     return response()->json([
    //         'project_id' => $invoice->project->project_id ?? '-',
    //         'is_gst' => $invoice->is_gst,
    //         'invoice_no' => $invoice->invoice_no,
    //         'client_name' => $invoice->project->client_name ?? '-',
    //         'created_by' => $invoice->created_by,
    //         'created_date' => $invoice->created_date,
    //         'invoice_doc' => $invoice->invoice_doc,
    //     ]);
    // }
}
