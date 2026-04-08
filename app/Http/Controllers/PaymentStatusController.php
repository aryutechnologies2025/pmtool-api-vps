<?php

namespace App\Http\Controllers;

use App\Models\EmployeePaymentDetails;
use App\Models\EntryProcessModel;
use App\Models\PaymentDetails;
use App\Models\PaymentLogs;
use App\Models\PaymentStatusModel;
use App\Models\People;
use App\Models\ProjectActivity;
use App\Models\ProjectAssignDetails;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentStatusController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Retrieve all PaymentStatusModel entries with related data
        $details = PaymentStatusModel::with('paymentData', 'projectData')
            ->orderBy('created_at', 'desc')
            ->get();
        $data = [];

        foreach ($details as $d) {
            $data[] = [
                'id' => $d->id,
                'project_id' => $d->projectData->project_id ?? null, // Safeguard null project data
                'entry_date' => $d->projectData->entry_date ?? null,
                'budget' => $d->projectData->budget ?? null,
                'writer_payment' => $d->writer_payment,
                'reviewer_payment' => $d->reviewer_payment,
                'statistican_payment' => $d->statistican_payment,
                'journal_payment' => $d->journal_payment,
                'payment_details' => $d->paymentData->map(function ($paymentDetail) {
                    return [
                        'payment_id' => $paymentDetail->id,
                        'payment' => $paymentDetail->payment,
                        'payment_date' => $paymentDetail->payment_date,
                    ];
                }),
            ];
        }

        return response()->json($data);
    }

    public function store(Request $request)
    {
        $entry = EntryProcessModel::select('id', 'entry_date', 'title', 'project_id', 'type_of_work', 'email', 'institute', 'department', 'profession', 'budget', 'process_status', 'hierarchy_level', 'created_by', 'project_status', 'assign_by', 'assign_date', 'projectduration')->where('project_id', $request->project_id)->first();

        if (! $entry) {
            return response()->json(['message' => 'Invalid project ID'], 400);
        }
        // Create a new PaymentStatusModel instance
        $details = new PaymentStatusModel;

        // Set fields
        $details->project_id = $request->project_id;
        $details->process_title = $request->process_title;
        $details->budget = $request->budget;
        $details->payment_status = $request->payment_status;
        $details->reference_number = $request->reference_number ?? '';

        if ($request->hasFile('reference_number_file') && $request->file('reference_number_file')->isValid()) {
            // Get the uploaded file
            $file = $request->file('reference_number_file');

            // Get the original name without extension
            $originalNameWithoutExtension = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

            // Generate a unique name for the file
            $uniqueName = $originalNameWithoutExtension.'_'.time().'.'.$file->extension();

            $path = public_path('payment_screenshots');

            if (! is_dir($path)) {
                mkdir($path, 0775, true);
            }

            $file->move($path, $uniqueName);

            $filePath = $uniqueName;

            // Log the details of the file upload process
            // Log::info('File uploaded successfully:', [
            //     'original_name' => $file->getClientOriginalName(),
            //     'unique_name' => $uniqueName,
            //     'path' => $path,
            //     'file_path' => $filePath,
            // ]);
        } else {
            Log::warning('No valid file uploaded or file upload failed.');
        }

        if ($filePath) {
            $details->reference_number_file = $filePath;
        }
        // Set default values for status and is_deleted if not provided
        $details->status = $request->status ?? 1;
        $details->is_deleted = $request->is_deleted ?? 0;

        // Save the details
        $details->save();

        if (
            (! empty($request->writerPay) && is_array($request->writerPay)) ||
            (! empty($request->reviewerPay) && is_array($request->reviewerPay)) ||
            (! empty($request->statisticanPay) && is_array($request->statisticanPay))
        ) {
            // Process writer payments
            if (! empty($request->writerPay) && is_array($request->writerPay)) {
                foreach ($request->writerPay as $user) {
                    $paymentEpD = new EmployeePaymentDetails;
                    $paymentEpD->project_id = $request->project_id;
                    $paymentEpD->employee_id = $user['writerName'] ?? null;
                    $paymentEpD->payment = $user['paymentAmount'] ?? 0;
                    $paymentEpD->payment_date = $user['paymentDate'] ?? date('Y-m-d');
                    $paymentEpD->status = $user['paymentStatus'] ?? 'pending';
                    $paymentEpD->created_date = date('Y-m-d H:i:s');
                    $paymentEpD->save();
                }
            }

            // Process reviewer payments
            if (! empty($request->reviewerPay) && is_array($request->reviewerPay)) {
                foreach ($request->reviewerPay as $user) {
                    $paymentEpD = new EmployeePaymentDetails;
                    $paymentEpD->project_id = $request->project_id;
                    $paymentEpD->employee_id = $user['reviewerName'] ?? null;
                    $paymentEpD->payment = $user['reviewerPayment'] ?? 0;
                    $paymentEpD->payment_date = $user['reviewerPaymentDate'] ?? date('Y-m-d');
                    $paymentEpD->status = $user['reviewerPaymentStatus'] ?? 'pending';
                    $paymentEpD->created_date = date('Y-m-d H:i:s');
                    $paymentEpD->save();
                }
            }

            // Process statistician payments
            if (! empty($request->statisticanPay) && is_array($request->statisticanPay)) {
                foreach ($request->statisticanPay as $user) {
                    $paymentEpD = new EmployeePaymentDetails;
                    $paymentEpD->project_id = $request->project_id;
                    $paymentEpD->employee_id = $user['statisticanName'] ?? null;
                    $paymentEpD->payment = $user['statisticanPayment'] ?? 0;
                    $paymentEpD->payment_date = $user['statisticanPaymentDate'] ?? date('Y-m-d');
                    $paymentEpD->status = $user['statisticanPaymentStatus'] ?? 'pending';
                    $paymentEpD->created_date = date('Y-m-d H:i:s');
                    $paymentEpD->save();
                }
            }
        }

        // Check if payment_details exist in the request and add them
        if ($request->has('payment_details') && is_array($request->payment_details)) {
            foreach ($request->payment_details as $pay) {
                // Add each payment detail
                $paymentDetails = new PaymentDetails;
                $paymentDetails->payment_id = $details->id; // Assuming the payment ID relates to project_id
                $paymentDetails->payment = $pay['amount']; // Amount from payment details
                $paymentDetails->payment_date = $pay['date']; // Payment date from payment details
                $paymentDetails->save();
            }
        }

        // Return the saved details
        return response()->json($details);
    }

    /**
     * Display the specified resource.
     */
    // public function show(string $id)
    // {
    //     $details = PaymentStatusModel::with('paymentData', 'projectData', 'paymentWEmpData', 'paymentREmpData', 'paymentSEmpData', 'paymentJEmpData')
    //         ->orderBy('created_at', 'desc')
    //         ->where('project_id', $id)->first();

    //     return response()->json($details);
    // }

    // public function show(string $id)
    // {
    //     $details = PaymentStatusModel::with(
    //         'paymentData',
    //         'projectData',
    //         'paymentWEmpData',
    //         'paymentREmpData',
    //         'paymentSEmpData',
    //         'paymentJEmpData'
    //     )
    //         ->orderBy('created_at', 'desc')
    //         ->where('project_id', $id)
    //         ->first();

    //     if (! $details) {
    //         return response()->json(null);
    //     }

    //     $safeDecode = function ($value) {
    //         if (! is_string($value)) {
    //             return $value;
    //         }

    //         $first = json_decode($value, true);
    //         if (json_last_error() !== JSON_ERROR_NONE) {
    //             return $value;
    //         }

    //         return is_string($first)
    //             ? json_decode($first, true)
    //             : $first;
    //     };

    //     $details->reference_number_file =
    //         $safeDecode($details->reference_number_file);

    //     if ($details->paymentData) {
    //         foreach ($details->paymentData as $payment) {
    //             $payment->reference_number_file =
    //                 $safeDecode($payment->reference_number_file);
    //         }
    //     }

    //     return response()->json($details->toArray());
    // }

    public function show(string $id)
    {
        $details = PaymentStatusModel::with(
            'paymentData',
            'projectData',
            'paymentWEmpData',
            'paymentREmpData',
            'paymentSEmpData',
            'paymentJEmpData'
        )
            ->where('project_id', $id)
            ->orderBy('created_at', 'desc')
            ->first();

        if (! $details) {
            return response()->json(null);
        }

        // ================================
        // HANDLE OLD/STRING DATA SAFELY
        // ================================
        $normalizeFiles = function ($value) {
            // If null or empty → return empty array
            if (empty($value)) {
                return [];
            }

            $files = [];

            // Already an array → filter empty values
            if (is_array($value)) {
                $files = array_filter($value, fn ($v) => trim($v) !== '');
            }
            // JSON string → decode and filter
            elseif (is_string($value)) {
                $decoded = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $files = array_filter($decoded, fn ($v) => trim($v) !== '');
                } else {
                    // Single filename string → trim and wrap in array if not empty
                    $value = trim($value);
                    if ($value !== '') {
                        $files = [$value];
                    }
                }
            }

            return array_values($files); // Reindex
        };

        // Fix main payment file
        $details->reference_number_file = $normalizeFiles($details->reference_number_file);

        // Fix each payment detail
        if ($details->paymentData) {
            foreach ($details->paymentData as $payment) {
                $payment->reference_number_file = $normalizeFiles($payment->reference_number_file);
            }
        }

        // Return safe JSON
        return response()->json($details->toArray());
    }

    public function showPayment(string $id, Request $request)
    {
        $position = $request->query('position');
        $createdBy = $request->query('created_by');
        // Fetch the payment details
        $details = PaymentStatusModel::with('paymentData', 'paymentLog', 'projectData')
            ->orderBy('created_at', 'desc')
            ->where('project_id', $id)
            ->first();

        if ($details) {
            if ($position == 8) {
                $paymentDetails = [
                    'reviewer_payment' => $details->reviewer_payment,
                    'reviewer_payment_date' => $details->reviewer_payment_date,
                    'project_data' => $details->projectData,
                ];
            } else {
                $paymentDetails = [
                    'writer_payment' => $details->writer_payment,
                    'writer_payment_date' => $details->writer_payment_date,
                    'project_data' => $details->projectData,
                ];
            }

            return response()->json($paymentDetails);
        }

        return response()->json(['error' => 'Payment details not found'], 404);
    }

    public function update(Request $request, $id)
    {
        $payment = PaymentStatusModel::find($id);
        if (! $payment) {
            return response()->json(['message' => 'Payment record not found.'], 404);
        }

        $oldPaymentStatus = $payment->payment_status;

        $paymentDetails = $request->payment_details ?? [];
        $latestPaymentType = null;
        $latestPaymentAmount = null;
        $latestId = null;

        if (is_array($paymentDetails)) {
            foreach ($paymentDetails as $detail) {
                if (! empty($detail['payment_type'])) {
                    $latestId = $detail['id'] ?? null;
                    $latestPaymentType = $detail['payment_type'];
                    $latestPaymentAmount = $detail['payment'] ?? null;
                }
            }
        }

        $entryProcess = EntryProcessModel::find($request->project_id);
        if (! $entryProcess) {
            return response()->json(['message' => 'Project entry not found.'], 404);
        }

        // $totalPayment = PaymentDetails::where('payment_id', $id)
        //     ->whereIn('payment_type', [
        //         'advance_received',
        //         'partial_payment_received',
        //         'completed',
        //     ])
        //     ->where('id', '!=', $latestId)
        //     ->sum('payment');

        // if (in_array($latestPaymentType, ['advance_received', 'partial_payment_received', 'completed'])) {
        //     $finalAmount = $totalPayment + ($latestPaymentAmount ?? 0);

        //     if ($finalAmount > $entryProcess->budget) {
        //         return response()->json(['message' => 'Payment amount is greater than the budget.'], 400);
        //     }

        //     if ($latestPaymentType === 'completed' && $finalAmount != $entryProcess->budget) {
        //         return response()->json(['message' => 'Payment amount does not match the budget.'], 400);
        //     }
        // }


// Get all payment details from the request payload
$paymentDetails = $request->payment_details ?? [];

// Calculate total from the UPDATED payment details in the request
$totalUpdatedPayment = 0;
$paymentIdsInRequest = [];

foreach ($paymentDetails as $detail) {
    if (in_array($detail['payment_type'], ['advance_received', 'partial_payment_received', 'completed'])) {
        $totalUpdatedPayment += ($detail['payment'] ?? 0);
        
        if (!empty($detail['id'])) {
            $paymentIdsInRequest[] = $detail['id'];
        }
    }
}

// Get existing payments from database that are NOT being updated
$existingPaymentsTotal = PaymentDetails::where('payment_id', $id)
    ->whereIn('payment_type', ['advance_received', 'partial_payment_received', 'completed'])
    ->whereNotIn('id', $paymentIdsInRequest) // Exclude the ones being updated
    ->sum('payment');

// Final total = existing payments (not updated) + updated payments from request
$finalAmount = $existingPaymentsTotal + $totalUpdatedPayment;

// Validate against budget
if ($finalAmount > $entryProcess->budget) {
    return response()->json([
        'message' => 'Payment amount is greater than the budget.',
        'details' => [
            'existing_payments' => $existingPaymentsTotal,
            'updated_payments_total' => $totalUpdatedPayment,
            'final_amount' => $finalAmount,
            'budget' => $entryProcess->budget
        ]
    ], 400);
}

// Check if any payment type is 'completed' in the updated payload
$hasCompletedPayment = false;
foreach ($paymentDetails as $detail) {
    if ($detail['payment_type'] === 'completed') {
        $hasCompletedPayment = true;
        break;
    }
}

if ($hasCompletedPayment && $finalAmount != $entryProcess->budget) {
    return response()->json([
        'message' => 'Payment amount does not match the budget.',
        'details' => [
            'final_amount' => $finalAmount,
            'required_budget' => $entryProcess->budget,
            'difference' => $entryProcess->budget - $finalAmount
        ]
    ], 400);
}

        function handleFileUpload($mixedArray, $additionalExistingFiles = [])
        {
            $existingFiles = [];
            $filesToUpload = [];
            $uploadedFiles = [];

            if (is_array($mixedArray)) {
                foreach ($mixedArray as $item) {
                    if (is_string($item) && ! empty(trim($item))) {

                        $existingFiles[] = trim($item);
                    } elseif ($item instanceof \Illuminate\Http\UploadedFile) {

                        $filesToUpload[] = $item;
                    }
                }
            }

            if (! empty($additionalExistingFiles)) {
                if (is_array($additionalExistingFiles)) {
                    $existingFiles = array_merge($existingFiles, $additionalExistingFiles);
                } elseif (is_string($additionalExistingFiles)) {
                    $decoded = json_decode($additionalExistingFiles, true);
                    if (is_array($decoded)) {
                        $existingFiles = array_merge($existingFiles, $decoded);
                    }
                }
            }

            if (! empty($filesToUpload)) {
                foreach ($filesToUpload as $file) {
                    if ($file->isValid()) {
                        $filename =
                            pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)
                            .'_'.time().'_'.rand(1000, 9999)
                            .'.'.$file->extension();

                        $path = public_path('payment_screenshots');

                        if (! is_dir($path)) {
                            mkdir($path, 0775, true);
                        }

                        $file->move($path, $filename);
                        $uploadedFiles[] = $filename;
                    }
                }
            }

            $allFiles = array_merge($existingFiles, $uploadedFiles);
            $allFiles = array_values(array_unique(array_filter($allFiles)));

            return $allFiles;
        }

        $payment->project_id = $request->project_id;
        $payment->payment_status = $latestPaymentType ?? $request->payment_status;
        $payment->discounts = $request->discount;
        $payment->reference_number = $request->reference_number;
        $payment->created_by = $request->created_by;

        if ($request->hasFile('reference_number_file')) {
            $remainingFiles = $request->existing_reference_files ?? [];
            $files = handleFileUpload(
                $request->file('reference_number_file'),
                $remainingFiles
            );

            $payment->reference_number_file = ! empty($files) ? $files : null;
        }

        $payment->save();

        if ($payment->payment_status !== $oldPaymentStatus) {
            PaymentLogs::create([
                'project_id' => $request->project_id,
                'payment_id' => $payment->id,
                'payment_status' => $request->payment_status,
                'reference_number' => $request->reference_number,
                'reference_number_file' => $payment->reference_number_file,
                'created_by' => $request->created_by,
                'created_date' => now(),
            ]);
        }

        // if (is_array($paymentDetails)) {
        //     foreach ($paymentDetails as $index => $detail) {

        //         if (empty($detail['payment_type'])) {
        //             return response()->json(['message' => 'Payment type is not selected.'], 400);
        //         }

        //         $amountPayment = PaymentDetails::where('payment_id', $payment->id)
        //             // ->where('payment_type', $detail['payment_type'])
        //             ->first();
        //         if ($amountPayment->payment === $detail['payment']) {
        //             $existingPayment = PaymentDetails::where('payment_id', $payment->id)
        //             ->where('id', $detail['id'])
        //             // ->where('payment_type', $detail['payment_type'])
        //                 ->first();
        //         } elseif($existingPayment->payment_type !== $detail['payment_type']) {

        //             $existingPayment = PaymentDetails::where('payment_id', $payment->id)
        //                 // ->where('payment_type', $detail['payment_type'])
        //                 ->where('id', $detail['id'])
        //                 ->first();
        //         }
        //         else{
        //             $existingPayment = PaymentDetails::where('payment_id', $payment->id)
        //                 // ->where('payment_type', $detail['payment_type'])
        //                 // ->where('id', $detail['id'])
        //                 ->first();
        //         }

        //         $mixedFilesArray = $detail['reference_number_file'] ?? [];

        //         $additionalExistingFiles = $detail['existing_reference_files'] ?? [];

        //         $finalFiles = handleFileUpload($mixedFilesArray, $additionalExistingFiles);

        //         if ($existingPayment) {
        //             $existingPayment->payment = $detail['payment'] ?? $existingPayment->payment;
        //             $existingPayment->payment_date = $detail['payment_date'] ?? $existingPayment->payment_date;
        //             $existingPayment->reference_number = $detail['reference_number'] ?? $existingPayment->reference_number;
        //             $existingPayment->payment_type = $detail['payment_type'] ?? $existingPayment->payment_type;

        //             $existingPayment->reference_number_file = ! empty($finalFiles) ? $finalFiles : null;

        //             $existingPayment->save();
        //         } else {
        //             PaymentDetails::create([
        //                 'payment_id' => $payment->id,
        //                 'payment' => $detail['payment'] ?? 0,
        //                 'payment_type' => $detail['payment_type'],
        //                 'payment_date' => $detail['payment_date'] ?? now(),
        //                 'reference_number' => $detail['reference_number'] ?? null,
        //                 'reference_number_file' => ! empty($finalFiles) ? $finalFiles : null,
        //             ]);
        //         }
        //     }
        //     $created = User::with('createdByUser')->find($request->created_by);
        //     $employee = $created?->employee_name ?? 'Mohamed Ali';
        //     $creator = $created?->createdByUser?->name ?? 'Admin';
        //     $paymentTypeForActivity = ! empty($detail['payment_type']) ? $detail['payment_type'] : $request->payment_type;
        //     $activityText = "Payment marked as {$paymentTypeForActivity} by {$employee} ({$creator})";
        //     //project activity
        //     ProjectActivity::create([
        //         'project_id' => $request->project_id,
        //         'activity' => $activityText,
        //         'created_by' => $request->created_by,
        //         'role' => $creator,
        //         'created_date' => now(),
        //     ]);
        // }

        if (is_array($paymentDetails)) {
    foreach ($paymentDetails as $index => $detail) {
        if (empty($detail['payment_type'])) {
            return response()->json(['message' => 'Payment type is not selected.'], 400);
        }

        // Initialize $existingPayment as null
        $existingPayment = null;
        
        // Only try to find existing payment if 'id' exists in the detail
        if (isset($detail['id']) && !empty($detail['id'])) {
            $existingPayment = PaymentDetails::where('payment_id', $payment->id)
                ->where('id', $detail['id'])
                ->first();
        }

        $mixedFilesArray = $detail['reference_number_file'] ?? [];
        $additionalExistingFiles = $detail['existing_reference_files'] ?? [];
        $finalFiles = handleFileUpload($mixedFilesArray, $additionalExistingFiles);

        if ($existingPayment) {
            // Update existing payment
            $existingPayment->payment = $detail['payment'] ?? $existingPayment->payment;
            $existingPayment->payment_date = $detail['payment_date'] ?? $existingPayment->payment_date;
            $existingPayment->reference_number = $detail['reference_number'] ?? $existingPayment->reference_number;
            $existingPayment->payment_type = $detail['payment_type'] ?? $existingPayment->payment_type;
            $existingPayment->reference_number_file = !empty($finalFiles) ? $finalFiles : null;
            $existingPayment->save();
        } else {
            // Create new payment
            PaymentDetails::create([
                'payment_id' => $payment->id,
                'payment' => $detail['payment'] ?? 0,
                'payment_type' => $detail['payment_type'],
                'payment_date' => $detail['payment_date'] ?? now(),
                'reference_number' => $detail['reference_number'] ?? null,
                'reference_number_file' => !empty($finalFiles) ? $finalFiles : null,
            ]);
        }
    }
    
    // Activity logging (using the last detail or request data)
    $lastPaymentType = !empty($detail['payment_type']) ? $detail['payment_type'] : ($request->payment_type ?? 'N/A');
    $created = User::with('createdByUser')->find($request->created_by);
    $employee = $created?->employee_name ?? 'Mohamed Ali';
    $creator = $created?->createdByUser?->name ?? 'Admin';
    
    $activityText = "Payment marked as {$lastPaymentType} by {$employee} ({$creator})";
    
    ProjectActivity::create([
        'project_id' => $request->project_id,
        'activity' => $activityText,
        'created_by' => $request->created_by,
        'role' => $creator,
        'created_date' => now(),
    ]);
}

        $employeePaymentTypes = ['writerPay', 'reviewerPay', 'statisticanPay', 'journalPay'];

        foreach ($employeePaymentTypes as $type) {
            $paymentData = $request->input($type);

            if (! empty($paymentData) && is_array($paymentData)) {
                foreach ($paymentData as $user) {
                    if (! is_array($user)) {
                        continue;
                    }

                    $employeeFieldMap = [
                        'writerPay' => ['id' => 'id', 'id_field' => 'writerName', 'payment_field' => 'paymentAmount', 'date_field' => 'paymentDate', 'status_field' => 'paymentStatus', 'db_type' => 'writer'],
                        'reviewerPay' => ['id' => 'id', 'id_field' => 'reviewerName', 'payment_field' => 'reviewerPayment', 'date_field' => 'reviewerPaymentDate', 'status_field' => 'reviewerPaymentStatus', 'db_type' => 'reviewer'],
                        'statisticanPay' => ['id' => 'id', 'id_field' => 'statisticanName', 'payment_field' => 'statisticanPayment', 'date_field' => 'statisticanPaymentDate', 'status_field' => 'statisticanPaymentStatus', 'db_type' => 'statistican'],
                        'journalPay' => ['id' => 'id', 'id_field' => 'journalName', 'payment_field' => 'journalPayment', 'date_field' => 'journalPaymentDate', 'status_field' => 'journalPaymentStatus', 'db_type' => 'publication_manager'],
                    ];

                    $mapping = $employeeFieldMap[$type];
                    $employee_id = $user[$mapping['id_field']] ?? null;

                    if (! $employee_id) {
                        continue;
                    }

                    $paymentEpD = EmployeePaymentDetails::where('project_id', $request->project_id)
                        // ->where('payment_id', $payment->id)
                        ->where('id', $user[$mapping['id']] ?? 0)
                        // ->where('employee_id', $employee_id)
                        ->first();

                    if ($paymentEpD) {
                        $paymentEpD->payment = $user[$mapping['payment_field']] ?? 0;
                        $paymentEpD->payment_date = $user[$mapping['date_field']] ?? null;
                        $paymentEpD->status = $user[$mapping['status_field']] ?? null;
                        $paymentEpD->type = $mapping['db_type'];
                        $paymentEpD->created_date = now();
                        $paymentEpD->save();
                    } else {
                        EmployeePaymentDetails::create([
                            'project_id' => $request->project_id,
                            'payment_id' => $payment->id,
                            'employee_id' => $employee_id,
                            'payment' => $user[$mapping['payment_field']] ?? 0,
                            'payment_date' => $user[$mapping['date_field']] ?? null,
                            'status' => $user[$mapping['status_field']] ?? null,
                            'type' => $mapping['db_type'],
                            'created_date' => now(),
                        ]);
                    }
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment successfully updated.',
            'payment_id' => $payment->id,
            'details' => $payment->toArray(),
            'payment_details' => $paymentDetails,
        ]);
    }
    //    public function update(Request $request, $id)
    // {
    //     $payment = PaymentStatusModel::find($id);
    //     if (!$payment) {
    //         return response()->json(['message' => 'Payment record not found.'], 404);
    //     }

    //     // Store old status to compare
    //     $oldPaymentStatus = $payment->payment_status;

    //     $paymentDetails = $request->payment_details ?? [];
    //     $latestPaymentType = null;
    //     $latestPaymentAmount = null;

    //     if (is_array($paymentDetails)) {
    //         foreach ($paymentDetails as $detail) {
    //             if (!empty($detail['payment_type'])) {
    //                 $latestPaymentType = $detail['payment_type'];
    //                 $latestPaymentAmount = $detail['payment'];
    //             }
    //         }
    //     }

    //     $entryProcess = EntryProcessModel::where('id', $request->project_id)->first();
    //     if (!$entryProcess) {
    //         return response()->json(['message' => 'Project entry not found.'], 404);
    //     }

    //     Log::info('Total payment amount: ' . $entryProcess->budget);

    //     // Calculate total payment amount excluding pending payments
    //     $totalPayment = PaymentDetails::where('payment_id', $id)
    //         ->whereIn('payment_type', [
    //             'advance_received',
    //             'partial_payment_received',
    //             'completed',
    //         ])
    //         ->sum('payment');

    //     // Add the latest payment amount if it's a received payment
    //     if (in_array($latestPaymentType, ['advance_received', 'partial_payment_received', 'completed'])) {
    //         $finalAmount = $totalPayment + ($latestPaymentAmount ?? 0);

    //         Log::info('latestPaymentAmount: ' . $latestPaymentAmount);
    //         Log::info('finalAmount: ' . $finalAmount);

    //         // Validate payment amounts
    //         if ($finalAmount > $entryProcess->budget) {
    //             return response()->json([
    //                 'message' => 'Payment amount is greater than the budget.',
    //             ], 400);
    //         }

    //         if ($latestPaymentType === 'completed' && $finalAmount != $entryProcess->budget) {
    //             return response()->json([
    //                 'message' => 'Payment amount does not match the budget.',
    //             ], 400);
    //         }
    //     }

    //     // Update base payment fields
    //     $payment->project_id = $request->project_id;
    //     $payment->payment_status = $latestPaymentType ?? $request->payment_status;
    //     $payment->discounts = $request->discount;
    //     $payment->reference_number = $request->reference_number;
    //     $payment->created_by = $request->created_by;

    //     // Handle main payment file upload (array of files)
    //     if ($request->hasFile('reference_number_file')) {
    //         $filenames = [];
    //         $files = $request->file('reference_number_file');

    //         // Check if $files is an array
    //         if (is_array($files)) {
    //             foreach ($files as $fileArray) {
    //                 // Some items might be arrays, others might be single files
    //                 if (is_array($fileArray)) {
    //                     foreach ($fileArray as $file) {
    //                         if ($file && $file->isValid()) {
    //                             $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '_' . time() . '_' . rand(1000, 9999) . '.' . $file->extension();
    //                             $path = public_path('payment_screenshots');

    //                             if (!is_dir($path)) {
    //                                 mkdir($path, 0775, true);
    //                             }

    //                             $file->move($path, $filename);
    //                             $filenames[] = $filename;
    //                         }
    //                     }
    //                 } else if ($fileArray && $fileArray->isValid()) {
    //                     // Handle single file
    //                     $file = $fileArray;
    //                     $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '_' . time() . '_' . rand(1000, 9999) . '.' . $file->extension();
    //                     $path = public_path('payment_screenshots');

    //                     if (!is_dir($path)) {
    //                         mkdir($path, 0775, true);
    //                     }

    //                     $file->move($path, $filename);
    //                     $filenames[] = $filename;
    //                 }
    //             }
    //         }

    //         if (!empty($filenames)) {
    //             $payment->reference_number_file = json_encode($filenames);
    //         }
    //     }

    //     $payment->save();

    //     // Insert or update logs based on changed status
    //     if ($payment->payment_status !== $oldPaymentStatus) {
    //         $log = PaymentLogs::where('project_id', $request->project_id)
    //             ->where('payment_id', $payment->id)
    //             ->where('payment_status', $request->payment_status)
    //             ->first();

    //         if ($log) {
    //             $log->reference_number = $request->reference_number;
    //             $log->reference_number_file = $payment->reference_number_file ?? null;
    //             $log->created_date = now();
    //             $log->save();
    //         } else {
    //             PaymentLogs::create([
    //                 'project_id' => $request->project_id,
    //                 'payment_id' => $payment->id,
    //                 'payment_status' => $request->payment_status,
    //                 'reference_number' => is_array($request->reference_number) ? json_encode($request->reference_number) : $request->reference_number,
    //                 'reference_number_file' => $payment->reference_number_file ?? null,
    //                 'created_by' => $request->created_by,
    //                 'created_date' => now(),
    //             ]);
    //         }
    //     }

    //     // Process payment details
    //     if (is_array($paymentDetails)) {
    //         foreach ($paymentDetails as $index => $detail) {
    //             $existingPayment = null;

    //             if (!empty($detail['id'])) {
    //                 $existingPayment = PaymentDetails::where('payment_id', $payment->id)
    //                     ->where('id', $detail['id'])
    //                     ->first();
    //             }

    //             // File handling for payment details
    //             $uploadedFileNames = [];
    //             if ($request->hasFile("payment_details.$index.reference_number_file")) {
    //                 $files = $request->file("payment_details.$index.reference_number_file");

    //                 if (is_array($files)) {
    //                     foreach ($files as $file) {
    //                         if ($file && $file->isValid()) {
    //                             $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '_' . time() . '_' . rand(1000, 9999) . '.' . $file->extension();
    //                             $path = public_path('payment_screenshots');

    //                             if (!is_dir($path)) {
    //                                 mkdir($path, 0775, true);
    //                             }

    //                             $file->move($path, $filename);
    //                             $uploadedFileNames[] = $filename;
    //                         }
    //                     }
    //                 } else if ($files && $files->isValid()) {
    //                     // Handle single file
    //                     $file = $files;
    //                     $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '_' . time() . '_' . rand(1000, 9999) . '.' . $file->extension();
    //                     $path = public_path('payment_screenshots');

    //                     if (!is_dir($path)) {
    //                         mkdir($path, 0775, true);
    //                     }

    //                     $file->move($path, $filename);
    //                     $uploadedFileNames[] = $filename;
    //                 }
    //             }

    //             if (empty($detail['payment_type'])) {
    //                 return response()->json([
    //                     'message' => 'Payment type is not selected.',
    //                 ], 400);
    //             }

    //             if ($existingPayment) {
    //                 // Update existing record
    //                 $existingPayment->payment = $detail['payment'] ?? $existingPayment->payment;
    //                 $existingPayment->payment_type = $detail['payment_type'] ?? $existingPayment->payment_type;
    //                 $existingPayment->payment_date = $detail['payment_date'] ?? $existingPayment->payment_date;
    //                 $existingPayment->reference_number = $detail['reference_number'] ?? $existingPayment->reference_number;

    //                 // Update files if new ones uploaded
    //                 if (!empty($uploadedFileNames)) {
    //                     // Check if there are existing files to merge
    //                     $existingFiles = [];
    //                     if ($existingPayment->reference_number_file) {
    //                         $existingFiles = json_decode($existingPayment->reference_number_file, true) ?? [];
    //                     }
    //                     // Merge existing and new files
    //                     $allFiles = array_merge($existingFiles, $uploadedFileNames);
    //                     $existingPayment->reference_number_file = json_encode($allFiles);
    //                 }

    //                 $existingPayment->save();
    //             } else {
    //                 // Create new record
    //                 $paymentDetail = new PaymentDetails;
    //                 $paymentDetail->payment_id = $payment->id;
    //                 $paymentDetail->payment = $detail['payment'] ?? 0;
    //                 $paymentDetail->payment_type = $detail['payment_type'] ?? $request->payment_status;
    //                 $paymentDetail->payment_date = $detail['payment_date'] ?? now();
    //                 $paymentDetail->reference_number = $detail['reference_number'] ?? null;
    //                 $paymentDetail->reference_number_file = !empty($uploadedFileNames) ? json_encode($uploadedFileNames) : null;
    //                 $paymentDetail->save();
    //             }
    //         }
    //     } else {
    //         Log::warning('Invalid payment_details format. Expected an array.', ['payment_details' => $paymentDetails]);
    //     }

    //     // Process employee payments (if provided)
    //     $employeePaymentTypes = ['writerPay', 'reviewerPay', 'statisticanPay', 'journalPay'];

    //     foreach ($employeePaymentTypes as $type) {
    //         $paymentData = $request->input($type);

    //         if (!empty($paymentData) && is_array($paymentData)) {
    //             foreach ($paymentData as $user) {
    //                 if (!is_array($user)) continue;

    //                 $employeeFieldMap = [
    //                     'writerPay' => ['id_field' => 'writerName', 'payment_field' => 'paymentAmount', 'date_field' => 'paymentDate', 'status_field' => 'paymentStatus', 'db_type' => 'writer'],
    //                     'reviewerPay' => ['id_field' => 'reviewerName', 'payment_field' => 'reviewerPayment', 'date_field' => 'reviewerPaymentDate', 'status_field' => 'reviewerPaymentStatus', 'db_type' => 'reviewer'],
    //                     'statisticanPay' => ['id_field' => 'statisticanName', 'payment_field' => 'statisticanPayment', 'date_field' => 'statisticanPaymentDate', 'status_field' => 'statisticanPaymentStatus', 'db_type' => 'statistican'],
    //                     'journalPay' => ['id_field' => 'journalName', 'payment_field' => 'journalPayment', 'date_field' => 'journalPaymentDate', 'status_field' => 'journalPaymentStatus', 'db_type' => 'publication_manager'],
    //                 ];

    //                 $mapping = $employeeFieldMap[$type];
    //                 $employee_id = $user[$mapping['id_field']] ?? null;

    //                 if (!$employee_id) continue;

    //                 $paymentEpD = EmployeePaymentDetails::where('project_id', $request->project_id)
    //                     ->where('payment_id', $payment->id)
    //                     ->where('employee_id', $employee_id)
    //                     ->first();

    //                 if ($paymentEpD) {
    //                     // Update existing record
    //                     $paymentEpD->payment = $user[$mapping['payment_field']] ?? 0;
    //                     $paymentEpD->payment_date = $user[$mapping['date_field']] ?? null;
    //                     $paymentEpD->status = $user[$mapping['status_field']] ?? null;
    //                     $paymentEpD->type = $mapping['db_type'];
    //                     $paymentEpD->created_date = now();
    //                     $paymentEpD->save();
    //                 } else {
    //                     // Create new record
    //                     EmployeePaymentDetails::create([
    //                         'project_id' => $request->project_id,
    //                         'payment_id' => $payment->id,
    //                         'employee_id' => $employee_id,
    //                         'payment' => $user[$mapping['payment_field']] ?? 0,
    //                         'payment_date' => $user[$mapping['date_field']] ?? null,
    //                         'status' => $user[$mapping['status_field']] ?? null,
    //                         'type' => $mapping['db_type'],
    //                         'created_date' => now(),
    //                     ]);
    //                 }
    //             }
    //         }
    //     }

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Payment successfully updated.',
    //         'payment_id' => $payment->id
    //     ]);
    // }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $details = PaymentStatusModel::find($id);
        $details->is_deleted = 1;
        $details->status = 0;
        $details->save();

        return response()->json($details);
    }

    public function deletePaymentById(Request $request)
    {
        $paymentId = $request->query('payment_id');
        $status = $request->query('status');
        $paymentDetailId = $request->query('payment_detail_id');

        DB::beginTransaction();

        try {
            // Delete payment detail
            PaymentDetails::where('payment_id', $paymentId)
                ->where('id', $paymentDetailId)
                ->delete();

            // Delete payment log
            PaymentLogs::where('payment_id', $paymentId)
                ->where('payment_status', $status)
                ->delete();

            // Get latest payment log after deletion
            $paymentLog = PaymentLogs::where('payment_id', $paymentId)
                ->orderBy('id', 'desc')
                ->first();

            // Update payment status if log exists
            if ($paymentLog) {
                PaymentStatusModel::where('id', $paymentId)->update([
                    'payment_status' => $paymentLog->payment_status,
                    'reference_number' => $paymentLog->reference_number,
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Payment deleted successfully',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Failed to delete payment',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function storePaymentData(Request $request)
    {
        // Log::info('Entry data.', $request->all());

        $existingPayment = PaymentStatusModel::where('project_id', $request->project_id)->exists();

        if ($existingPayment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment for this project already exists. Duplicate payments are not allowed.',
            ], 400);
        }

        // Save payment status data
        $payment = new PaymentStatusModel;
        $payment->project_id = $request->project_id;
        $payment->payment_status = $request->payment_status;
        $payment->discounts = $request->discount;
        $payment->reference_number = $request->reference_number ?? '';
        $filePath = null;

        if ($request->hasFile('reference_number_file') && $request->file('reference_number_file')->isValid()) {
            // Get the uploaded file
            $file = $request->file('reference_number_file');

            // Get the original name without extension
            $originalNameWithoutExtension = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

            // Generate a unique name for the file
            $uniqueName = $originalNameWithoutExtension.'_'.time().'.'.$file->extension();

            $path = public_path('payment_screenshots');

            if (! is_dir($path)) {
                mkdir($path, 0775, true);
            }

            $file->move($path, $uniqueName);

            $filePath = $uniqueName;

            // Log the details of the file upload process
            // Log::info('File uploaded successfully:', [
            //     'original_name' => $file->getClientOriginalName(),
            //     'unique_name' => $uniqueName,
            //     'path' => $path,
            //     'file_path' => $filePath,
            // ]);
        } else {
            Log::warning('No valid file uploaded or file upload failed.');
        }

        if ($filePath) {
            $payment->reference_number_file = $filePath;
        } else {
            $payment->reference_number_file = null;
        }

        $payment->created_by = $request->created_by;
        $payment->save();

        if (
            (! empty($request->writerPay) && is_array($request->writerPay)) ||
            (! empty($request->reviewerPay) && is_array($request->reviewerPay)) ||
            (! empty($request->statisticanPay) && is_array($request->statisticanPay))
        ) {
            // Process writer payments
            if (! empty($request->writerPay) && is_array($request->writerPay)) {
                foreach ($request->writerPay as $user) {
                    $paymentEpD = new EmployeePaymentDetails;
                    $paymentEpD->project_id = $request->project_id;
                    $paymentEpD->payment_id = $payment->id;
                    $paymentEpD->employee_id = $user['writerName'] ?? null;
                    $paymentEpD->payment = $user['paymentAmount'] ?? 0;
                    $paymentEpD->payment_date = $user['paymentDate'];
                    $paymentEpD->status = $user['paymentStatus'];
                    $paymentEpD->type = 'writer';
                    $paymentEpD->created_date = date('Y-m-d H:i:s');
                    $paymentEpD->save();
                }
            }

            // Process reviewer payments
            if (! empty($request->reviewerPay) && is_array($request->reviewerPay)) {
                foreach ($request->reviewerPay as $user) {
                    $paymentEpD = new EmployeePaymentDetails;
                    $paymentEpD->project_id = $request->project_id;
                    $paymentEpD->payment_id = $payment->id;
                    $paymentEpD->employee_id = $user['reviewerName'] ?? null;
                    $paymentEpD->payment = $user['reviewerPayment'] ?? 0;
                    $paymentEpD->payment_date = $user['reviewerPaymentDate'];
                    $paymentEpD->status = $user['reviewerPaymentStatus'];
                    $paymentEpD->type = 'reviewer';
                    $paymentEpD->created_date = date('Y-m-d H:i:s');
                    $paymentEpD->save();
                }
            }

            // Process statistician payments
            if (! empty($request->statisticanPay) && is_array($request->statisticanPay)) {
                foreach ($request->statisticanPay as $user) {
                    $paymentEpD = new EmployeePaymentDetails;
                    $paymentEpD->project_id = $request->project_id;
                    $paymentEpD->payment_id = $payment->id;
                    $paymentEpD->employee_id = $user['statisticanName'] ?? null;
                    $paymentEpD->payment = $user['statisticanPayment'] ?? 0;
                    $paymentEpD->payment_date = $user['statisticanPaymentDate'];
                    $paymentEpD->status = $user['statisticanPaymentStatus'];
                    $paymentEpD->type = 'statistican';
                    $paymentEpD->created_date = date('Y-m-d H:i:s');
                    $paymentEpD->save();
                }
            }
        }

        $activity = new ProjectActivity;
        $activity->project_id = $request->project_id;
        $activity->activity = 'Project payment added successfully';
        $activity->created_by = $request->created_by;
        $activity->created_date = date('Y-m-d H:i:s');
        $activity->save();

        if (! empty($request->payment_status)) {
            PaymentLogs::create([
                'project_id' => $request->project_id,
                'payment_id' => $payment->id,
                'payment_status' => $request->payment_status,
                'created_by' => $request->created_by,
                'created_date' => date('Y-m-d H:i:s'),
            ]);
        }

        if (! empty($request->payment_details)) {
            foreach ($request->payment_details as $pay) {
                PaymentDetails::create([
                    'payment_id' => $payment->id,
                    'payment' => $pay['amount'],
                    'payment_date' => $pay['date'],
                ]);
            }
        } else {
            Log::warning('No payment details provided.');
        }

        return response()->json([
            'success' => true,
            'paymentDetails' => 'Payment created successfully',
        ]);
    }

    public function paymentDelete(Request $request, $id)
    {
        // Find the payment detail by ID
        $paymentDetail = PaymentDetails::find($id);

        $createdBy = $request->query('created_by');
        $projectId = $request->query('project_id');

        if (! $paymentDetail) {
            return response()->json(['message' => 'Payment detail not found'], 404);
        }
        $paymentDetail->is_deleted = 1;
        $paymentDetail->save();

        $paymentDetaild = PaymentStatusModel::where('id', $paymentDetail->payment_id)->first();

        // Log::info('Payment Details:', ['payment' => $paymentDetail]);
        $activity = new ProjectActivity;
        $activity->project_id = $paymentDetaild->project_id;
        $activity->activity = 'Payment deleted successfully';
        $activity->created_by = $createdBy;
        $activity->created_date = date('Y-m-d H:i:s');
        $activity->save();

        return response()->json(['message' => 'Payment detail deleted successfully']);
    }

    public function download($filename)
    {
        $filePath = public_path('uploads/'.$filename);

        // Check if the file exists
        if (! file_exists($filePath)) {
            return response()->json(['error' => 'File not found'], 404);
        }

        // Generate the download response
        $response = response()->download($filePath, $filename);

        // Set custom headers
        $response->headers->set('Content-Type', 'application/pdf'); // Set the correct MIME type (example for PDFs)
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0'); // Disable caching

        return $response;
    }

    public function getPaymentList(Request $request)
    {
        // Count total payments for each status
        $paymentPendingCount = PaymentStatusModel::select('id')->where('payment_status', 'final_payment_pending')
            ->whereHas('projectData', function ($query) {
                $query->where('is_deleted', 0);
                // ->where('process_status', '!=', 'completed');
            })
            ->count();
        $paymentPendingIds = PaymentStatusModel::where('payment_status', 'final_payment_pending')
            ->whereHas('projectData', function ($query) {
                $query->where('is_deleted', 0);
                // ->where('process_status', '!=', 'completed');
            })
            ->pluck('project_id')
            ->toArray();
        $advancePendingCount = PaymentStatusModel::select('id')->where('payment_status', 'advance_pending')
            ->whereHas('projectData', function ($query) {
                $query->where('is_deleted', 0)
                    ->where('process_status', '!=', 'completed');
            })
            ->count();

        $advancePendingIds = PaymentStatusModel::where('payment_status', 'advance_pending')
            ->whereHas('projectData', function ($query) {
                $query->where('is_deleted', 0)
                    ->where('process_status', '!=', 'completed');
            })
            ->pluck('project_id')
            ->toArray();
        $partialPaymentPendingCount = PaymentStatusModel::select('id')->where('payment_status', 'partial_payment_pending')
            ->whereHas('projectData', function ($query) {
                $query->where('is_deleted', 0)
                    ->where('process_status', '!=', 'completed');
            })

            ->count();
        $partialPaymentPendingIds = PaymentStatusModel::where('payment_status', 'partial_payment_pending')
            ->whereHas('projectData', function ($query) {
                $query->where('is_deleted', 0)
                    ->where('process_status', '!=', 'completed');
            })
            ->pluck('project_id')
            ->toArray();

        // Get total payments for each status
        $paymentsPending = PaymentStatusModel::where('payment_status', 'final_payment_pending')
            ->with('paymentData', 'paymentLData')
            ->whereHas('projectData', function ($query) {
                $query->where('is_deleted', 0);
                // ->where('process_status', '!=', 'completed');
            })

            ->get();
        $advancePending = PaymentStatusModel::where('payment_status', 'advance_pending')
            ->with('paymentData', 'paymentLData')
            ->whereHas('projectData', function ($query) {
                $query->where('is_deleted', 0)
                    ->where('process_status', '!=', 'completed');
            })
            ->get();
        $partialPaymentPending = PaymentStatusModel::where('payment_status', 'partial_payment_pending')
            ->with('paymentData')
            ->whereHas('projectData', function ($query) {
                $query->where('is_deleted', 0)
                    ->where('process_status', '!=', 'completed');
            })
            ->whereHas('paymentData', function ($query) {
                $query->where('payment_type', 'partial_payment_pending');
            })
            ->get();

        // Fetch payments that are verified (is_verify = 1)
        $completedPayments = PaymentStatusModel::where('is_verify', 1)
            ->with('paymentData', 'paymentLData')
            ->whereHas('projectData', function ($query) {
                $query->where('is_deleted', 0);
                // ->where('process_status', '!=', 'completed');
            })
            ->get();

        // Sum the payments for each status
        $totalPaymentPending = 0;
        $totalAdvancePending = 0;
        $totalPartialPaymentPending = 0;
        $totalCompletedPayment = 0;

        // Sum payments for 'payment_pending'
        foreach ($paymentsPending as $payment) {
            $totalPaymentPending += $payment->paymentData->where('payment_type', 'final_payment_pending')->sum('payment');
        }

        // Sum payments for 'advance_pending'
        foreach ($advancePending as $payment) {
            $totalAdvancePending += $payment->paymentData->where('payment_type', 'advance_pending')->sum('payment');
        }

        // Sum payments for 'partial_payment_pending'
        foreach ($partialPaymentPending as $payment) {
            $totalPartialPaymentPending += $payment->paymentData->where('payment_type', 'partial_payment_pending')->sum('payment');
        }

        // Sum payments for 'completed/verified' payments
        foreach ($completedPayments as $payment) {
            $totalCompletedPayment += $payment->paymentData->whereIn('payment_type', ['partial_payment_pending', 'final_payment_pending', 'advance_pending', 'completed'])->sum('payment');
        }
        // Count the total number of completed/verified payments
        $completedPaymentsCount = PaymentStatusModel::where('is_verify', 1)
            ->with('paymentData', 'paymentLData')
            ->whereHas('projectData', function ($query) {
                $query->where('is_deleted', 0);
                // ->where('process_status', '!=', 'completed');
            })
            ->count();
        $completedPaymentsIds = PaymentStatusModel::where('is_verify', 1)
            ->with('paymentData', 'paymentLData')
            ->whereHas('projectData', function ($query) {
                $query->where('is_deleted', 0)
                    ->where('process_status', '!=', 'completed');
            })
            ->pluck('project_id')
            ->toArray();

        $projects = EntryProcessModel::with(['paymentProcess.paymentLog'])
            ->select('id', 'entry_date', 'title', 'project_id', 'type_of_work', 'client_name')
            ->where('is_deleted', 0)
            ->get();

        $paymentProject = PaymentStatusModel::with(['paymentWEmpData', 'paymentREmpData', 'paymentSEmpData', 'projectData'])->get();

        $paymentProjectDetails = $paymentProject->flatMap(function ($project) {
            $paymentData = [];

            // Writer Payment
            if (! empty($project->paymentREmpData->writer_payment) && isset($project->writerId)) {
                $projectscount = EntryProcessModel::select('id')->where('writer', $project->writerId->id)
                    ->where('is_deleted', 0)
                    ->whereIn('id', PaymentStatusModel::pluck('project_id'))
                    ->count();

                $paymentData[] = [
                    'total_project' => $projectscount,
                    'id' => $project->writerId->id,
                    'type' => 'writer',
                    'totalname' => $project->writerId->employee_name,
                    'totalproject' => 0,
                    'payment' => $project->writer_payment,
                    'payment_status' => $project->writer_payment_status,
                    'payment_date' => $project->writer_payment_date,

                ];
            }

            // Reviewer Payment
            if (! empty($project->reviewer_payment) && isset($project->reviewerID)) {
                $projectscountReviewer = EntryProcessModel::select('id')->where('reviewer', $project->reviewerID->id)
                    ->where('is_deleted', 0)
                    ->whereIn('id', PaymentStatusModel::pluck('project_id'))
                    ->count();

                $paymentData[] = [
                    'total_project' => $projectscountReviewer,
                    'id' => $project->reviewerID->id,
                    'type' => 'reviewer',
                    'totalname' => $project->reviewerID->employee_name,
                    'totalproject' => 0,
                    'payment' => $project->reviewer_payment,
                    'payment_status' => $project->reviewer_payment_status,
                    'payment_date' => $project->reviewer_payment_date,
                ];
            }

            // Statistician Payment
            if (! empty($project->statistican_payment) && isset($project->statisticanID)) {

                $projectscountStatistican = EntryProcessModel::select('id')->where('statistican', $project->statisticanID->id)
                    ->where('is_deleted', 0)
                    ->whereIn('id', PaymentStatusModel::pluck('project_id'))
                    ->count();

                $paymentData[] = [
                    'total_project' => $projectscountStatistican,
                    'id' => $project->statisticanID->id,
                    'type' => 'statistican',
                    'totalname' => $project->statisticanID->employee_name,
                    'totalproject' => 0,
                    'payment' => $project->statistican_payment,
                    'payment_status' => $project->statistican_payment_status,
                    'payment_date' => $project->statistican_payment_date,
                ];
            }

            return $paymentData;
        })->groupBy('id')->map(function ($items) {
            return [
                'id' => $items->first()['id'],
                'type' => $items->first()['type'],
                'totalname' => $items->first()['totalname'],
                'total_project' => $items->first()['total_project'],
                'totalpayment' => $items->sum('payment'),
                'payment_status' => $items->first()['payment_status'],
                'payment_date' => $items->first()['payment_date'],
            ];
        })->values();

        foreach ($projects as $project) {
            if ($project->paymentProcess) {
                $payment_id = $project->paymentProcess->id;
                $project_id = $project->paymentProcess->project_id;
                $paymentstatus = $project->paymentProcess->payment_status;
                $project->paymentstatus = $paymentstatus;
                $project->is_verify = $project->paymentProcess->is_verify;
                $project->payment_id = $payment_id;

                $paymentdetails = PaymentDetails::where('payment_id', $payment_id)
                    ->where('is_deleted', 0)
                    ->get();
                $total_cost = $paymentdetails->sum('payment');
                $project->total_cost = $total_cost ?: '-';

                $paymentdate = '-';
                $paymentlogs = PaymentLogs::where('project_id', $project_id)
                    ->where('payment_id', $payment_id)
                    ->where('payment_status', $paymentstatus)
                    ->latest()
                    ->first();

                if ($paymentlogs) {
                    if ($paymentlogs->created_date) {
                        $project->paymentdate = $paymentlogs->created_date;
                    } else {
                        $project->paymentdate = '-';
                    }
                } else {
                    $project->paymentdate = '-';
                }
            } else {
                $project->paymentdate = '-';
                $project->paymentstatus = '-';
                $project->total_cost = '-';
                $project->is_verify = '-';
                $project->payment_id = '-';
            }
        }

        // Return the total counts and sums
        return response()->json([
            'totalPaymentPendingCount' => $paymentPendingCount,
            'totalAdvancePendingCount' => $advancePendingCount,
            'totalPartialPaymentPendingCount' => $partialPaymentPendingCount,
            'totalPaymentPending' => $totalPaymentPending,
            'totalAdvancePending' => $totalAdvancePending,
            'totalPartialPaymentPending' => $totalPartialPaymentPending,
            'totalCompletedPaymentCount' => $completedPaymentsCount,
            'totalCompletedPayment' => $totalCompletedPayment,
            'projects' => $projects,
            'paymentProjectDetails' => $paymentProjectDetails,
            'advancePendingIds' => $advancePendingIds,
            'completedPaymentsIds' => $completedPaymentsIds,
            'partialPaymentPendingIds' => $partialPaymentPendingIds,
            'paymentPendingIds' => $paymentPendingIds,
        ]);
    }

    public function statusChange(Request $request)
    {
        // Get the payment_id and project_id from the request
        $payment_id = $request->input('payment_id');
        $project_id = $request->input('project_id');
        $created_by = $request->input('created_by');

        // Find the payment based on project_id and payment_id
        $payment = PaymentStatusModel::where('project_id', $project_id)
            ->where('id', $payment_id)
            ->first();

        // Check if the payment exists
        if ($payment) {
            // Update the 'is_verify' status to 1 (verified)
            $payment->is_verify = 1;
            $payment->save();

            $activity = new ProjectActivity;
            $activity->project_id = $project_id;
            $activity->activity = 'Project payment verify successfully';
            $activity->created_by = $created_by;
            $activity->created_date = date('Y-m-d H:i:s');
            $activity->save();

            // Return a success response
            return response()->json(['status' => 'success', 'message' => 'Payment status updated']);
        } else {
            // Return an error if the payment was not found
            return response()->json(['status' => 'error', 'message' => 'Payment not found'], 404);
        }
    }

    public function getPaymentDetails(Request $request, string $id)
    {
        $type = $request->query('type');
        $projectPayments = [];

        if (! in_array($type, ['writer', 'reviewer', 'statistican', 'journal'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid type parameter',
            ], 400);
        }

        // Define field mappings
        $typeFieldMap = [
            'writer' => 'writer_id',
            'reviewer' => 'reviewer_id',
            'statistican' => 'statistican_id',
            'journal' => 'journal_id',
        ];

        $paymentFieldMap = [
            'writer' => 'writer_payment',
            'reviewer' => 'reviewer_payment',
            'statistican' => 'statistican_payment',
            'journal' => 'journal_payment',
        ];

        $dateFieldMap = [
            'writer' => 'writer_payment_date',
            'reviewer' => 'reviewer_payment_date',
            'statistican' => 'statistican_payment_date',
            'journal' => 'journal_payment_date',
        ];

        $statusFieldMap = [
            'writer' => 'writer_payment_status',
            'reviewer' => 'reviewer_payment_status',
            'statistican' => 'statistican_payment_status',
            'journal' => 'journal_payment_status',
        ];

        // Get only the projects that exist in PaymentStatusModel
        $validProjectIds = PaymentStatusModel::where($typeFieldMap[$type], $id)->pluck('project_id');

        $projects = EntryProcessModel::whereIn('id', $validProjectIds)
            ->select('id', 'entry_date', 'title', 'project_id', 'type_of_work', 'email', 'institute', 'department', 'profession', 'budget', 'process_status', 'journal', 'journal_status_date', 'journal_assigned_date', 'journal_status', 'hierarchy_level', 'created_by', 'project_status', 'assign_by', 'assign_date', 'projectduration')
            ->where($type, $id)
            ->where('is_deleted', 0)
            ->get();

        foreach ($projects as $project) {
            $payment = PaymentStatusModel::where('project_id', $project->id)
                ->where($typeFieldMap[$type], $id)
                ->first();

            $projectPayments[] = [
                'projectname' => $project->title ?? 'N/A',
                'budget' => $project->budget ?? 0,
                'amountpaid' => $payment->{$paymentFieldMap[$type]} ?? 0,
                'date' => isset($payment->{$dateFieldMap[$type]}) ? $payment->{$dateFieldMap[$type]} : '-',
                'status' => $payment->{$statusFieldMap[$type]} ?? '-',
            ];
        }

        return response()->json([
            'success' => true,
            'projects' => $projectPayments,
        ]);
    }

    public function getFreelancerDetails()
    {

        $people = People::where('employee_type', 'freelancers')->get();

        // $totalProjectsFreelancer = People::select('id', 'position', 'employee_name', 'employee_type')
        // ->where('employee_type', '=', 'freelancers')
        // ->get();

        $freelancerDetails = EmployeePaymentDetails::with(['UserDateF'])
            ->where('type', '!=', 'publication_manager')
            ->get();

        $grouped = $freelancerDetails->groupBy('employee_id')->map(function ($items, $employeeId) {
            $totalProjectsFreelancer = ProjectAssignDetails::where('assign_user', $employeeId)->where('status', '!=', 'rejected')->count();
            $totalProjectsFreelancers = ProjectAssignDetails::where('assign_user', $employeeId)
                ->where('status', '!=', 'rejected')
                ->select('project_id')
                ->get();

            return [
                'freelancer_id' => $employeeId,
                'freelancer_name' => optional($items->first()->UserDateF)->employee_name,
                'freelancer_project_count' => $totalProjectsFreelancer,
                'freelancer_project_id' => $totalProjectsFreelancers,
                'freelancer_total_payment' => $items->sum(function ($item) {
                    return (float) $item->payment ?? 0;
                }),
                'freelancerPendingAmount' => $items->where('status', 'pending')->sum(function ($item) {
                    return (float) $item->payment ?? 0;
                }),
                'freelancerPaidAmount' => $items->where('status', 'paid')->sum(function ($item) {
                    return (float) $item->payment ?? 0;
                }),
            ];
        })->values();

        return response()->json($grouped);
    }
}
