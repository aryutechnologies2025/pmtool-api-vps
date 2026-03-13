<?php

namespace App\Http\Controllers;

use App\Models\DepartmentModel;
use App\Models\EmployeePaymentDetails;
use App\Models\EntryProcessModel;
use App\Models\InstitutionModel;
use App\Models\PaymentDetails;
use App\Models\PaymentStatusModel;
use App\Models\People;
use App\Models\ProfessionModel;
use App\Models\ProjectAssignDetails;
use App\Models\ProjectLogs;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ReportsController extends Controller
{
    //process report
    // public function getProcessReport()
    // {
    //     $statuses = ['not_assigned', 'withdrawal', 'in_progress', 'completed'];

    //     $projects = EntryProcessModel::selectRaw('type_of_work, process_status, COUNT(*) as count')
    //         ->groupBy('type_of_work', 'process_status')
    //         ->where('process_status', '!=', 'completed')
    //         ->where('is_deleted', 0)
    //         ->get();

    //     $formattedData = [];

    //     foreach ($projects as $project) {
    //         $typeOfWork = $project->type_of_work;
    //         $status = $project->process_status;
    //         $count = $project->count;

    //         if (!isset($formattedData[$typeOfWork])) {
    //             $formattedData[$typeOfWork] = array_fill_keys($statuses, 0);
    //         }

    //         $formattedData[$typeOfWork][$status] = $count;
    //     }

    //     $result = [];
    //     foreach ($formattedData as $typeOfWork => $statusCounts) {
    //         $result[] = [
    //             'type_of_work' => $typeOfWork,
    //             'total_count' => array_sum($statusCounts),
    //             'not_assigned' => $statusCounts['not_assigned'],
    //             'withdrawal' => $statusCounts['withdrawal'],
    //             'in_progress' => $statusCounts['in_progress'],
    //             'completed' => $statusCounts['completed'],
    //         ];
    //     }

    //     return response()->json([
    //         'details' => $result
    //     ]);
    // }

    public function getProcessReport(Request $request)
    {
        $fromDate = $request->query('from_date');
        $toDate = $request->query('to_date');

        $statuses = ['not_assigned', 'withdrawal', 'in_progress', 'pending_author', 'client_review', 'completed'];

        $projects = EntryProcessModel::selectRaw('type_of_work, process_status, COUNT(*) as count')
            ->where('is_deleted', 0)
            // ->whereDate('entry_date', '>=', $fromDate)
            // ->whereDate('entry_date', '<=', $toDate)
            ->when($fromDate, fn ($q) => $q->whereDate('entry_date', '>=', $fromDate))
            ->when($toDate, fn ($q) => $q->whereDate('entry_date', '<=', $toDate))
            // ->where('process_status', '!=', 'completed')
            ->groupBy('type_of_work', 'process_status')
            ->get();

        $completedProjects = EntryProcessModel::selectRaw('type_of_work, COUNT(*) as count')
            ->where('is_deleted', 0)
            ->where('process_status', 'completed')
            // ->whereDate('entry_date', '>=', $fromDate)
            // ->whereDate('entry_date', '<=', $toDate)
            ->when($fromDate, fn ($q) => $q->whereDate('entry_date', '>=', $fromDate))
            ->when($toDate, fn ($q) => $q->whereDate('entry_date', '<=', $toDate))
            ->groupBy('type_of_work')
            ->get()
            ->keyBy('type_of_work');

        $formattedData = [];

        foreach ($projects as $project) {
            $typeOfWork = $project->type_of_work;
            $status = $project->process_status;

            if (! isset($formattedData[$typeOfWork])) {
                $formattedData[$typeOfWork] = array_fill_keys($statuses, 0);
            }

            $formattedData[$typeOfWork][$status] = $project->count;
        }

        foreach ($completedProjects as $typeOfWork => $completed) {
            if (! isset($formattedData[$typeOfWork])) {
                $formattedData[$typeOfWork] = array_fill_keys($statuses, 0);
            }

            $formattedData[$typeOfWork]['completed'] = $completed->count;
        }

        $result = [];
        foreach ($formattedData as $typeOfWork => $statusCounts) {

            $totalCount =
                $statusCounts['not_assigned'] +
                $statusCounts['withdrawal'] +
                $statusCounts['in_progress'] +
                $statusCounts['pending_author'] +
                $statusCounts['completed'] +
                $statusCounts['client_review'];

            $result[] = [
                'type_of_work' => $typeOfWork,
                'total_count' => $totalCount,

                'not_assigned' => $statusCounts['not_assigned'],
                'withdrawal' => $statusCounts['withdrawal'],
                'in_progress' => $statusCounts['in_progress'],
                'pending_author' => $statusCounts['pending_author'],
                'client_review' => $statusCounts['client_review'],

                'completed' => $statusCounts['completed'],
            ];
        }

        return response()->json(['details' => $result]);
    }

    // public function getProjectPayment()
    // {
    //     $totalProjectPayment = EntryProcessModel::selectRaw('type_of_work, COUNT(*) as count, SUM(budget) as total_budget')
    //         ->with(['journalPaymentDetails', 'paymentProcess.paymentData' => function ($query) {
    //         $query->select('id', 'payment_id', 'payment', 'payment_date')
    //             ->where('is_deleted', 0);
    //         }])
    //         ->groupBy('type_of_work')
    //         ->get();

    //     $totalProjectPayments = EntryProcessModel::with(['employeePaymentDetails:id,payment,type', 'journalPaymentDetails:id,payment'])
    //         ->select('id', 'project_id', 'type_of_work')
    //         ->where('is_deleted', 0)
    //         ->get();

    //     $paidUnpaidPayments = $totalProjectPayments->groupBy('type_of_work')->map(function ($projects, $typeOfWork) {
    //         $paid = $projects->flatMap(function ($project) {
    //         return $project->employeePaymentDetails->where('status','paid');
    //         })->sum('payment');

    //         $unpaid = $projects->flatMap(function ($project) {
    //         return $project->employeePaymentDetails->where('status', 'pending');
    //         })->count();

    //         $journalPaid = $projects->flatMap(function ($project) {
    //         return $project->journalPaymentDetails;
    //         })->sum('payment');

    //         return [
    //         'type_of_work' => $typeOfWork,
    //         'paid_payment' => $paid,
    //         'unpaid_payment_count' => $unpaid,
    //         'journal_paid' => $journalPaid,
    //         ];
    //     });

    //     return response()->json([
    //         'total_project_payment' => $totalProjectPayment,
    //         'paid_unpaid_payments' => $paidUnpaidPayments,
    //     ]);

    //     $paymentStatus = PaymentStatusModel::with('paymentData','paymentLData')
    //         ->orderBy('created_at', 'desc')
    //         ->get();

    //     $formattedData = $totalProjectPayment->map(function ($projectPayment) use ($paymentStatus) {
    //         $paymentFields = [
    //             'amount_received' => 0,
    //             'amount_pending' => $projectPayment->total_budget,
    //         ];

    //         foreach ($paymentStatus as $status) {
    //             if ($status->projectData->type_of_work === $projectPayment->type_of_work) {
    //                 foreach ($status->paymentData as $payment) {
    //                     $paymentFields['amount_received'] += $payment->payment;
    //                 }
    //             }
    //         }

    //         // Calculate the amount pending
    //         $paymentFields['amount_pending'] = $projectPayment->total_budget - $paymentFields['amount_received'];

    //         return [
    //             'type_of_project' => $projectPayment->type_of_work,
    //             'total_project_count' => $projectPayment->count,
    //             'budget' => $projectPayment->total_budget,
    //             'amount_received' => $paymentFields['amount_received'],
    //             'amount_pending' => $paymentFields['amount_pending'],
    //             'Journal_paid_amount' =>'0',
    //             'Freelance_paid' => '0',
    //             'Freelance_unpaid' => '0',
    //         ];
    //     });

    //     // $employee_payment = EmployeePaymentDetails::where('project_id', $totalProjectPayment->id)
    //     // ->where('type', 'publication_manager')
    //     // ->sum('payment');

    //     return response()->json([
    //         'details' => $formattedData,
    //         'total_project_payment' => $totalProjectPayments,
    //         // 'employee_payment' => $employee_payment,
    //     ]);
    // }

    public function getProjectPayment(Request $request)
    {
        $fromDate = $request->query('from_date');
        $toDate = $request->query('to_date');

        /*----------------------------------------------------
        | Step 1: Total project payment grouped by type_of_work
        ----------------------------------------------------*/
        $totalProjectPayment = EntryProcessModel::selectRaw(
            'type_of_work, COUNT(*) as count, SUM(budget) as total_budget'
        )
            ->with([
            'journalPaymentDetails',
            'paymentProcess.paymentData' => function ($query) {
                $query->select('id', 'payment_id', 'payment', 'payment_date')
                    ->where('is_deleted', 0);
            },
        ])
            ->when($fromDate, fn ($q) => $q->whereDate('entry_date', '>=', $fromDate))
            ->when($toDate, fn ($q) => $q->whereDate('entry_date', '<=', $toDate))
            ->where('is_deleted', 0)
            ->groupBy('type_of_work')
            ->get();

        /*----------------------------------------------------
        | Step 2: Project payments with details
        ----------------------------------------------------*/
        $totalProjectPayments = EntryProcessModel::with([
            'employeePaymentDetails:id,project_id,payment,type,status',
            'journalPaymentDetails:id,project_id,payment,type,status',
        ])
            ->select('id', 'project_id', 'type_of_work')
            ->when($fromDate, fn ($q) => $q->whereDate('entry_date', '>=', $fromDate))
            ->when($toDate, fn ($q) => $q->whereDate('entry_date', '<=', $toDate))
            ->where('is_deleted', 0)
            ->get();

        /*----------------------------------------------------
        | Step 3: Freelance paid / unpaid / journal paid
        ----------------------------------------------------*/
        $paidUnpaidPayments = $totalProjectPayments
            ->groupBy('type_of_work')
            ->map(function ($projects, $typeOfWork) {

                $paid = $projects->flatMap(fn ($p) => $p->employeePaymentDetails->where('status', 'paid')
                ->whereIn('type', ['writer', 'reviewer'])
                )->sum('payment');

                $unpaid = $projects->flatMap(fn ($p) => $p->employeePaymentDetails->where('status', 'pending')
                 ->whereIn('type', ['writer', 'reviewer'])
                )->sum('payment');

                $journalPaid = $projects->flatMap(fn ($p) => $p->journalPaymentDetails
                    ->where('status', 'paid')
                    ->where('type', 'publication_manager')
                )->sum('payment');

                return [
                    'type_of_work' => $typeOfWork,
                    'paid_payment' => $paid,
                    'unpaid_payment' => $unpaid,
                    'journal_paid' => $journalPaid,
                ];
            });

        /*----------------------------------------------------
        | Step 4: Payment status (amount received)
        ----------------------------------------------------*/
        $paymentStatus = PaymentStatusModel::with('paymentData', 'paymentLData', 'projectData')
            ->whereHas('projectData', function ($query) use ($fromDate, $toDate) {
                $query->where('is_deleted', 0)
                    ->when($fromDate, fn ($q) => $q->whereDate('entry_date', '>=', $fromDate))
                    ->when($toDate, fn ($q) => $q->whereDate('entry_date', '<=', $toDate));
            })
            ->orderBy('created_at', 'desc')
            ->get();

        /*----------------------------------------------------
        | Step 5: Final formatted response
        ----------------------------------------------------*/
        $formattedData = $totalProjectPayment->map(function ($projectPayment) use ($paymentStatus, $paidUnpaidPayments) {

            $typeOfWork = $projectPayment->type_of_work;
            $received = 0;

            foreach ($paymentStatus as $status) {
                if ($status->projectData?->type_of_work === $typeOfWork) {
                    $received += $status->paymentData->sum('payment');
                }
            }

            $freelanceData = $paidUnpaidPayments[$typeOfWork] ?? [
                'paid_payment' => 0,
                'unpaid_payment' => 0,
                'journal_paid' => 0,
            ];

            return [
                'type_of_project' => $typeOfWork,
                'total_project_count' => $projectPayment->count,
                'budget' => $projectPayment->total_budget,
                'amount_received' => $received,
                'amount_pending' => $projectPayment->total_budget - $received,
                'Journal_paid_amount' => $freelanceData['journal_paid'],
                'Freelance_paid' => $freelanceData['paid_payment'],
                'Freelance_unpaid' => $freelanceData['unpaid_payment'],
            ];
        });

        return response()->json([
            'details' => $formattedData,
        ]);
    }

    // Client analysis report

    public function getDepartmentReport()
    {
        $workUsage = EntryProcessModel::selectRaw(
            'department,
             COUNT(*) as total_count,
             SUM(CASE WHEN type_of_work = "Statistics" THEN 1 ELSE 0 END) as statistics_count,
             SUM(CASE WHEN type_of_work = "Manuscript" THEN 1 ELSE 0 END) as manuscript_count,
             SUM(CASE WHEN type_of_work = "Thesis" THEN 1 ELSE 0 END) as thesis_count,
             SUM(CASE WHEN type_of_work = "Others" THEN 1 ELSE 0 END) as others_count,
             SUM(CASE WHEN type_of_work = "Publication" THEN 1 ELSE 0 END) as publication_count'
        )
            ->where('is_deleted', 0)
            ->groupBy('department')
            ->distinct()
            ->get();

        $departmentIds = $workUsage->pluck('department')->unique()->toArray();

        $departments = DepartmentModel::whereIn('id', $departmentIds)->pluck('name', 'id');

        $workUsage->transform(function ($item) use ($departments) {
            return [
                'department_name' => $departments[$item->department] ?? 'Unknown',
                'total_count' => $item->total_count,
                'statistics_count' => $item->statistics_count,
                'manuscript_count' => $item->manuscript_count,
                'thesis_count' => $item->thesis_count,
                'others_count' => $item->others_count,
                'publication_count' => $item->publication_count,
            ];
        });

        return response()->json([
            'department_report' => $workUsage,
        ]);
    }

    public function getProfessionReport()
    {
        $workUsage = EntryProcessModel::selectRaw(
            'profession,
         COUNT(*) as total_count,
         SUM(CASE WHEN type_of_work = "Statistics" THEN 1 ELSE 0 END) as statistics_count,
         SUM(CASE WHEN type_of_work = "Manuscript" THEN 1 ELSE 0 END) as manuscript_count,
         SUM(CASE WHEN type_of_work = "Thesis" THEN 1 ELSE 0 END) as thesis_count,
         SUM(CASE WHEN type_of_work = "Others" THEN 1 ELSE 0 END) as others_count,
         SUM(CASE WHEN type_of_work = "Publication" THEN 1 ELSE 0 END) as publication_count'
        )
            ->where('is_deleted', 0)
            ->groupBy('profession')
            ->distinct()
            ->get();
        $professionIds = $workUsage->pluck('profession')->unique()->toArray();
        $professions = ProfessionModel::whereIn('id', $professionIds)->pluck('name', 'id');
        $workUsage->transform(function ($item) use ($professions) {
            return [
                'profession_name' => $professions[$item->profession] ?? 'Unknown',
                'total_count' => $item->total_count,
                'statistics_count' => $item->statistics_count,
                'manuscript_count' => $item->manuscript_count,
                'thesis_count' => $item->thesis_count,
                'others_count' => $item->others_count,
                'publication_count' => $item->publication_count,
            ];
        });

        return response()->json([
            'profession_report' => $workUsage,
        ]);
    }

    public function getInstituteReport()
    {
        $workUsage = EntryProcessModel::selectRaw(
            'institute,
                 COUNT(*) as total_count,
                 SUM(CASE WHEN type_of_work = "Statistics" THEN 1 ELSE 0 END) as statistics_count,
                 SUM(CASE WHEN type_of_work = "Manuscript" THEN 1 ELSE 0 END) as manuscript_count,
                 SUM(CASE WHEN type_of_work = "Thesis" THEN 1 ELSE 0 END) as thesis_count,
                 SUM(CASE WHEN type_of_work = "Others" THEN 1 ELSE 0 END) as others_count,
                 SUM(CASE WHEN type_of_work = "Publication" THEN 1 ELSE 0 END) as publication_count'
        )
            ->where('is_deleted', 0)
            ->groupBy('institute')
            ->distinct()
            ->get();
        $instituteIds = $workUsage->pluck('institute')->unique()->toArray();
        $institutes = InstitutionModel::whereIn('id', $instituteIds)->pluck('name', 'id');
        $workUsage->transform(function ($item) use ($institutes) {
            return [
                'institute_name' => $institutes[$item->institute] ?? 'Unknown',
                'total_count' => $item->total_count,
                'statistics_count' => $item->statistics_count,
                'manuscript_count' => $item->manuscript_count,
                'thesis_count' => $item->thesis_count,
                'others_count' => $item->others_count,
                'publication_count' => $item->publication_count,
            ];
        });

        return response()->json([
            'institute_report' => $workUsage,
        ]);
    }

    //client details

    public function getClientDetails()
    {
        $clients = EntryProcessModel::with([
            'institute:id,name',
            'department:id,name',
            'profession:id,name',
        ])
            ->select('id', 'client_name', 'email', 'contact_number', 'institute', 'department', 'profession')
            ->where('is_deleted', 0)
            ->get();

        $clients->load('institute', 'department', 'profession');

        Log::info('Clients:', $clients->toArray());

        $clientsArray = $clients->toArray();

        $formattedClients = collect($clientsArray)->map(function ($client, $index) {
            return [
                'client_name' => $client['client_name'],
                'email' => $client['email'] ?? 'Not Provided',
                'contact' => $client['contact_number'],
                'institute' => $client['institute']['name'] ?? 'Not Found',
                'department' => $client['department']['name'] ?? 'Not Found',
                'profession' => $client['profession']['name'] ?? 'Not Found',
            ];
        });

        $journal_type = ProjectAssignDetails::select('project_id', 'type')->get();
        $author = EntryProcessModel::with(['institute:id,name', 'department:id,name', 'profession:id,name'])
            ->select('id', 'client_name', 'email', 'contact_number', 'institute', 'department', 'profession')
            ->where('is_deleted', 0)
            ->get();
        $author->load('institute', 'department', 'profession');

        $matchedAuthors = $author->filter(function ($auth) use ($journal_type) {
            return $journal_type->contains(function ($journal) use ($auth) {
                return $journal->project_id == $auth->id && $journal->type === 'publication_manager';
            });
        });

        $matchedAuthor = $matchedAuthors->toArray();

        $formattedJournal = collect($matchedAuthor)->map(function ($client, $index) {
            return [
                'client_name' => $client['client_name'],
                'email' => $client['email'] ?? 'Not Provided',
                'contact' => $client['contact_number'],
                'institute' => $client['institute']['name'] ?? 'Not Found',
                'department' => $client['department']['name'] ?? 'Not Found',
                'profession' => $client['profession']['name'] ?? 'Not Found',
            ];
        })->values();

        return response()->json([
            'client_details' => $formattedClients,
            'Journal_Publication_details' => $formattedJournal,
        ]);
    }

    // public function yearly_process_report()
    // {
    //     // Get distinct years from the database based on 'updated_at' and 'created_at' fields
    //     $years = PaymentStatusModel::selectRaw('YEAR(updated_at) as year')
    //         ->union(EntryProcessModel::selectRaw('YEAR(created_at) as year'))
    //         ->orderBy('year', 'desc')
    //         ->pluck('year')
    //         ->toArray();

    //     if (empty($years)) {
    //         return response()->json(['message' => 'No data available']);
    //     }

    //     // Define an array of month names
    //     $months = [
    //         '01' => 'January',
    //         '02' => 'February',
    //         '03' => 'March',
    //         '04' => 'April',
    //         '05' => 'May',
    //         '06' => 'June',
    //         '07' => 'July',
    //         '08' => 'August',
    //         '09' => 'September',
    //         '10' => 'October',
    //         '11' => 'November',
    //         '12' => 'December',

    //         // 'yearly_total' => [
    //         //     'total_year' => 'yearly_total',
    //         //     'writer_reviewer_payment' => 0,
    //         //     'office_emp_salary' => 0,
    //         //     'journal_payment' => 0,
    //         //     'total_budget' => 0,
    //         //     'total_count' => 0
    //         // ]
    //     ];

    //     $response = [];

    //     // foreach ($years as $year) {
    //     //     $year_data = [
    //     //         'year' => $year,
    //     //         'months' => [],
    //     // 'yearly_total' => [
    //     //     'writer_reviewer_payment' => 0,
    //     //     'office_emp_salary' => 0,
    //     //     'journal_payment' => 0,
    //     //     'total_budget' => 0,
    //     //     'total_count' => 0
    //     // ]
    //     //     ];

    //     foreach ($years as $year) {
    //         $yearly_total = [
    //             'total_year' => 'yearly_total',
    //             'writer_reviewer_payment' => 0,
    //             'office_emp_salary' => 0,
    //             'journal_payment' => 0,
    //             'total_budget' => 0,
    //             'total_count' => 0
    //         ];

    //         $year_data = [
    //             'year' => $year,
    //             'months' => [],
    //             'yearly_total' => $yearly_total
    //         ];

    //         foreach ($months as $num => $month) {
    //             // $external_source = People::select('id')
    //             //     ->where('employee_name', '!=', 'Admin')
    //             //     ->where('employee_type', 'freelancers')
    //             //     ->whereIn('position', [7, 8, 10, 11])
    //             //     ->get();
    //             // $externalEmployeeIds = $external_source->pluck('id')->toArray();

    //             $external = EmployeePaymentDetails::whereIn('type', ['writer', 'reviewer'])
    //                 ->whereMonth('updated_at', $num)
    //                 ->whereYear('updated_at', $year)
    //                 ->sum(DB::raw('COALESCE(payment, 0)'));

    //             // $internal_source = People::select('id')
    //             //     ->where('employee_name', '!=', 'Admin')
    //             //     ->whereIn('employee_type', ['full_time', 'part_time'])
    //             //     ->whereIn('position', [7, 8, 10, 11])
    //             //     ->get();
    //             // $internalEmployeeIds = $internal_source->pluck('id')->toArray();

    //             // $internal = PaymentStatusModel::where(function ($query) use ($internalEmployeeIds) {
    //             //         $query->whereIn('writer_id', $internalEmployeeIds)
    //             //               ->orWhereIn('reviewer_id', $internalEmployeeIds);
    //             //     })
    //             //     ->whereMonth('updated_at', $num)
    //             //     ->whereYear('updated_at', $year)
    //             //     ->sum(DB::raw('COALESCE(writer_payment, 0) + COALESCE(reviewer_payment, 0)'));

    //             $journal_report = EmployeePaymentDetails::whereMonth('updated_at', $num)
    //                 ->whereYear('updated_at', $year)
    //                 ->where('type', 'publication_manager')
    //                 ->sum(DB::raw('COALESCE(payment, 0)'));

    //             $budget = EntryProcessModel::where('is_deleted', 0)
    //                 ->whereMonth('created_at', $num)
    //                 ->whereYear('created_at', $year)
    //                 ->sum('budget');

    //             $total_count = EntryProcessModel::where('is_deleted', 0)
    //                 ->whereMonth('created_at', $num)
    //                 ->whereYear('created_at', $year)
    //                 ->count();

    //             // Store monthly data
    //             $month_data = [
    //                 'month' => $month,
    //                 'writer_reviewer_payment' => $external ?? 0,
    //                 //'office_emp_salary' => $internal ?? 0,
    //                 'journal_payment' => $journal_report ?? 0,
    //                 'total_budget' => $budget ?? 0,
    //                 'total_count' => $total_count ?? 0,
    //             ];

    //             // Add monthly data to the 'months' array
    //             $year_data['months'][] = $month_data;

    //             // Accumulate yearly totals
    //             $year_data['yearly_total']['writer_reviewer_payment'] += $external ?? 0;
    //             // $year_data['yearly_total']['office_emp_salary'] += $internal ?? 0;
    //             $year_data['yearly_total']['journal_payment'] += $journal_report ?? 0;
    //             $year_data['yearly_total']['total_budget'] += $budget ?? 0;
    //             $year_data['yearly_total']['total_count'] += $total_count ?? 0;
    //         }

    //         // Add year-wise data to response
    //         $response[] = $year_data;
    //     }

    //     return response()->json($response);
    // }

    // public function yearly_process_report()
    // {
    //     // Get distinct years from DB
    //     $years = PaymentStatusModel::selectRaw('YEAR(updated_at) as year')
    //         ->union(EntryProcessModel::selectRaw('YEAR(created_at) as year'))
    //         ->orderBy('year', 'desc')
    //         ->pluck('year')
    //         ->toArray();

    //     if (empty($years)) {
    //         return response()->json(['message' => 'No data available']);
    //     }

    //     // Month list
    //     $months = [
    //         '01' => 'January',
    //         '02' => 'February',
    //         '03' => 'March',
    //         '04' => 'April',
    //         '05' => 'May',
    //         '06' => 'June',
    //         '07' => 'July',
    //         '08' => 'August',
    //         '09' => 'September',
    //         '10' => 'October',
    //         '11' => 'Nov',
    //         '12' => 'December'
    //     ];

    //     $response = [];

    //     foreach ($years as $year) {

    //         $year_data = [
    //             'year' => $year,
    //             'months' => [],
    //             'yearly_total' => [
    //                 'writer_reviewer_payment' => 0,
    //                 'office_emp_salary' => 0,
    //                 'journal_payment' => 0,
    //                 'total_budget' => 0,
    //                 'total_count' => 0,
    //             ]
    //         ];

    //         foreach ($months as $num => $month) {

    //             // Get external (writer & reviewer payments)
    //             $external = EmployeePaymentDetails::whereIn('type', ['writer', 'reviewer'])
    //                 ->whereMonth('updated_at', $num)
    //                 ->whereYear('updated_at', $year)
    //                 ->sum(DB::raw('COALESCE(payment, 0)'));

    //             // 🔥 **NEW** — Fetch office employees salary from HRMS API
    //             $office_emp_salary = 0;
    //             try {
    //                 $responseAPI = Http::get(
    //                     'https://hrmsapi.medicsresearch.com/api/emp-attendances/monthly-report',
    //                     [
    //                         'month' => $month,
    //                         'year' => $year
    //                     ]
    //                 );

    //                 if ($responseAPI->successful()) {
    //                     $data = $responseAPI->json();
    //                     $office_emp_salary = collect($data)->sum('total_salary_with_ot');
    //                 }
    //             } catch (\Exception $e) {
    //                 \Log::error('HRMS Payroll API error: ' . $e->getMessage());
    //             }

    //             // Journal manager payment
    //             $journal_report = EmployeePaymentDetails::whereMonth('updated_at', $num)
    //                 ->whereYear('updated_at', $year)
    //                 ->where('type', 'publication_manager')
    //                 ->sum(DB::raw('COALESCE(payment, 0)'));

    //             // Budget
    //             $budget = EntryProcessModel::where('is_deleted', 0)
    //                 ->whereMonth('created_at', $num)
    //                 ->whereYear('created_at', $year)
    //                 ->sum('budget');

    //             // Entry count
    //             $total_count = EntryProcessModel::where('is_deleted', 0)
    //                 ->whereMonth('created_at', $num)
    //                 ->whereYear('created_at', $year)
    //                 ->count();

    //             // Store monthly data
    //             $month_data = [
    //                 'month' => $month,
    //                 'writer_reviewer_payment' => $external ?? 0,
    //                 'office_emp_salary' => $office_emp_salary ?? 0,
    //                 'journal_payment' => $journal_report ?? 0,
    //                 'total_budget' => $budget ?? 0,
    //                 'total_count' => $total_count ?? 0,
    //             ];

    //             $year_data['months'][] = $month_data;

    //             // Accumulate totals
    //             $year_data['yearly_total']['writer_reviewer_payment'] += $external;
    //             $year_data['yearly_total']['office_emp_salary'] += $office_emp_salary;
    //             $year_data['yearly_total']['journal_payment'] += $journal_report;
    //             $year_data['yearly_total']['total_budget'] += $budget;
    //             $year_data['yearly_total']['total_count'] += $total_count;
    //         }

    //         $response[] = $year_data;
    //     }

    //     return response()->json($response);
    // }

    public function yearly_process_report()
    {
        // Get distinct years from the database based on 'updated_at' and 'created_at' fields
        $years = PaymentStatusModel::selectRaw('YEAR(updated_at) as year')
            ->union(EntryProcessModel::selectRaw('YEAR(created_at) as year'))
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();

        if (empty($years)) {
            return response()->json(['message' => 'No data available']);
        }

        // Define an array of month names
        $months = [
            '01' => 'January',
            '02' => 'February',
            '03' => 'March',
            '04' => 'April',
            '05' => 'May',
            '06' => 'June',
            '07' => 'July',
            '08' => 'August',
            '09' => 'September',
            '10' => 'October',
            '11' => 'November',
            '12' => 'December',
        ];

        $response = [];

        foreach ($years as $year) {
            $yearly_total = [
                'total_year' => 'yearly_total',
                'writer_reviewer_payment' => 0,
                'office_emp_salary' => 0,
                'journal_payment' => 0,
                'total_budget' => 0,
                'total_count' => 0,
            ];

            $year_data = [
                'year' => $year,
                'months' => [],
                'yearly_total' => $yearly_total,
            ];

            foreach ($months as $num => $month) {

                // External writer/reviewer payments
                $external = EmployeePaymentDetails::whereIn('type', ['writer', 'reviewer'])
                    ->whereMonth('updated_at', $num)
                    ->whereYear('updated_at', $year)
                    ->sum(DB::raw('COALESCE(payment, 0)'));

                // Office employee salary fetched from the HRMS API
                $officeSalary = 0;
                try {
                    $apiResponse = Http::get('https://hrmsapi.medicsresearch.com/api/emp-attendances/monthly-report', [
                        'month' => $month,
                        'year' => $year,
                    ]);

                    if ($apiResponse->successful()) {
                        $data = $apiResponse->json();
                        $officeSalary = collect($data)->sum('total_salary_with_ot');
                    }
                } catch (\Exception $e) {
                    Log::error('HRMS Payroll API error: '.$e->getMessage());
                }

                // Journal payments
                $journal_report = EmployeePaymentDetails::whereMonth('updated_at', $num)
                    ->whereYear('updated_at', $year)
                    ->where('type', 'publication_manager')
                    ->sum(DB::raw('COALESCE(payment, 0)'));

                // Budget and total count
                $budget = EntryProcessModel::where('is_deleted', 0)
                    ->whereMonth('entry_date', $num)
                    ->whereYear('entry_date', $year)
                    ->sum('budget');

                $total_count = EntryProcessModel::where('is_deleted', 0)
                    ->whereMonth('entry_date', $num)
                    ->whereYear('entry_date', $year)
                    ->count();

                // Store monthly data
                $month_data = [
                    'month' => $month,
                    'writer_reviewer_payment' => $external ?? 0,
                    'office_emp_salary' => $officeSalary ?? 0,
                    'journal_payment' => $journal_report ?? 0,
                    'total_budget' => $budget ?? 0,
                    'total_count' => $total_count ?? 0,
                ];

                // Add monthly data to the 'months' array
                $year_data['months'][] = $month_data;

                // Accumulate yearly totals
                $year_data['yearly_total']['writer_reviewer_payment'] += $external ?? 0;
                $year_data['yearly_total']['office_emp_salary'] += $officeSalary ?? 0;
                $year_data['yearly_total']['journal_payment'] += $journal_report ?? 0;
                $year_data['yearly_total']['total_budget'] += $budget ?? 0;
                $year_data['yearly_total']['total_count'] += $total_count ?? 0;
            }

            // Add year-wise data to response
            $response[] = $year_data;
        }

        return response()->json($response);
    }

    //based on the type of work for the payment report

    public function getTypeOfWorkReport(Request $request)
    {
        $years = EntryProcessModel::where('is_deleted', 0)
            ->selectRaw('YEAR(entry_date) as year')
            ->distinct()
            ->orderBy('year', 'DESC')
            ->pluck('year');

        $result = [];

        foreach ($years as $year) {
            $data = [
                ['Project_Type' => 'Manuscript', 'Count' => 0, 'Budget' => 0, 'Expense' => 0, 'Income' => 0],
                ['Project_Type' => 'Thesis', 'Count' => 0, 'Budget' => 0, 'Expense' => 0, 'Income' => 0],
                ['Project_Type' => 'Statistics', 'Count' => 0, 'Budget' => 0, 'Expense' => 0, 'Income' => 0],
                ['Project_Type' => 'Others', 'Count' => 0, 'Budget' => 0, 'Expense' => 0, 'Income' => 0],
            ];

            $totalCount = 0;
            $totalBudget = 0;
            $totalExpense = 0;
            $totalIncome = 0;

            foreach ($data as &$item) {
                $typeOfWork = $item['Project_Type'];

                $entries = EntryProcessModel::where('type_of_work', $typeOfWork)
                    ->where('is_deleted', 0)
                    ->whereYear('entry_date', $year)
                    ->get();

                $count = $entries->count();
                $budget = $entries->sum('budget');

                $entryIds = $entries->pluck('id')->toArray();

                // Filter freelancer (writer + reviewer) payments by entry_id and year
                $freelancer_payment = EmployeePaymentDetails::whereIn('type', ['writer', 'reviewer'])
                    ->whereIn('project_id', $entryIds)
                    ->whereYear('updated_at', $year)
                    ->sum(DB::raw('COALESCE(payment, 0)'));

                // Filter journal (publication_manager) payments by entry_id and year
                $journal_payment = EmployeePaymentDetails::where('type', 'publication_manager')
                    ->whereIn('project_id', $entryIds)
                    ->whereYear('updated_at', $year)
                    ->sum(DB::raw('COALESCE(payment, 0)'));

                $expense = $freelancer_payment + $journal_payment;
                $income = $budget - $expense;

                $item['Count'] = $count;
                $item['Budget'] = $budget;
                $item['Expense'] = $expense;
                $item['Income'] = $income;

                $totalCount += $count;
                $totalBudget += $budget;
                $totalExpense += $expense;
                $totalIncome += $income;
            }

            $total_data = [
                'year' => $year,
                'Project_Type' => 'Total',
                'Count' => $totalCount,
                'Budget' => $totalBudget,
                'Expense' => $totalExpense,
                'Income' => $totalIncome,
            ];

            $result[] = [
                'year' => $year,
                'data' => $data,
                'total_data' => $total_data,
            ];
        }

        return response()->json($result);
    }

    public function getTotalPayment(Request $request)
    {

        $years = PaymentStatusModel::selectRaw('YEAR(updated_at) as year')
            ->union(EntryProcessModel::selectRaw('YEAR(created_at) as year'))
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();

        // Initialize data array
        // $data = [
        //     ['Year' => '2023', 'Total_Project' => 0, 'Budget' => 0, 'Expense' => 0, 'Total_Received_Amount' => 0, 'Income' => 0, 'Income_percentage' => 0],
        //     ['Year' => '2024', 'Total_Project' => 0, 'Budget' => 0, 'Expense' => 0, 'Total_Received_Amount' => 0, 'Income' => 0, 'Income_percentage' => 0],
        //     ['Year' => '2025', 'Total_Project' => 0, 'Budget' => 0, 'Expense' => 0, 'Total_Received_Amount' => 0, 'Income' => 0, 'Income_percentage' => 0]
        // ];

        $data = [];

        foreach ($years as $year) {
            $data[] = [
                'Year' => (string) $year,
                'Total_Project' => 0,
                'Budget' => 0,
                'Expense' => 0,
                'Total_Received_Amount' => 0,
                'Income' => 0,
                'Income_percentage' => 0,
            ];
        }

        // Fetch data for each year
        foreach ($data as &$yearData) {
            $year = $yearData['Year'];

            // Fetch all entries for the given year
            $entries = EntryProcessModel::whereYear('created_at', $year)
                ->where('is_deleted', 0)
                ->get();

            // Count total projects
            $totalProjects = $entries->count();
            $totalBudget = $entries->sum('budget');

            $payment_recevied = PaymentDetails::whereYear('payment_date', $year)
                ->where('is_deleted', 0)
                ->sum(DB::raw('COALESCE(payment, 0)'));

            $freelancer_payment = EmployeePaymentDetails::whereIn('type', ['writer', 'reviewer'])

                ->whereYear('updated_at', $year)
                ->sum(DB::raw('COALESCE(payment, 0)'));

            $journal_payment = EmployeePaymentDetails::whereYear('updated_at', $year)
                ->where('type', 'publication_manager')
                ->sum(DB::raw('COALESCE(payment, 0)'));

            // Calculate total expense
            $totalExpense = $freelancer_payment + $journal_payment;

            // Calculate income
            $totalIncome = $payment_recevied - $totalExpense;

            // Calculate income percentage
            $incomePercentage = $payment_recevied > 0 ? ($totalIncome / $payment_recevied) * 100 : 0;

            // Assign calculated values to the array
            $yearData['Total_Project'] = $totalProjects;
            $yearData['Budget'] = $totalBudget;
            $yearData['Expense'] = $totalExpense;
            $yearData['Total_Received_Amount'] = $payment_recevied;
            $yearData['Income'] = $totalIncome;
            $yearData['Income_percentage'] = round($incomePercentage, 2);
        }

        return response()->json($data);
    }

    public function projectList(Request $request)
    {
        $fromDate = $request->query('from_date');
        $toDate = $request->query('to_date');
        $query = EntryProcessModel::with([
            'institute:id,name',
            'department:id,name',
            'profession:id,name',
            'writerData',
            'reviewerData',
            'statisticanData',
            'journalData',
            'paymentProcess',
            'employeePaymentDetails',
        ])
            // ->where('process_status', '!=', 'completed')
            ->select('id', 'project_id', 'department', 'institute', 'client_name', 'profession', 'title', 'type_of_work', 'process_status', 'process_date', 'budget')
            ->where('is_deleted', 0);
        if ($fromDate) {
            $query->whereDate('entry_date', '>=', $fromDate);
        }

        if ($toDate) {
            $query->whereDate('entry_date', '<=', $toDate);
        }
        if ($request->filled('id')) {
            $ids = explode(',', $request->id);
            $query->whereIn('id', $ids);
        }

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        if ($request->filled('institute')) {
            $query->whereHas('institute', function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->institute.'%');
            });
        }

        if ($request->filled('department')) {
            $query->whereHas('department', function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->department.'%');
            });
        }

        if ($request->filled('profession')) {
            $query->whereHas('profession', function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->profession.'%');
            });
        }

        if ($request->filled('payment_status')) {
            $query->whereHas('paymentProcess', function ($q) use ($request) {
                $q->where('payment_status', 'like', '%'.$request->payment_status.'%');
            });
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('process_date', [$request->start_date, $request->end_date]);
        } elseif ($request->filled('start_date') || $request->filled('end_date')) {
            $query->where('process_date', [$request->filled('start_date') ? $request->start_date : $request->end_date]);
        }

        if ($request->filled('type_of_work')) {
            $query->where('type_of_work', 'like', '%'.$request->type_of_work.'%');
        }

        if ($request->filled('process_status')) {
            $query->where('process_status', 'like', '%'.$request->process_status.'%');
        }

        $projectList = $query->get();
        $employees = DB::connection('mysql_medics_hrms')
            ->table('employee_details')
            ->where('status', '1')
            ->get()
            ->keyBy('id');

        $userhrms = DB::connection('mysql_medics_hrms')
            ->table('employee_details')
            ->where('position', '7')
            ->where('status', '1')
            ->get();

        $userIds = $userhrms->pluck('id');

        $salary = DB::connection('mysql_medics_hrms')
            ->table('salary_information')
            ->whereIn('employee_id', $userIds)
            ->get();

        $attentance = DB::connection('mysql_medics_hrms')
            ->table('emp_attendances')
            ->whereIn('emp_id', $userIds)
            ->where('reason', 'Login')
            ->get();

        $projectList = $projectList->map(function ($item) use ($employees) {
            $item->writer_data = collect($item->writerData)->map(function ($data) use ($employees) {
                $employeeName = isset($employees[$data->assign_user]) ? $employees[$data->assign_user]->employee_name : null;

                return [
                    'name' => $employeeName,
                    'status' => $data->status,
                ];
            })->toArray();

            $item->reviewer_data = collect($item->reviewerData)->map(function ($data) use ($employees) {
                $employeeName = isset($employees[$data->assign_user]) ? $employees[$data->assign_user]->employee_name : null;

                return [
                    'name' => $employeeName,
                    'status' => $data->status,
                ];
            })->toArray();

            $item->statistican_data = collect($item->statisticanData)->map(function ($data) use ($employees) {
                $employeeName = isset($employees[$data->assign_user]) ? $employees[$data->assign_user]->employee_name : null;

                return [
                    'name' => $employeeName,
                    'status' => $data->status,
                ];
            })->toArray();

            $item->journal_data = collect($item->journalData)->map(function ($data) {
                return [
                    'name' => $data->assign_user,
                    'status' => $data->status,
                ];
            })->toArray();

            $item->payment_details = $item->paymentProcess;

            $groupedPayments = collect($item->employeePaymentDetails)
                ->groupBy('type')
                ->mapWithKeys(function ($group, $type) {
                    $total = $group->sum(function ($g) {
                        return (float) $g->payment;
                    });

                    return [$type.'_fee' => $total];
                });

            foreach ($groupedPayments as $key => $val) {
                $item->$key = $val;
            }

            unset($item->writerData, $item->reviewerData, $item->statisticanData, $item->journalData, $item->paymentProcess, $item->employeePaymentDetails);

            return $item;
        });

        return response()->json($projectList);
    }

    public function projectPending(Request $request)
    {
        $fromDate = $request->query('from_date');
        $toDate = $request->query('to_date');
        $type_of_work = ['statistics', 'thesis', 'others', 'manuscript'];

        // Get People IDs
        $peopleIds_admin = People::where('position', 'Admin')->pluck('id')->filter()->values()->toArray();
        $peopleIds_pm = People::where('position', '13')->pluck('id')->filter()->values()->toArray();
        $peopleIds_sme = People::where('position', '28')->pluck('id')->filter()->values()->toArray();
        $peopleIds_pm = People::where('position', '27')->pluck('id')->filter()->values()->toArray();

        // Admin entries
        $admin_to_list = EntryProcessModel::where('is_deleted', 0)
            // ->whereIn('created_by', $peopleIds_admin)
            ->where('process_status', 'in_progress')
            ->where('process_status', '!=', 'completed')
            ->when($fromDate, fn ($q) => $q->whereDate('entry_date', '>=', $fromDate))
            ->when($toDate, fn ($q) => $q->whereDate('entry_date', '<=', $toDate))
            ->select('id', 'type_of_work', 'process_status', 'created_by')
            ->get();

        // PM entries
        $pm_to_list = EntryProcessModel::where('is_deleted', 0)
            // ->whereIn('created_by', $peopleIds_pm)
            ->whereIn('process_status', ['not_assigned'])
            // ->where('process_status', '!=', 'completed')
            ->when($fromDate, fn ($q) => $q->whereDate('entry_date', '>=', $fromDate))
            ->when($toDate, fn ($q) => $q->whereDate('entry_date', '<=', $toDate))
            ->select('id', 'type_of_work', 'process_status', 'created_by')
            ->get();

        $pm_count = $pm_to_list->count();

        $pm_by_type_of_work = array_fill_keys($type_of_work, 0);
        foreach ($pm_to_list as $item) {
            $key = $item->type_of_work ?? '';
            if (array_key_exists($key, $pm_by_type_of_work)) {
                $pm_by_type_of_work[$key]++;
            } else {
                $pm_by_type_of_work[''] = ($pm_by_type_of_work[''] ?? 0) + 1;
            }
        }
        $writer_tc_list = EntryProcessModel::with('projectStatus')
            ->whereHas('projectStatus', function ($query) {
                $query->whereIn('status', ['rejected'])
                    ->orderBy('created_at', 'desc');
            })
            ->where('is_deleted', 0)
            ->where('process_status', '!=', 'completed')
            ->when($fromDate, fn ($q) => $q->whereDate('entry_date', '>=', $fromDate))
            ->when($toDate, fn ($q) => $q->whereDate('entry_date', '<=', $toDate))
            // ->whereYear('entry_date', $currentYear)
            ->orderBy('created_at', 'desc')
            ->get();

        $writer_tc_count = $writer_tc_list->count();

        $rejected_tc_by_type_of_work = array_fill_keys($type_of_work, 0);
        foreach ($writer_tc_list as $item) {
            $key = $item->type_of_work ?? '';
            if (array_key_exists($key, $rejected_tc_by_type_of_work)) {
                $rejected_tc_by_type_of_work[$key]++;
            } else {
                $rejected_tc_by_type_of_work[''] = ($rejected_tc_by_type_of_work[''] ?? 0) + 1;
            }
        }

        $notAssigned_tc_count = ProjectAssignDetails::where('type', 'team_coordinator')
            ->whereIn('type_sme', ['writer', 'Publication Manager', 'reviewer', '2nd_writer'])
            ->where('status', 'completed')
            ->whereNotIn('status', ['need_support'])
            ->whereHas('projectData', function ($query) use ($fromDate, $toDate) {
                $query->where('is_deleted', 0)
                    ->where('process_status', '!=', 'completed')
                    ->when($fromDate, fn ($q) => $q->whereDate('entry_date', '>=', $fromDate))
                    ->when($toDate, fn ($q) => $q->whereDate('entry_date', '<=', $toDate))
                    ->whereDoesntHave('writerData', function ($sq) {
                        $sq->whereIn('status', ['correction', 'to_do', 'on_going']);
                    })
                    ->whereDoesntHave('reviewerData', function ($sq) {
                        $sq->whereIn('status', ['correction', 'to_do', 'need_support', 'revert', 'on_going']);
                    });
            })
            ->select('project_id', 'status', 'type', 'updated_at')
            ->orderBy('updated_at', 'desc')
            ->get()
            ->unique('project_id');

        $notAssigned_count = $notAssigned_tc_count->count();
        $notAssigned_by_type_of_work = array_fill_keys($type_of_work, 0);
        foreach ($notAssigned_tc_count as $item) {
            $ep = EntryProcessModel::where('id', $item->project_id)
                ->when($fromDate, fn ($q) => $q->whereDate('entry_date', '>=', $fromDate))
                ->when($toDate, fn ($q) => $q->whereDate('entry_date', '<=', $toDate))
                ->first();

            $key = $ep->type_of_work ?? '';
            if (array_key_exists($key, $notAssigned_by_type_of_work)) {
                $notAssigned_by_type_of_work[$key]++;
            } else {
                $notAssigned_by_type_of_work[''] = ($notAssigned_by_type_of_work[''] ?? 0) + 1;
            }
        }
        $entriesTask = EntryProcessModel::select(
            'id',
            'project_id',
            'type_of_work',
            'process_status',
            'hierarchy_level',
            'projectduration',
            'created_by'
        )
            ->where('is_deleted', 0)
            ->get();

        /** FIX #1 */
        $projectIdsTask = $entriesTask->pluck('project_id')->unique()->toArray();

        $writerCompletedProjects = ProjectAssignDetails::with([
            'projectData:id,project_id,type_of_work,process_status,hierarchy_level,created_at',
        ])
            ->where('status', 'completed')
            ->where('type', 'writer')
            ->whereIn('project_id', $projectIdsTask)
            ->whereHas('projectData', function ($query) use ($fromDate, $toDate) {
                $query->where('is_deleted', 0)
                    ->where('process_status', '!=', 'completed')
                    ->when($fromDate, fn ($q) => $q->whereDate('entry_date', '>=', $fromDate))
                    ->when($toDate, fn ($q) => $q->whereDate('entry_date', '<=', $toDate))
                    ->whereDoesntHave('projectAcceptStatust', function ($sq) {
                        $sq->where('status', 'rejected');
                    })
                    ->whereDoesntHave('writerData', function ($sq) {
                        $sq->whereIn('status', ['to_do', 'on_going']);
                    })
                    ->whereDoesntHave('reviewerData', function ($sq) {
                        $sq->whereIn('status', ['to_do', 'on_going', 'correction']);
                    });
            })
            ->select('project_id', 'status', 'type', 'updated_at')
            ->orderBy('created_at', 'desc')
            ->get();

        $allWriterProjects = $writerCompletedProjects->unique('project_id')->values();
        $writerProjectIds = $allWriterProjects->pluck('project_id')->toArray();

        $reviewerProjects = ProjectAssignDetails::where('type', 'reviewer')
            ->whereIn('project_id', $writerProjectIds)
            ->pluck('project_id')
            ->unique()
            ->toArray();

        $writerWithoutReviewer = $allWriterProjects->filter(function ($writer) use ($reviewerProjects) {
            return ! in_array($writer->project_id, $reviewerProjects);
        })
            ->unique('project_id')
            ->sortByDesc('updated_at')
            ->values();

        $writerWithoutReviewer_count = $writerWithoutReviewer->count();

        $entryMap = EntryProcessModel::whereIn(
            'project_id',
            $writerWithoutReviewer->pluck('project_id')
        )
            ->pluck('type_of_work', 'project_id')
            ->toArray();

        $writerWithoutReviewer_by_type_of_work = [];

        foreach ($writerWithoutReviewer as $item) {
            $key = $entryMap[$item->project_id] ?? 'unknown';
            $writerWithoutReviewer_by_type_of_work[$key] =
                ($writerWithoutReviewer_by_type_of_work[$key] ?? 0) + 1;
        }

        // SME entries
        $sme_to_list = ProjectAssignDetails::with('projectData')
            ->whereHas('projectData', function ($query) {
                $query->where('is_deleted', 0)
                    // ->whereDate('entry_date', '>=', $fromDate)
                    // ->whereDate('entry_date', '<=', $toDate)
                    ->where('process_status', '!=', 'completed')

                    ->whereHas('writerData', function ($wq) {
                        $wq->whereIn('status', ['rejected', 'revert']);

                    })

                    ->whereHas('reviewerData', function ($wq) {
                        $wq->whereIn('status', ['rejected', 'revert']);
                    })

                    ->whereHas('statisticanData', function ($wq) {
                        $wq->whereIn('status', ['rejected', 'revert']);
                    })

                    ->whereHas('tcData', function ($wq) {
                        $wq->where('type', 'team_coordinator')
                            ->whereIn('type_sme', [
                                'writer',
                                'Publication Manager',
                                'reviewer',
                                '2nd_writer',
                            ]);
                    });
            })
            ->get()
            ->unique('project_id')
            ->values();

        // dd($sme_to_list);

        $tc_merged = $pm_to_list->merge(
            $sme_to_list->map(function ($item) use ($fromDate, $toDate) {
                $ep = EntryProcessModel::where('project_id', $item->project_id)
                    ->when($fromDate, fn ($q) => $q->whereDate('entry_date', '>=', $fromDate))
                    ->when($toDate, fn ($q) => $q->whereDate('entry_date', '<=', $toDate))

                    ->first();
                if ($ep) {
                    $item->type_of_work = $ep->type_of_work;
                } else {
                    $item->type_of_work = '';
                }

                return $item;
            })
        );

        $tc_total_count = $tc_merged->count();

        $tc_by_type_of_work = array_fill_keys($type_of_work, 0);
        foreach ($tc_merged as $item) {
            $key = $item->type_of_work ?? '';
            if (array_key_exists($key, $tc_by_type_of_work)) {
                $tc_by_type_of_work[$key]++;
            } else {
                $tc_by_type_of_work[''] = ($tc_by_type_of_work[''] ?? 0) + 1;
            }
        }

        // Statistics
        $statisticsStatus = ['to_do', 'client_review', 'correction'];
        $pending_statistics = ProjectAssignDetails::where('type', 'statistican')
            ->whereIn('status', $statisticsStatus)
            ->where('status', '!=', 'completed')
            ->whereHas('projectData', function ($query) use ($fromDate, $toDate) {
                $query->where('is_deleted', 0)
                    ->when($fromDate, fn ($q) => $q->whereDate('entry_date', '>=', $fromDate))
                    ->when($toDate, fn ($q) => $q->whereDate('entry_date', '<=', $toDate))
                    ->where('process_status', '!=', 'completed');
            })
            ->get();
        $statistican_count = $pending_statistics->count();
        $statistics_by_type_of_work = array_fill_keys($type_of_work, 0);
        foreach ($pending_statistics as $item) {
            $ep = EntryProcessModel::where('id', $item->project_id)->first();
            $key = $ep->type_of_work ?? '';
            if (array_key_exists($key, $statistics_by_type_of_work)) {
                $statistics_by_type_of_work[$key]++;
            } else {
                $statistics_by_type_of_work[''] = ($statistics_by_type_of_work[''] ?? 0) + 1;
            }
        }

        // Writer
        $writerStatus = ['to_do', 'plag_correction', 'correction'];
        $pending_writer = ProjectAssignDetails::where('type', 'writer')
            ->whereIn('status', $writerStatus)
            ->where('status', '!=', 'completed')
            ->whereHas('projectData', function ($query) use ($fromDate, $toDate) {
                $query->where('is_deleted', 0)
                    ->when($fromDate, fn ($q) => $q->whereDate('entry_date', '>=', $fromDate))
                    ->when($toDate, fn ($q) => $q->whereDate('entry_date', '<=', $toDate))
                    ->where('process_status', '!=', 'completed');
            })
            ->get();

        $writer_count = $pending_writer->count();

        $writer_by_type_of_work = array_fill_keys($type_of_work, 0);
        foreach ($pending_writer as $item) {
            $ep = EntryProcessModel::where('id', $item->project_id)->first();
            $key = $ep->type_of_work ?? '';
            if (array_key_exists($key, $writer_by_type_of_work)) {
                $writer_by_type_of_work[$key]++;
            } else {
                $writer_by_type_of_work[''] = ($writer_by_type_of_work[''] ?? 0) + 1;
            }
        }

        // Reviewer
        // $reviewerStatus = ['to_do', 'plag_correction', 'correction'];
        $pending_reviewer = ProjectAssignDetails::where('type', 'reviewer')
            // ->whereIn('status', $reviewerStatus)
            ->where('status', '!=', 'completed')
            ->whereHas('projectData', function ($query) use ($fromDate, $toDate) {
                $query->where('is_deleted', 0)
                    ->when($fromDate, fn ($q) => $q->whereDate('entry_date', '>=', $fromDate))
                    ->when($toDate, fn ($q) => $q->whereDate('entry_date', '<=', $toDate))
                    ->where('process_status', '!=', 'completed');
            })
            ->get();

        $reviewer_count = $pending_reviewer->count();

        $reviewer_by_type_of_work = array_fill_keys($type_of_work, 0);
        foreach ($pending_reviewer as $item) {
            $ep = EntryProcessModel::where('id', $item->project_id)->first();
            $key = $ep->type_of_work ?? '';
            if (array_key_exists($key, $reviewer_by_type_of_work)) {
                $reviewer_by_type_of_work[$key]++;
            } else {
                $reviewer_by_type_of_work[''] = ($reviewer_by_type_of_work[''] ?? 0) + 1;
            }
        }

        // Author
        $pending_author = EntryProcessModel::where('is_deleted', 0)
            ->where('process_status', 'pending_author')
            ->when($fromDate, fn ($q) => $q->whereDate('entry_date', '>=', $fromDate))
            ->when($toDate, fn ($q) => $q->whereDate('entry_date', '<=', $toDate))
            ->get();

        $author_count = $pending_author->count();

        $author_by_type_of_work = array_fill_keys($type_of_work, 0);
        foreach ($pending_author as $item) {
            $key = $item->type_of_work ?? '';
            if (array_key_exists($key, $author_by_type_of_work)) {
                $author_by_type_of_work[$key]++;
            } else {
                $author_by_type_of_work[''] = ($author_by_type_of_work[''] ?? 0) + 1;
            }
        }

        // SME
        // $supportStatus = ['need_support', 'completed','submit_to_journal', 'pending_author', 'resubmission', 'reviewer_comments'];
        $pending_sme = ProjectAssignDetails::whereIn('type', ['writer', 'reviewer', 'statistican'])
            ->whereIn('status', ['completed', 'need_support'])
            ->whereHas('projectData', function ($query) use ($peopleIds_pm, $fromDate, $toDate) {
                $query->where('is_deleted', 0)
                    ->where('process_status', '!=', 'completed')
                    ->when($fromDate, fn ($q) => $q->whereDate('entry_date', '>=', $fromDate))
                    ->when($toDate, fn ($q) => $q->whereDate('entry_date', '<=', $toDate))
                    ->whereHas('journalData', function ($wq) use ($peopleIds_pm) {
                        $wq->whereIn('status', [
                            'submit_to_journal',
                            'pending_author',
                            'resubmission',
                            'reviewer_comments',
                        ])
                            ->where('created_by', $peopleIds_pm);
                    });
            })
            ->get()
            ->unique('project_id')
            ->values();

        $sme_count = $pending_sme->count();

        $sme_by_type_of_work = array_fill_keys($type_of_work, 0);
        foreach ($pending_sme as $item) {
            $ep = EntryProcessModel::where('id', $item->project_id)->first();
            $key = $ep->type_of_work ?? '';
            if (array_key_exists($key, $sme_by_type_of_work)) {
                $sme_by_type_of_work[$key]++;
            } else {
                $sme_by_type_of_work[''] = ($sme_by_type_of_work[''] ?? 0) + 1;
            }
        }
        $entries = EntryProcessModel::select('id', 'type_of_work', 'project_id', 'process_status', 'hierarchy_level', 'projectduration', 'created_by')
            ->where('is_deleted', 0)
            ->get();

        $totalproject = $entries->count();
        $projectids = $entries->pluck('id')->toArray();
        $reviewerist_sme = ProjectAssignDetails::with(['documents', 'projectData'])
            ->whereIn('project_id', $projectids)
            ->where('type', 'reviewer')
            ->whereIn('status', ['need_support', 'completed'])
            ->where(function ($q) use ($fromDate, $toDate) {
                $q->where('status', 'need_support')
                    ->orWhereHas('projectData', function ($query) use ($fromDate, $toDate) {
                        $query->whereNotIn('process_status', ['completed', 'client_review', 'pending_author', 'withdrawal'])
                            ->when($fromDate, fn ($q) => $q->whereDate('entry_date', '>=', $fromDate))
                            ->when($toDate, fn ($q) => $q->whereDate('entry_date', '<=', $toDate))
                            ->whereDoesntHave('writerData', function ($wq) {
                                $wq->whereIn('status', ['to_do', 'correction', 'need_support']);
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
                                    ->whereIn('type_sme', ['writer', 'Publication Manager', 'reviewer', '2nd_writer']);
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

        $reviewerist_sme_count = $reviewerist_sme->count();

        $reviewerist_sme_by_type_of_work = array_fill_keys($type_of_work, 0);
        foreach ($reviewerist_sme as $item) {
            $ep = EntryProcessModel::where('id', $item->project_id)->first();
            $key = $ep->type_of_work ?? '';
            if (array_key_exists($key, $reviewerist_sme_by_type_of_work)) {
                $reviewerist_sme_by_type_of_work[$key]++;
            } else {
                $reviewerist_sme_by_type_of_work[''] = ($reviewerist_sme_by_type_of_work[''] ?? 0) + 1;
            }
        }

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
            ->whereHas('projectData', function ($query) use ($fromDate, $toDate) {
                $query->where('process_status', '!=', 'completed')
                    ->when($fromDate, fn ($q) => $q->whereDate('entry_date', '>=', $fromDate))
                    ->when($toDate, fn ($q) => $q->whereDate('entry_date', '<=', $toDate));
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

        $writerList_sme_count = $writerList->count();

        $writerList_sme_by_type_of_work = array_fill_keys($type_of_work, 0);
        foreach ($writerList as $item) {
            $ep = EntryProcessModel::where('id', $item->project_id)->first();
            $key = $ep->type_of_work ?? '';
            if (array_key_exists($key, $writerList_sme_by_type_of_work)) {
                $writerList_sme_by_type_of_work[$key]++;
            } else {
                $writerList_sme_by_type_of_work[''] = ($writerList_sme_by_type_of_work[''] ?? 0) + 1;
            }
        }

        $statisticanlist = ProjectAssignDetails::with(['documents', 'projectData'])
            ->whereIn('project_id', $projectids)
            ->where('type', 'statistican')
            ->orderBy('created_at', 'desc')
            ->where(function ($q) use ($fromDate, $toDate) {
                $q->whereIn('status', ['need_support', 'completed'])
                    ->whereHas('projectData', function ($query) use ($fromDate, $toDate) {
                        $query->whereNotIn('process_status', ['completed', 'client_review', 'pending_author', 'withdrawal'])
                            ->when($fromDate, fn ($q) => $q->whereDate('entry_date', '>=', $fromDate))
                            ->when($toDate, fn ($q) => $q->whereDate('entry_date', '<=', $toDate))
                            ->whereDoesntHave('writerData', function ($wq) {
                                $wq->whereIn('status', ['to_do', 'correction', 'need_support']);
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

        $statisticanlist_sme_count = $statisticanlist->count();

        $statisticanlist_sme_by_type_of_work = array_fill_keys($type_of_work, 0);
        foreach ($statisticanlist as $item) {
            $ep = EntryProcessModel::where('id', $item->project_id)->first();
            $key = $ep->type_of_work ?? '';
            if (array_key_exists($key, $statisticanlist_sme_by_type_of_work)) {
                $statisticanlist_sme_by_type_of_work[$key]++;
            } else {
                $statisticanlist_sme_by_type_of_work[''] = ($statisticanlist_sme_by_type_of_work[''] ?? 0) + 1;
            }
        }

        $smelist = ProjectAssignDetails::with(['projectData'])
            ->whereIn('project_id', $projectids)
            ->where('type', 'sme')
            ->where('status', 'need_support')
            ->whereHas('projectData', function ($query) use ($fromDate, $toDate) {
                $query->where('process_status', '!=', 'completed')
                    ->when($fromDate, fn ($q) => $q->whereDate('entry_date', '>=', $fromDate))
                    ->when($toDate, fn ($q) => $q->whereDate('entry_date', '<=', $toDate))

                    ->whereDoesntHave('tcData', function ($sq) {
                        $sq->where('status', 'correction')
                            ->where('type', 'team_coordinator');
                    });
            })
            ->orderBy('created_at', 'desc')
            ->get()
            ->unique('project_id')
            ->values();

        $smelist_sme_count = $smelist->count();

        $smelist_sme_by_type_of_work = array_fill_keys($type_of_work, 0);
        foreach ($smelist as $item) {
            $ep = EntryProcessModel::where('id', $item->project_id)->first();
            $key = $ep->type_of_work ?? '';
            if (array_key_exists($key, $smelist_sme_by_type_of_work)) {
                $smelist_sme_by_type_of_work[$key]++;
            } else {
                $smelist_sme_by_type_of_work[''] = ($smelist_sme_by_type_of_work[''] ?? 0) + 1;
            }
        }

        $publication_list = ProjectAssignDetails::with(['projectData'])
            ->whereIn('created_by', $peopleIds_pm)
            ->whereIn('project_id', $projectids)
            ->where('type', 'publication_manager')
            ->orderBy('created_at', 'desc')
            ->whereIn('status', ['pending_author', 'rejected', 'reviewer_comments', 'resubmission', 'published', 'submitted'])
            ->whereHas('projectData', function ($query) use ($fromDate, $toDate) {
                $query->whereNotIn('process_status', ['completed', 'client_review', 'pending_author', 'withdrawal'])
                    ->when($fromDate, fn ($q) => $q->whereDate('entry_date', '>=', $fromDate))
                    ->when($toDate, fn ($q) => $q->whereDate('entry_date', '<=', $toDate))
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

        $publication_list_count = $publication_list->count();

        $publication_list_by_type_of_work = array_fill_keys($type_of_work, 0);
        foreach ($publication_list as $item) {
            $ep = EntryProcessModel::where('id', $item->project_id)->first();
            $key = $ep->type_of_work ?? '';
            if (array_key_exists($key, $publication_list_by_type_of_work)) {
                $publication_list_by_type_of_work[$key]++;
            } else {
                $publication_list_by_type_of_work[''] = ($publication_list_by_type_of_work[''] ?? 0) + 1;
            }
        }

        // Publication
        $publicationStatus = ['submit_to_journal', 'pending_author', 'rejected', 'withdrawal', 'resubmission', 'reviewer_comments'];
        $pending_publication = ProjectAssignDetails::where('type', 'publication_manager')
            ->whereIn('status', $publicationStatus)
            ->whereIn('created_by', $peopleIds_sme)
            ->whereHas('projectData', function ($query) use ($fromDate, $toDate) {
                $query->where('is_deleted', 0)
                    ->where('process_status', '!=', 'completed')
                    ->when($fromDate, fn ($q) => $q->whereDate('entry_date', '>=', $fromDate))
                    ->when($toDate, fn ($q) => $q->whereDate('entry_date', '<=', $toDate));
            })
            ->get()
            ->unique('project_id')
            ->values();

        $publication_count = $pending_publication->count();

        $publication_by_type_of_work = array_fill_keys($type_of_work, 0);
        foreach ($pending_publication as $item) {
            $ep = EntryProcessModel::where('id', $item->project_id)->first();
            $key = $ep->type_of_work ?? '';
            if (array_key_exists($key, $publication_by_type_of_work)) {
                $publication_by_type_of_work[$key]++;
            } else {
                $publication_by_type_of_work[''] = ($publication_by_type_of_work[''] ?? 0) + 1;
            }
        }

        //TC Dashboard

        $entries = EntryProcessModel::select(
            'id',
            'type_of_work',
            'project_id',
            'process_status',
            'hierarchy_level',
            'projectduration',
            'created_by'
        )
            ->where('is_deleted', 0)
           // ->whereYear('entry_date', $currentYear)
            ->when($fromDate, fn ($q) => $q->whereDate('entry_date', '>=', $fromDate))
            ->when($toDate, fn ($q) => $q->whereDate('entry_date', '<=', $toDate))
            ->get();
        $entriesTask = EntryProcessModel::select(
            'id',
            'type_of_work',
            'project_id',
            'process_status',
            'hierarchy_level',
            'projectduration',
            'created_by'
        )
            ->where('is_deleted', 0)
            // ->whereYear('entry_date', $currentYear)
            // ->whereRaw("DATE_FORMAT(entry_date, '%Y-%m') = ?", [$selectedMonth])
            ->get();

        $projectIds = $entries->pluck('id')->unique()->toArray();
        $projectIdsTask = $entriesTask->pluck('id')->unique()->toArray();

        $statisticianWithoutWriter = ProjectAssignDetails::with([
            'projectData:id,project_id,type_of_work,process_status,hierarchy_level,created_at',
        ])
            ->where('type', 'team_coordinator')
            ->whereIn('type_sme', ['writer', 'Publication Manager', 'reviewer', '2nd_writer'])
            ->where('status', 'completed')
            ->whereNotIn('status', ['need_support'])
            ->whereHas('projectData', function ($query) {
                $query->where('is_deleted', 0)
                    ->where('process_status', '!=', 'completed')
                    ->whereDoesntHave('writerData', function ($sq) {
                        $sq->whereIn('status', ['correction', 'to_do', 'on_going']);
                    })
                    ->whereDoesntHave('reviewerData', function ($sq) {
                        $sq->whereIn('status', ['correction', 'to_do', 'need_support', 'revert', 'on_going']);
                    });
            })
            ->select('project_id', 'status', 'type', 'updated_at')
            ->orderBy('updated_at', 'desc')
            ->get()
            ->unique('project_id');

        // 9. Writer Completed Projects
        $writerCompletedProjects = ProjectAssignDetails::with([
            'projectData:id,project_id,type_of_work,process_status,hierarchy_level,created_at',
        ])
            ->whereIn('status', ['completed'])
            ->where('type', 'writer')
            ->whereIn('project_id', $projectIdsTask)
            ->whereHas('projectData', function ($query) {
                $query->where('is_deleted', 0)
                    ->where('process_status', '!=', 'completed')
                    ->whereDoesntHave('projectAcceptStatust', function ($sq) {
                        $sq->where('status', 'rejected');
                    })
                    ->whereDoesntHave('writerData', function ($sq) {
                        $sq->whereIn('status', ['to_do', 'on_going']);
                    })
                    ->whereDoesntHave('reviewerData', function ($sq) {
                        $sq->whereIn('status', ['to_do', 'on_going', 'correction']);
                    });
            })
            ->select('project_id', 'status', 'type', 'updated_at')
            ->orderBy('created_at', 'desc')
            ->get();

        $allWriterProjects = $writerCompletedProjects->unique('project_id')->values();
        $writerProjectIds = $allWriterProjects->pluck('project_id')->unique()->toArray();

        $reviewerProjects = ProjectAssignDetails::where('type', 'reviewer')
            ->whereIn('project_id', $writerProjectIds)
            ->pluck('project_id')
            ->unique()
            ->toArray();

        $writerWithoutReviewer = $allWriterProjects->filter(function ($writer) use ($reviewerProjects) {
            $typeOfWork = $writer->projectData->type_of_work ?? null;
            $projectId = $writer->project_id;

            // Count completed writers
            $writerCount = ProjectAssignDetails::where('project_id', $projectId)
                ->where('type', 'writer')
                ->where('status', 'completed')
                ->count();

            // if ($typeOfWork === 'thesis' && $writerCount === 2) {
            //     // Count reviewers assigned
            //     $reviewerCount = ProjectAssignDetails::where('project_id', $projectId)
            //         ->where('type', 'reviewer')
            //         ->count();

            //     return $reviewerCount < 2;
            // }

            return ! in_array($projectId, $reviewerProjects);
        })
            ->unique('project_id')
            ->sortByDesc('updated_at')
            ->values();

        $revert_writer = collect()
            ->merge($writerWithoutReviewer)
            ->merge($statisticianWithoutWriter)
            // ->merge($notAssignedProjects)
            ->sortByDesc('updated_at')
            ->unique('project_id')
            ->values();

        $revert_writer_count = $revert_writer->count();

        $revert_writer_by_type_of_work = array_fill_keys($type_of_work, 0);
        foreach ($revert_writer as $item) {
            $ep = EntryProcessModel::where('id', $item->project_id)->first();
            $key = $ep->type_of_work ?? '';
            if (array_key_exists($key, $revert_writer_by_type_of_work)) {
                $revert_writer_by_type_of_work[$key]++;
            } else {
                $revert_writer_by_type_of_work[''] = ($revert_writer_by_type_of_work[''] ?? 0) + 1;
            }
        }

        $notAssignedProjects = EntryProcessModel::where('process_status', 'not_assigned')
            ->select('id', 'project_id', 'type_of_work', 'process_status', 'hierarchy_level', 'created_at')
            ->where('is_deleted', 0)
            ->orderBy('updated_at', 'desc')
            ->get();

        $notAssignedProjects_count = $notAssignedProjects->count();

        $notAssignedProjects_tc_by_type_of_work = array_fill_keys($type_of_work, 0);
        foreach ($notAssignedProjects as $item) {
            $key = $item->type_of_work ?? '';
            if (array_key_exists($key, $notAssignedProjects_tc_by_type_of_work)) {
                $notAssignedProjects_tc_by_type_of_work[$key]++;
            } else {
                $notAssignedProjects_tc_by_type_of_work[''] = ($notAssignedProjects_tc_by_type_of_work[''] ?? 0) + 1;
            }
        }

        $revertdetails = ProjectAssignDetails::with([
            'projectData.writerData',
            'projectData.reviewerData',
            'projectData.statisticanData',
            'projectData.tcData',
        ])
            ->whereIn('project_id', $projectIdsTask)
            ->where('status', 'revert')
            ->orderBy('updated_at', 'desc')
            ->whereHas('projectData', function ($query) {
                $query->where('is_deleted', 0)
                    ->where('process_status', '!=', 'completed')
                    ->whereDoesntHave('projectAcceptStatust', function ($sq) {
                        $sq->where('status', 'rejected');
                    })
                    ->whereDoesntHave('writerData', function ($sq) {
                        $sq->whereIn('status', ['to_do', 'on_going']);
                    })
                    ->whereDoesntHave('reviewerData', function ($sq) {
                        $sq->whereIn('status', ['to_do', 'on_going', 'correction']);
                    })
                    ->whereDoesntHave('statisticanData', function ($sq) {
                        $sq->whereIn('status', ['to_do', 'on_going']);
                    });
            })
            ->get()
            ->unique('project_id');

        $revertdetails_count = $revertdetails->count();

        $revertdetails_by_type_of_work = array_fill_keys($type_of_work, 0);
        foreach ($revertdetails as $item) {
            $ep = EntryProcessModel::where('id', $item->project_id)->first();
            $key = $ep->type_of_work ?? '';
            if (array_key_exists($key, $revertdetails_by_type_of_work)) {
                $revertdetails_by_type_of_work[$key]++;
            } else {
                $revertdetails_by_type_of_work[''] = ($revertdetails_by_type_of_work[''] ?? 0) + 1;
            }
        }

        //tc Das TODO

        $tc_to_do = ProjectAssignDetails::with([
            'projectData.writerData',
            'projectData.reviewerData',
            'projectData.statisticanData',
            'projectData.tcData',
        ])
            ->where('status', 'correction')
            ->where('type', 'team_coordinator')
            ->orderBy('updated_at', 'desc')
            ->whereHas('projectData', function ($q) {
                $q->where('process_status', '!=', 'completed')
                    ->where('is_deleted', 0);

                // Writer condition based on type_of_work
                $q->where(function ($innerQ) {
                    $innerQ->where(function ($subInnerQ) {
                        // Non-thesis → block to_do & on_going
                        $subInnerQ->where('type_of_work', '!=', 'thesis')
                            ->whereDoesntHave('writerData', function ($subQ) {
                                $subQ->whereIn('status', [
                                    'to_do',
                                    'on_going',
                                    'correction',
                                    'plag_correction',
                                    'rejected',
                                    'revert',
                                    'need_support',
                                ]);
                            });
                    })
                        ->orWhere(function ($subInnerQ) {
                            // Thesis → only block correction, plag_correction, rejected, revert
                            $subInnerQ->where('type_of_work', 'thesis')
                                ->whereDoesntHave('writerData', function ($subQ) {
                                    $subQ->whereIn('status', [
                                        'correction',
                                        'plag_correction',
                                        'rejected',
                                        'revert',
                                        'need_support',
                                    ]);
                                });
                        });
                });

                // Reviewer condition based on type_of_work
                $q->where(function ($innerQ) {
                    $innerQ->where(function ($subInnerQ) {
                        // Non-thesis → block to_do & on_going
                        $subInnerQ->where('type_of_work', '!=', 'thesis')
                            ->whereDoesntHave('reviewerData', function ($subQ) {
                                $subQ->whereIn('status', [
                                    'to_do',
                                    'on_going',
                                    'correction',
                                    'plag_correction',
                                    'rejected',
                                    'revert',
                                    'need_support',
                                ]);
                            });
                    })
                        ->orWhere(function ($subInnerQ) {
                            // Thesis → only block correction, plag_correction, rejected, revert
                            $subInnerQ->where('type_of_work', 'thesis')
                                ->whereDoesntHave('reviewerData', function ($subQ) {
                                    $subQ->whereIn('status', [
                                        'correction',
                                        'plag_correction',
                                        'rejected',
                                        'revert',
                                        'need_support',
                                    ]);
                                });
                        });
                });

                // Statistician condition
                $q->whereDoesntHave('statisticanData', function ($subQ) {
                    $subQ->whereIn('status', [
                        'to_do',
                        'on_going',
                        'correction',
                        'plag_correction',
                        'rejected',
                        'revert',
                        'need_support',
                    ]);
                });

                // Project acceptance status
                $q->whereDoesntHave('projectAcceptStatust', function ($sq) {
                    $sq->where('status', 'rejected');
                });

                // SME data condition
                $q->whereDoesntHave('smeData', function ($subQ) {
                    $subQ->where('status', 'need_support');
                });
            })
            ->get()
            ->unique('project_id')
            ->filter(function ($row) {
                if ($row->projectData->tcData->isNotEmpty()) {
                    return true;
                }

                $writerStatus = optional($row->projectData->writerData->first())->status;
                $reviewerStatus = optional($row->projectData->reviewerData->first())->status;
                $statisticianStatus = optional($row->projectData->statisticanData->first())->status;

                return ! ($writerStatus === 'completed' &&
                    $reviewerStatus === 'completed' &&
                    $statisticianStatus === 'completed');
            })
            ->values();

        $peopleIds_sme = People::where('position', '13')
            ->pluck('id')
            ->filter()
            ->values()
            ->toArray();

        $projectAssignDetails = ProjectAssignDetails::pluck('project_id')->unique();

        // 5. TC Todo List
        $tcTodoListQuery = EntryProcessModel::with([
            'userData',
            'writerData',
            'reviewerData',
            'statisticanData',
            'journalData',
        ])
            ->where('process_status', 'in_progress')
            // ->whereYear('entry_date', $currentYear)
            ->where('is_deleted', 0)
            ->where('process_status', '!=', 'completed')
            ->whereIn('created_by', $peopleIds_sme)
            ->limit(100);

        if ($projectAssignDetails->isNotEmpty()) {
            $tcTodoListQuery->whereNotIn('id', $projectAssignDetails);
        }

        $tcTodoList = $tcTodoListQuery->orderBy('id', 'desc')->get();

        // 6. Admin Todo List
        $adminTodoListQuery = EntryProcessModel::with([
            'userData',
            'writerData',
            'reviewerData',
            'statisticanData',
            'journalData',
        ])
            ->where('process_status', 'in_progress')
            // ->whereYear('entry_date', $currentYear)
            ->where('is_deleted', 0)
            ->where('process_status', '!=', 'completed')
            ->where('created_by', 9);

        if ($projectAssignDetails->isNotEmpty()) {
            $adminTodoListQuery->whereNotIn('id', $projectAssignDetails);
        }

        $adminTodoList = $adminTodoListQuery->orderBy('id', 'desc')->get();

        // 7. Merge and process todo items
        $todoItems = collect($tcTodoList)
            ->merge(
                collect($adminTodoList)
                    ->filter(function ($item) {
                        return ! empty($item->writerData) ||
                            ! empty($item->reviewerData) ||
                            ! empty($item->statisticanData) ||
                            ! empty($item->journalData);
                    })
            )
            ->merge($tc_to_do)
            ->sortByDesc('updated_at')
            ->map(function ($item) {
                $hasAnyRole = (isset($item->writerData) && $item->writerData->isEmpty()) ||
                    (isset($item->reviewerData) && $item->reviewerData->isEmpty()) ||
                    (isset($item->statisticanData) && $item->statisticanData->isEmpty()) ||
                    (isset($item->journalData) && $item->journalData->isEmpty());

                return [
                    'id' => $item->id ?? null,
                    'project_id' => $item->project_id ?? null,
                    'type_of_work' => $item->projectData->type_of_work ?? null,
                    'project_ids' => $item->projectData->project_id ?? null,
                    'hierarchy_level' => $item->hierarchy_level ?? null,
                    'hierarchy_levels' => $item->projectData->hierarchy_level ?? null,
                    'process_status' => $item->process_status ?? null,
                    'process_statuses' => $item->projectData->process_status ?? null,
                    'created_by' => $item->created_by ?? null,
                    'has_role' => $hasAnyRole,
                ];
            })
            ->values();

        // dd($todoItems);

        $todoItems_count = $todoItems->count();

        // $todoItems_by_type_of_work = array_fill_keys($type_of_work, 0);
        // foreach ($todoItems as $item) {
        //     $ep = EntryProcessModel::where('id', $item->project_id)->first();
        //     $key = $ep->type_of_work ?? '';
        //     if (array_key_exists($key, $todoItems_by_type_of_work)) {
        //         $todoItems_by_type_of_work[$key]++;
        //     } else {
        //         $todoItems_by_type_of_work[''] = ($todoItems_by_type_of_work[''] ?? 0) + 1;
        //     }
        // }

        $todoItems_by_type_of_work = array_fill_keys($type_of_work, 0);

        foreach ($todoItems as $item) {
            // Access as array, not object
            $key = $item['type_of_work'] ?? '';

            if (array_key_exists($key, $todoItems_by_type_of_work)) {
                $todoItems_by_type_of_work[$key]++;
            } else {
                // Count items with missing / unknown type_of_work
                $todoItems_by_type_of_work[''] = ($todoItems_by_type_of_work[''] ?? 0) + 1;
            }
        }

        $finalResponse = [];
        foreach ($type_of_work as $index => $type) {
            $total_sme =
    ($reviewerist_sme_by_type_of_work[$type] ?? 0) +
    ($writerList_sme_by_type_of_work[$type] ?? 0) +
    ($statisticanlist_sme_by_type_of_work[$type] ?? 0) +
    ($smelist_sme_by_type_of_work[$type] ?? 0) +
    ($publication_list_by_type_of_work[$type] ?? 0);

            $total_tc = ($todoItems_by_type_of_work[$type] ?? 0) + ($revert_writer_by_type_of_work[$type] ?? 0)
            + ($notAssignedProjects_tc_by_type_of_work[$type] ?? 0) + ($revertdetails_by_type_of_work[$type] ?? 0)
            + ($rejected_tc_by_type_of_work[$type] ?? 0);
            $finalResponse[$index] = [

                'type_of_work' => $type,
                // 'tc' => $tc_by_type_of_work[$type] ?? 0,
                'pending_statistics' => $statistics_by_type_of_work[$type] ?? 0,
                'pending_writer' => $writer_by_type_of_work[$type] ?? 0,
                'pending_reviewer' => $reviewer_by_type_of_work[$type] ?? 0,
                'pending_author' => $author_by_type_of_work[$type] ?? 0,
                // 'pending_sme' => $sme_by_type_of_work[$type] ?? 0,
                // 'reviewerist_sme'=> $reviewerist_sme_by_type_of_work[$type] ?? 0,
                // 'writerList_sme' => $writerList_sme_by_type_of_work[$type] ?? 0,
                // 'statisticanlist_sme' => $statisticanlist_sme_by_type_of_work[$type] ?? 0,
                // 'smelist_sme' => $smelist_sme_by_type_of_work[$type] ?? 0,
                // 'publication_sme' => $publication_list_by_type_of_work[$type] ?? 0,
                'pending_sme' => $total_sme,
                'tc' => $total_tc,

                'pending_publication' => $publication_by_type_of_work[$type] ?? 0,
                // 'pm_by_type_of_work' => $pm_by_type_of_work[$type] ?? 0,
                // 'notAssigned_tc_count' => $notAssigned_tc_count[$type] ?? 0,
                // 'writerWithoutReviewer' => $writerWithoutReviewer_by_type_of_work[$type] ?? 0,

                // //TC
                // 'revert_writer_by_type_of_work' => $revert_writer_by_type_of_work[$type] ?? 0,
                // 'notAssignedProjects_tc_by_type_of_work' => $notAssignedProjects_tc_by_type_of_work[$type] ?? 0,
                // 'revertdetails_by_type_of_work' => $revertdetails_by_type_of_work[$type] ?? 0,

                // //toDo tc
                // 'todoItems_by_type_of_work' => $todoItems_by_type_of_work[$type] ?? 0,

                // //rejected tc
                // 'rejected_tc_list' => $rejected_tc_by_type_of_work[$type] ?? 0,

            ];
        }

        return response()->json($finalResponse);
    }

    //employee performance report getting the ongoing to completed status time for each project_id based on assign_user
    // public function getEmployeePerformanceReport(Request $request)
    // {
    //     $query = ProjectLogs::with([
    //         'entryProcess:id,client_name,project_id',
    //         'userData:id,employee_name',
    //         'entryProcess.writerData',
    //         'entryProcess.reviewerData',
    //         'entryProcess.statisticanData',
    //         'entryProcess.journalData',
    //     ])
    //         ->select('id', 'project_id', 'employee_id', 'status', 'status_type', 'created_at', 'updated_at');

    //     if ($request->filled('employee_id')) {
    //         $query->where('employee_id', $request->employee_id);
    //     }

    //     $logs = $query->orderBy('created_at')->get();
    //     $performanceData = [];
    //     $groupedLogs = $logs->groupBy('employee_id');

    //     foreach ($groupedLogs as $employeeId => $employeeLogs) {
    //         $employeePerformance = [
    //             'employee_id' => $employeeId,
    //             'employee_name' => $employeeLogs->first()->userData->employee_name ?? 'Unknown',
    //             'project_data' => [],
    //         ];

    //         $writerLogs = $employeeLogs->where('status_type', 'writer')->where('status', 'on_going')->values();
    //         $writerCompletedLogs = $employeeLogs->where('status_type', 'writer')->where('status', 'completed')
    //         ->orderBy('created_at', 'asc')
    //         ->first();
    //         $reviewerLogs = $employeeLogs->where('status_type', 'reviewer')->values();

    //         // Process writer logs
    //         foreach ($writerLogs as $index => $log) {
    //             if ($log->status == 'completed') {
    //                 $previousLog = $writerLogs->get($index - 1);

    //                 if ($previousLog) {
    //                     $startTime = Carbon::parse($previousLog->created_date);
    //                     $endTime = Carbon::parse($log->created_date);
    //                     $duration = $startTime->diffInSeconds($endTime);
    //                     $projectId = $log->entryProcess->project_id ?? null;

    //                     if (! isset($employeePerformance['project_data'][$projectId])) {
    //                         $employeePerformance['project_data'][$projectId] = [
    //                             'project_id' => $projectId,
    //                             'writer' => [
    //                                 'normal' => [],
    //                                 'corrections' => [],
    //                                 'plag_corrections' => [],
    //                                 'corrections_count' => 0,
    //                                 'plag_corrections_count' => 0,
    //                                 'total_duration' => 0,
    //                             ],
    //                             'reviewer' => [
    //                                 'normal' => [],
    //                                 'corrections' => [],
    //                                 'plag_corrections' => [],
    //                                 'corrections_count' => 0,
    //                                 'plag_corrections_count' => 0,
    //                                 'total_duration' => 0,
    //                             ],
    //                             'total_duration' => 0,
    //                         ];
    //                     }

    //                     if ($previousLog->status == 'correction') {
    //                         $employeePerformance['project_data'][$projectId]['writer']['corrections'][] = [
    //                             'start_time' => $startTime->toDateTimeString(),
    //                             'end_time' => $endTime->toDateTimeString(),
    //                             'duration' => $this->formatSecondsToUnits($duration),
    //                         ];
    //                         $employeePerformance['project_data'][$projectId]['writer']['corrections_count']++;
    //                     } elseif ($previousLog->status == 'plag_correction') {
    //                         $employeePerformance['project_data'][$projectId]['writer']['plag_corrections'][] = [
    //                             'start_time' => $startTime->toDateTimeString(),
    //                             'end_time' => $endTime->toDateTimeString(),
    //                             'duration' => $this->formatSecondsToUnits($duration),
    //                         ];
    //                         $employeePerformance['project_data'][$projectId]['writer']['plag_corrections_count']++;
    //                     } elseif ($previousLog->status == 'on_going') {
    //                         $employeePerformance['project_data'][$projectId]['writer']['normal'][] = [
    //                             'start_time' => $startTime->toDateTimeString(),
    //                             'end_time' => $endTime->toDateTimeString(),
    //                             'duration' => $this->formatSecondsToUnits($duration),
    //                         ];
    //                     }

    //                     $employeePerformance['project_data'][$projectId]['writer']['total_duration'] += $duration;
    //                     $employeePerformance['project_data'][$projectId]['total_duration'] += $duration;
    //                 }
    //             }
    //         }

    //         // Process reviewer logs
    //         foreach ($reviewerLogs as $index => $log) {
    //             if ($log->status == 'completed') {
    //                 $previousLog = $reviewerLogs->get($index - 1);

    //                 if ($previousLog) {
    //                     $startTime = Carbon::parse($previousLog->created_at);
    //                     $endTime = Carbon::parse($log->created_at);
    //                     $duration = $startTime->diffInSeconds($endTime);
    //                     $projectId = $log->entryProcess->project_id ?? null;

    //                     if (! isset($employeePerformance['project_data'][$projectId])) {
    //                         $employeePerformance['project_data'][$projectId] = [
    //                             'project_id' => $projectId,
    //                             'writer' => [
    //                                 'normal' => [],
    //                                 'corrections' => [],
    //                                 'plag_corrections' => [],
    //                                 'corrections_count' => 0,
    //                                 'plag_corrections_count' => 0,
    //                                 'total_duration' => 0,
    //                             ],
    //                             'reviewer' => [
    //                                 'normal' => [],
    //                                 'corrections' => [],
    //                                 'plag_corrections' => [],
    //                                 'corrections_count' => 0,
    //                                 'plag_corrections_count' => 0,
    //                                 'total_duration' => 0,
    //                             ],
    //                             'total_duration' => 0,
    //                         ];
    //                     }

    //                     if ($previousLog->status == 'correction') {
    //                         $employeePerformance['project_data'][$projectId]['reviewer']['corrections'][] = [
    //                             'start_time' => $startTime->toDateTimeString(),
    //                             'end_time' => $endTime->toDateTimeString(),
    //                             'duration' => $this->formatSecondsToUnits($duration),
    //                         ];
    //                         $employeePerformance['project_data'][$projectId]['reviewer']['corrections_count']++;
    //                     } elseif ($previousLog->status == 'plag_correction') {
    //                         $employeePerformance['project_data'][$projectId]['reviewer']['plag_corrections'][] = [
    //                             'start_time' => $startTime->toDateTimeString(),
    //                             'end_time' => $endTime->toDateTimeString(),
    //                             'duration' => $this->formatSecondsToUnits($duration),
    //                         ];
    //                         $employeePerformance['project_data'][$projectId]['reviewer']['plag_corrections_count']++;
    //                     } elseif ($previousLog->status == 'on_going') {
    //                         $employeePerformance['project_data'][$projectId]['reviewer']['normal'][] = [
    //                             'start_time' => $startTime->toDateTimeString(),
    //                             'end_time' => $endTime->toDateTimeString(),
    //                             'duration' => $this->formatSecondsToUnits($duration),
    //                         ];
    //                     }

    //                     $employeePerformance['project_data'][$projectId]['reviewer']['total_duration'] += $duration;
    //                     $employeePerformance['project_data'][$projectId]['total_duration'] += $duration;
    //                 }
    //             }
    //         }
    //         if (strlen($employeeId) > 0) {
    //             foreach ($employeePerformance['project_data'] as $projectId => $projectData) {
    //                 $performanceData[] = [
    //                     'employee_id' => $employeeId,
    //                     'employee_name' => $employeePerformance['employee_name'],
    //                     'project_ids' => [$projectId],
    //                     'writer' => $projectData['writer'],
    //                     'reviewer' => $projectData['reviewer'],
    //                     'total_duration' => $this->formatDuration($projectData['total_duration']),
    //                 ];
    //             }
    //         }
    //     }

    //     return response()->json($performanceData);
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

    public function getEmployeePerformanceReport(Request $request)
    {
        $query = ProjectLogs::with([
            'entryProcess:id,client_name,project_id',
            'userData:id,employee_name',
        ])->select('id', 'project_id', 'employee_id', 'status', 'status_type', 'created_date', 'assing_preview_id');

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        $logs = $query->orderBy('created_date')->get();
        $groupedLogs = $logs->groupBy('employee_id');
        $performanceData = [];

        foreach ($groupedLogs as $employeeId => $employeeLogs) {

            $employeePerformance = [
                'employee_id' => $employeeId,
                'employee_name' => $employeeLogs->first()->userData->employee_name ?? 'Unknown',
                'project_data' => [],
            ];

            // Process roles
            $this->processRoleLogs($employeeLogs, 'writer', $employeePerformance);
            $this->processRoleLogs($employeeLogs, 'reviewer', $employeePerformance);

            // Prepare final array
            foreach ($employeePerformance['project_data'] as $projectId => $projectData) {
                $performanceData[] = [
                    'employee_id' => $employeeId,
                    'employee_name' => $employeePerformance['employee_name'],
                    'project_ids' => [$projectId],
                    'writer' => $projectData['writer'],
                    'reviewer' => $projectData['reviewer'],
                    'total_duration' => $this->formatSecondsToUnits($projectData['total_duration']),
                ];
            }
        }

        return response()->json($performanceData);
    }

    private function processRoleLogs($employeeLogs, $role, &$employeePerformance)
    {
        $roleLogs = $employeeLogs->where('status_type', $role)->sortBy('created_date')->values();
        if ($roleLogs->isEmpty()) {
            return;
        }

        $logsByProject = $roleLogs->groupBy(fn ($log) => $log->entryProcess->project_id ?? null);

        foreach ($logsByProject as $projectId => $projectLogs) {

            if (! isset($employeePerformance['project_data'][$projectId])) {
                $employeePerformance['project_data'][$projectId] = [
                    'project_id' => $projectId,
                    'writer' => [
                        'normal' => [],
                        'corrections' => [],
                        'plag_corrections' => [],
                        'corrections_count' => 0,
                        'plag_corrections_count' => 0,
                        'total_duration' => 0,
                    ],
                    'reviewer' => [
                        'normal' => [],
                        'corrections' => [],
                        'plag_corrections' => [],
                        'corrections_count' => 0,
                        'plag_corrections_count' => 0,
                        'total_duration' => 0,
                    ],
                    'total_duration' => 0,
                ];
            }

            $projectLogs = $projectLogs->values();
            $totalLogs = count($projectLogs);

            for ($i = 0; $i < $totalLogs; $i++) {
                $log = $projectLogs[$i];

                // ===== on_going → first completed =====
                if ($log->status === 'on_going') {
                    $firstCompleted = null;
                    for ($j = $i + 1; $j < $totalLogs; $j++) {
                        if ($projectLogs[$j]->status === 'completed') {
                            $firstCompleted = $projectLogs[$j];
                            break;
                        }
                    }

                    if ($firstCompleted) {
                        $startTime = Carbon::parse($log->created_date);
                        $endTime = Carbon::parse($firstCompleted->created_date);
                        $duration = $startTime->diffInSeconds($endTime);

                        $employeePerformance['project_data'][$projectId][$role]['normal'][] = [
                            'start_time' => $startTime->toDateTimeString(),
                            'end_time' => $endTime->toDateTimeString(),
                            'duration' => $this->formatSecondsToUnits($duration),
                        ];

                        $employeePerformance['project_data'][$projectId][$role]['total_duration'] += $duration;
                        $employeePerformance['project_data'][$projectId]['total_duration'] += $duration;
                    }
                }

                // ===== correction / plag_correction → last completed =====
                if (in_array($log->status, ['correction', 'plag_correction'])) {
                    $lastCompleted = null;
                    for ($j = $i + 1; $j < $totalLogs; $j++) {
                        if ($projectLogs[$j]->status === 'completed') {
                            $lastCompleted = $projectLogs[$j]; // keep overriding to get last completed
                        }
                    }

                    if ($lastCompleted) {
                        $startTime = Carbon::parse($log->created_date);
                        $endTime = Carbon::parse($lastCompleted->created_date);
                        $duration = $startTime->diffInSeconds($endTime);

                        $type = $log->status === 'correction' ? 'corrections' : 'plag_corrections';
                        $employeePerformance['project_data'][$projectId][$role][$type][] = [
                            'start_time' => $startTime->toDateTimeString(),
                            'end_time' => $endTime->toDateTimeString(),
                            'duration' => $this->formatSecondsToUnits($duration),
                        ];
                        $employeePerformance['project_data'][$projectId][$role][$type.'_count']++;
                        $employeePerformance['project_data'][$projectId][$role]['total_duration'] += $duration;
                        $employeePerformance['project_data'][$projectId]['total_duration'] += $duration;
                    }
                }
            }
        }
    }

    // private function formatDuration($seconds)
    // {
    //     $days = floor($seconds / 86400);
    //     $seconds -= $days * 86400;

    //     $hours = floor($seconds / 3600);
    //     $seconds -= $hours * 3600;

    //     $minutes = floor($seconds / 60);

    //     $parts = [];

    //     if ($days > 0) {
    //         $parts[] = $days.' days';
    //     }
    //     if ($hours > 0) {
    //         $parts[] = $hours.' hours';
    //     }
    //     if ($minutes > 0) {
    //         $parts[] = $minutes.' minutes';
    //     }

    //     if (empty($parts)) {
    //         return '0 minutes';
    //     }

    //     return implode(' ', $parts);
    // }
    private function formatDuration($duration)
    {
        // Step 1: Apply the formula V = M / 60
        $V = $duration / 60;

        // Step 2: Extract hours and minutes
        $hours = floor($V);
        $remainingMinutes = round(($V - $hours) * 60);

        return "{$hours} hr {$remainingMinutes} min";
    }

    // Helper function to format duration in hr min sec
    // private function formatDuration($seconds)
    // {
    //     $hours = floor($seconds / 3600);
    //     $minutes = floor(($seconds % 3600) / 60);
    //     $seconds = $seconds % 60;

    //     return sprintf('%d hr %d min %d sec', $hours, $minutes, $seconds);
    // }

    public function employeeTypeReports()
    {
        $totalProjectsInhouse = People::select('id', 'position', 'employee_name', 'employee_type')
            ->where('position', '!=', 'Admin')
            ->whereIn('position', [7, 8, 11])
            ->get()
            ->map(function ($person) {
                $person->created_by_users = $person->created_by_users; // Access the accessor

                return $person;
            });

        foreach ($totalProjectsInhouse as $entry) {
            $emp_pos = $entry->position;
            $emp_id = $entry->id;
            $positions = explode(',', $emp_pos);

            // Initialize count variables
            $writerCount = $reviewerCount = $statisticanCount = 0;
            $totalDuration = 0;

            if (in_array('7', $positions)) {
                $writerProjects = ProjectAssignDetails::where('assign_user', $emp_id)
                    ->where('type', 'writer')
                    ->get();

                foreach ($writerProjects as $project) {
                    $startTime = Carbon::parse($project->created_at);
                    $endTime = Carbon::parse($project->updated_at);
                    $totalDuration += $startTime->diffInSeconds($endTime);
                }

                $writerCount = $writerProjects->count();
            }

            if (in_array('8', $positions)) {
                $reviewerProjects = ProjectAssignDetails::where('assign_user', $emp_id)
                    ->where('type', 'reviewer')
                    ->get();

                foreach ($reviewerProjects as $project) {
                    $startTime = Carbon::parse($project->created_at);
                    $endTime = Carbon::parse($project->updated_at);
                    $totalDuration += $startTime->diffInSeconds($endTime);
                }

                $reviewerCount = $reviewerProjects->count();
            }

            if (in_array('11', $positions)) {
                $statisticanProjects = ProjectAssignDetails::where('assign_user', $emp_id)
                    ->where('type', 'statistican')
                    ->get();

                foreach ($statisticanProjects as $project) {
                    $startTime = Carbon::parse($project->created_at);
                    $endTime = Carbon::parse($project->updated_at);
                    $totalDuration += $startTime->diffInSeconds($endTime);
                }

                $statisticanCount = $statisticanProjects->count();
            }

            $entry->writer_count = $writerCount;
            $entry->reviewer_count = $reviewerCount;
            $entry->statistican_count = $statisticanCount;
            $entry->total_duration = $this->formatSecondsToUnits($totalDuration);
        }

        return response()->json($totalProjectsInhouse);
    }

    // Helper function to format duration in hr min sec
    // private function formatDurations($seconds)
    // {
    //     $hours = floor($seconds / 3600);
    //     $minutes = floor(($seconds % 3600) / 60);
    //     $seconds = $seconds % 60;

    //     return sprintf('%d hr %d min %d sec', $hours, $minutes, $seconds);
    // }
    //old
    // private function formatDurations($seconds)
    // {
    //     $days = floor($seconds / 86400);
    //     $seconds -= $days * 86400;

    //     $hours = floor($seconds / 3600);
    //     $seconds -= $hours * 3600;

    //     $minutes = floor($seconds / 60);

    //     $parts = [];

    //     if ($days > 0) {
    //         $parts[] = $days.' days';
    //     }
    //     if ($hours > 0) {
    //         $parts[] = $hours.' hours';
    //     }
    //     if ($minutes > 0) {
    //         $parts[] = $minutes.' minutes';
    //     }

    //     if (empty($parts)) {
    //         return '0 minutes';
    //     }

    //     return implode(' ', $parts);
    // }

    private function formatDurations($duration)
    {
        // Step 1: Apply the formula V = M / 60
        $V = $duration / 60;

        // Step 2: Extract hours and minutes
        $hours = floor($V);
        $remainingMinutes = round(($V - $hours) * 60);

        return "{$hours} hr {$remainingMinutes} min";
    }

    public function journalStatus(Request $request)
    {
        $fromDate = $request->query('from_date');
        $toDate = $request->query('to_date');
        $journalStatus = ['submit_to_journal', 'pending_author', 'resubmission', 'reviewer_comments'];
        $pending_journal = ProjectAssignDetails::where('type', 'publication_manager')
            ->whereIn('status', $journalStatus)
            ->whereHas('projectData', function ($query) use ($fromDate, $toDate) {
                $query->where('process_status', '!=', 'completed')
                    ->when($fromDate, fn ($q) => $q->whereDate('entry_date', '>=', $fromDate))
                    ->when($toDate, fn ($q) => $q->whereDate('entry_date', '<=', $toDate));
            })
            ->get()
            ->unique('project_id');

        $grouped = $pending_journal->groupBy('status');
        $now = now();
        $statusCounts = [];

        foreach ($journalStatus as $status) {
            $entries = $grouped->get($status, collect());

            $statusData = [
                'period' => $status,
                'total' => $entries->count(),
                'two_weeks' => 0,
                'two_four_weeks' => 0,
                'four_weeks' => 0,
            ];

            foreach ($entries as $entry) {
                $diffInDays = $now->diffInDays($entry->created_at);

                if ($diffInDays < 14) {
                    $statusData['two_weeks']++;
                } elseif ($diffInDays <= 28) {
                    $statusData['two_four_weeks']++;
                } else {
                    $statusData['four_weeks']++;
                }
            }

            $statusCounts[] = $statusData;
        }

        return response()->json($statusCounts);
    }
}
