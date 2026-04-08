<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Models\DepartmentModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Models\EntryProcessModel;
use App\Models\InstitutionModel;
use App\Models\ProfessionModel;
use App\Models\PaymentStatusModel;
use App\Models\PendingStatusModel;
use App\Models\PaymentDetails;
use App\Models\EntryDocument;
use App\Models\User;
use App\Models\ProjectLogs;
use Carbon\Carbon;
use App\Models\ProjectStatus;
use App\Models\People;
use Illuminate\Support\Facades\Mail;
use App\Mail\AssignmentNotificationMail;
use App\Mail\ClientEmail;
use App\Mail\TaskEmail;
use App\Mail\RejectEmail;
use App\Mail\TaskCompleteEmail;
use App\Models\RejectReason;
use App\Models\EntryDocumentsList;



class EntryProcessController extends Controller

{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $start_date = $request->query('start_date') ?? now()->format('Y-m-d');
        $end_date = $request->query('end_date') ?? now()->format('Y-m-d');
        $type_of_work = $request->query('type_of_work') ?? 'all';
        $institutions = $request->query('institutions') ?? 'all';
        $authorname = $request->query('author_name') ?? 'all';
        $details = EntryProcessModel::with(['paymentProcess'])->where('is_deleted', 0)
            ->whereBetween('entry_date', [$start_date, $end_date]);
        if (isset($type_of_work) && $type_of_work != 'all') {
            $details = $details->where('type_of_work', $type_of_work);
        }
        if (isset($institutions) && $institutions != 'all') {
            $details = $details->where('institute', $institutions);
        }
        if (in_array($authorname, ['writer', 'reviewer', 'statistican', 'journal'])) {
            $query->whereNotNull($authorname);
        }
        $details = $details->orderBy('created_at', 'desc')->get();
        $typeofwork = EntryProcessModel::where('is_deleted', 0)
            ->select('type_of_work')
            ->groupBy('type_of_work')
            ->get();
        $institutionsList = InstitutionModel::where('is_deleted', 0)
            ->where('status', 'Active')
            ->select('name', 'id')
            ->get();
        $authornameList = User::with(['createdByUser'])
            ->whereIn('position', [7, 8, 10, 11])
            ->select('id', 'employee_name', 'profile_image', 'position')
            ->groupBy('id')
            ->get();
        return response()->json([
            'details' => $details,
            'typeofwork' => $typeofwork,
            'institutions' => $institutionsList,
            'authorname' => $authornameList
        ]);
    }


    public function getEmployeeName()
    {
        $positions = [7, 8, 11, 10];

        // Convert positions to lowercase for consistent comparison
        // $lowercasePositions = array_map('strtolower', $positions);

        try {
            $employees = DB::connection('mysql_medics_hrms')
                ->table('employee_details')
                ->whereIn('position', $positions)
                ->join('roles', 'employee_details.position', '=', 'roles.id')
                ->select(
                    'roles.name as position',
                    'employee_details.employee_name as employee_name',
                    'employee_details.id as employee_id'
                ) // Correctly closed select method
                ->get();

            $result = [];
            foreach ($employees as $employee) {
                $position = strtolower($employee->position);

                // Ensure the position key exists in the result array
                if (!isset($result[$position])) {
                    $result[$position] = [];
                }

                // Add the employee details to the correct position key in the result array
                $result[$position][] = [
                    'id' => $employee->employee_id,
                    'name' => $employee->employee_name
                ];
            }

            return response()->json($result);
        } catch (\Exception $e) {
            // Log the exception and return an error response
            Log::error($e->getMessage());
            return response()->json(['error' => 'Unable to fetch data'], 500);
        }
    }


    public function store(Request $request)
    {
        Log::info('File uploaded successfully:', $request->all());

        $selectedOption = $request->type_of_work;
        $customId = '';

        DB::transaction(function () use ($selectedOption, $request, &$customId) {
            // Generate custom ID
            $lastEntry = EntryProcessModel::where('type_of_work', $selectedOption)
                ->orderBy('id', 'desc')
                ->lockForUpdate()
                ->first();

            $increment = $lastEntry ? (int)substr($lastEntry->project_id, strlen($selectedOption) + 1) + 1 : 1;
            $formattedIncrement = str_pad($increment, 3, '0', STR_PAD_LEFT);
            $customId = $selectedOption . '-' . $formattedIncrement;

            // Check for duplicate custom ID
            if (EntryProcessModel::where('project_id', $customId)->exists()) {
                throw new \Exception('Duplicate custom ID found. Please try again.');
            }

            // Create new entry
            $details = new EntryProcessModel;
            $details->entry_date = $request->entry_date ?? null;
            $details->title = $request->title ?? null;
            $details->project_id = $customId;
            $details->type_of_work = $request->type_of_work ?? null;
            $details->others = $request->others ?? null;
            $details->client_name = $request->client_name ?? null;
            $details->email = $request->email ?? null;
            $details->contact_number = $request->contact_number ?? null;
            $details->institute = $request->institute ?? null;
            $details->department = $request->department ?? null;
            $details->profession = $request->profession ?? null;
            $details->budget = $request->budget ?? null;
            $details->process_status = $request->process_status ?? 'not_assigned';
            $details->process_date = $request->process_date ?? null;
            $details->hierarchy_level = $request->hierarchy_level ?? null;
            $details->comment_box = $request->comment_box ?? null;
            $details->else_project_manager = $request->else_project_manager;

            //writer data 
            $details->writer = $request->writer ?? null;
            $details->writer_assigned_date = $request->writer_assigned_date ?? null;
            $details->writer_status = $request->writer_status ?? null;
            $details->writer_status_date = $request->writer_status_date ?? null;
            $details->writer_project_duration = $request->writer_project_duration ?? null;
            $details->writer_duration_unit = $request->writer_duration_unit ?? null;

            //reviewer
            $details->reviewer = $request->reviewer ?? null;
            $details->reviewer_assigned_date = $request->reviewer_assigned_date ?? null;
            $details->reviewer_status = $request->reviewer_status ?? null;
            $details->reviewer_status_date = $request->reviewer_status_date ?? null;
            $details->reviewer_project_duration = $request->reviewer_project_duration ?? null;
            $details->reviewer_duration_unit = $request->reviewer_duration_unit ?? null;

            //statistican
            $details->statistican = $request->statistican ?? null;
            $details->statistican_assigned_date = $request->statistican_assigned_date ?? null;
            $details->statistican_status = $request->statistican_status ?? null;
            $details->statistican_status_date = $request->statistican_status_date ?? null;
            $details->statistican_project_duration = $request->statistican_project_duration ?? null;
            $details->statistican_duration_unit = $request->statistican_duration_unit ?? null;

            //jornal
            $details->journal = $request->journal ?? null;
            $details->journal_assigned_date = $request->journal_assigned_date ?? null;
            $details->journal_status = $request->journal_status ?? null;
            $details->journal_status_date = $request->journal_status_date ?? null;
            $details->journal_duration_unit = $request->journal_duration_unit ?? null;
            $details->journal_project_duration = $request->journal_project_duration ?? null;
            $details->status = $request->status ?? '1';
            $details->is_deleted = $request->is_deleted ?? 0;
            $details->created_by = $request->created_by ?? '-';
            $details->save();


            //project status

            $projectstatus = new ProjectStatus;
            $projectstatus->project_id = $details->id;
            $projectstatus->writer_id = $request->writer ?? null;
            $projectstatus->reviewer_id = $request->reviewer ?? null;
            $projectstatus->statistican_id = $request->statistican ?? null;
            $projectstatus->journal_id = $request->journal ?? null;

            $projectstatus->save();

            // Project Logs
            if (!empty($request->writer)) {

                ProjectLogs::create([
                    'project_id' => $details->id,
                    'employee_id' => $request->writer,
                    'assigned_date' => $request->writer_assigned_date,
                    'status' => $request->writer_status,
                    'status_date' => $request->writer_status_date,
                    'status_type' => 'writer', // Static value
                    'created_by' => $request->created_by,
                    'created_date' => date('Y-m-d H:i:s')

                ]);
            }

            if (!empty($request->reviewer)) {
                ProjectLogs::create([
                    'project_id' => $details->id,
                    'employee_id' => $request->reviewer,
                    'assigned_date' => $request->reviewer_assigned_date,
                    'status' => $request->reviewer_status,
                    'status_date' => $request->reviewer_status_date,
                    'status_type' => 'reviewer',
                    'created_by' => $request->created_by,
                    'created_date' => date('Y-m-d H:i:s')
                ]);
            }

            if (!empty($request->statistican)) {
                ProjectLogs::create([
                    'project_id' => $details->id,
                    'employee_id' => $request->statistican,
                    'assigned_date' => $request->statistican_assigned_date,
                    'status' => $request->statistican_status,
                    'status_date' => $request->statistican_status_date,
                    'status_type' => 'statistican',
                    'created_by' => $request->created_by,
                    'created_date' => date('Y-m-d H:i:s')
                ]);
            }

            if (!empty($request->journal)) {
                ProjectLogs::create([
                    'project_id' => $details->id,
                    'employee_id' => $request->journal,
                    'assigned_date' => $request->journal_assigned_date,
                    'status' => $request->journal_status,
                    'status_date' => $request->journal_status_date,
                    'status_type' => 'journal',
                    'created_by' => $request->created_by,
                    'created_date' => date('Y-m-d H:i:s')
                ]);
            }

            $roles = [
                'writer' => 'Writer',
                'reviewer' => 'Reviewer',
                'statistican' => 'Statistician',
                'journal' => 'Journal'
            ];

            foreach ($roles as $key => $role) {
                if (!empty($request->$key)) {
                    $userDetails = User::where('id', $request->$key)->first();

                    if ($userDetails) {
                        if (!empty($userDetails->email_address)) {
                            $durationKey = $key . '_project_duration'; // Dynamically form the duration key
                            $durationUnit = $key . '_duration_unit'; // Dynamically form the duration unit key
                            try {
                                Mail::to($userDetails->email_address)->send(new AssignmentNotificationMail([
                                    'name' => $userDetails->employee_name,
                                    'role' => $role,
                                    'project_id' => $customId,
                                    'title' => $details->title,
                                    'duration' => $details->$durationKey, // Dynamically fetch duration
                                    'unit' => $details->$durationUnit // Dynamically fetch duration unit
                                ]));
                                Log::info("Email sent to {$role} ({$userDetails->email_address}).");
                            } catch (\Exception $e) {
                                Log::error("Failed to send email to {$role}: " . $e->getMessage());
                            }
                        } else {
                            Log::error("{$role} email is empty or invalid.");
                        }
                    } else {
                        Log::error("{$role} not found.");
                    }
                } else {
                    Log::error("No {$role} ID provided.");
                }
            }


            // Check if documents are provided and are an array
            // if ($request->has('entryprocess_documents') && is_array($request->entryprocess_documents)) {
            //     $entryprocessDocuments = []; // Initialize an empty array to store document details

            //     foreach ($request->entryprocess_documents as $index => $document) {
            //         // Ensure the 'option' and 'file' keys exist in the array
            //         if (isset($document['specificOption']) && isset($document['file']) && $document['file']) {
            //             $file = $document['file'];

            //             // Get the original name without extension
            //             // $originalNameWithoutExtension = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

            //             // Generate a unique name for the file
            //             $uniqueName = $customId . '-' . time() . '-' . uniqid() . '.' . $file->extension();

            //             // Set the storage path (in this case, public/documents directory)
            //             $path = public_path('uploads');

            //             // Ensure the directory exists, if not create it
            //             if (!is_dir($path)) {
            //                 mkdir($path, 0775, true);
            //             }

            //             // $filePath = $file->store('uploads', 'public'); // Store the file
            //             $file->move($path, $uniqueName);

            //             $filePath = $uniqueName;

            //             // Add document details in the required format
            //             $entryprocessDocuments[] = [
            //                 'option' => $document['specificOption'],
            //                 'file' => $filePath,
            //             ];

            //             // Create document record in the database
            //             $details->documents()->create([
            //                 'select_document' => json_encode($document['specificOption'], ture),
            //                 'file' => json_encode($filePath, ture),
            //                 'created_by' => $request->created_by ?? '-'
            //             ]);
            //         }
            //     }

            //     // Return the documents in the desired format
            //     return response()->json([
            //         'entryprocess_documents' => $entryprocessDocuments
            //     ]);
            // }

            if ($request->has('entryprocess_documents') && is_array($request->entryprocess_documents)) {
                $entryprocessDocuments = []; // To store formatted document data for response
                $defaultSpecificOption = null; // Store the first valid specificOption

                foreach ($request->entryprocess_documents as $document) {
                    // Validate that specificOption and file keys exist and are arrays
                    if (isset($document['file']) && is_array($document['file'])) {
                        // Use specificOption from the first valid entry if current specificOption is empty
                        if (isset($document['specificOption']) && is_array($document['specificOption']) && !empty($document['specificOption'])) {
                            $defaultSpecificOption = $document['specificOption']; // Set as default for subsequent entries
                        } elseif ($defaultSpecificOption !== null) {
                            $document['specificOption'] = $defaultSpecificOption; // Use default specificOption if it's available
                        }

                        $fileNames = [];

                        $entryDocument = new EntryDocument();
                        $entryDocument->entry_process_model_id = $details->id;
                        $entryDocument->select_document = json_encode($document['specificOption'], JSON_UNESCAPED_UNICODE);
                        $entryDocument->created_by = $request->created_by ?? '-';
                        $entryDocument->save();

                        // Process each file in the `file` array
                        foreach ($document['file'] as $file) {
                            if (!empty($file)) {
                                // Get original file name and extension
                                $originalName = $file->getClientOriginalName();
                                $originalExtension = $file->getClientOriginalExtension();

                                // Remove spaces, special characters, and underscores, and convert to lowercase
                                $cleanedName = strtolower(preg_replace('/[^a-z0-9]+/i', '-', pathinfo($originalName, PATHINFO_FILENAME)));
                                $cleanedName = str_replace('_', '', $cleanedName); // Remove underscores
                                // Generate unique file name while retaining the original extension
                                $uniqueName = $customId . '-' . time() . '-' . uniqid() . '.' . $originalExtension;

                                // Define the storage path
                                $path = public_path('uploads');

                                // Ensure the directory exists
                                if (!is_dir($path)) {
                                    mkdir($path, 0775, true);
                                }

                                // Move the file to the upload directory
                                $file->move($path, $uniqueName);

                                // Save file information in the `entry_documents_list` table
                                $documentList = new EntryDocumentsList();
                                $documentList->document_id = $entryDocument->id;
                                $documentList->file = $uniqueName; // Save the unique file name
                                $documentList->original_name = $cleanedName . '.' . $originalExtension; // Save the cleaned original name with the extension
                                $documentList->save();

                                // Add file name to the response array
                                $fileNames[] = $uniqueName;
                            }
                        }

                        // Format data for the response
                        $entryprocessDocuments[] = [
                            'specificOption' => $document['specificOption'],
                            'file' => $fileNames
                        ];
                    }
                }

                // Return a successful response
                return response()->json([
                    'entryprocess_documents' => $entryprocessDocuments
                ], 200);
            } else {
                return response()->json(['error' => 'Invalid input data'], 400);
            }
        });
    }



    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $details = EntryProcessModel::with([
            'institute',
            'department',
            'profession',
            'documents',
            'paymentProcess',
            'paymentProcess.paymentData', // Include nested relationship
            // 'writer',
            // 'reviewer',
            // 'journal',
            // 'statistican'
        ])
            ->where('is_deleted', 0)
            ->find($id);
        return response()->json($details);
    }

    public function showProjectView(string $id)
    {
        $details = EntryProcessModel::with([
            'institute',
            'department',
            'profession',
            'documents',
            'paymentProcess',
            'paymentProcess.paymentData', // Include nested relationship
            'writer',
            'reviewer',
            'journal',
            'statistican'
        ])
            ->where('is_deleted', 0)
            ->find($id);
        return response()->json($details);
    }

    public function projectView(Request $request, string $id)
    {

        $createdBy = $request->query('created_by');
        // Fetch project details with relationships
        $details = EntryProcessModel::with([
            'institute',
            'department',
            'profession',
            'documents',
            'paymentProcess',
            'paymentProcess.paymentData',
            // 'rejectReason',
            'rejectReason' => function ($query) use ($createdBy) {
                // Conditionally fetch rejectReason based on created_by
                if ($createdBy) {
                    $query->where('created_by', $createdBy);
                }
            },
            'activityData.replies',
            'writerData',
            'reviewerData',
            'journalData',
            'statisticanData',
            'projectStatus'
        ])
            ->where('is_deleted', 0)
            ->where('project_id', $id)
            ->first();

        if ($details) {
            // Fetch employee details using the `created_by` field
            $userhrms = DB::connection('mysql_medics_hrms')
                ->table('employee_details')
                ->where('id', $details->created_by)
                ->first();

            $assignedEmployee = DB::connection('mysql_medics_hrms')
                ->table('employee_details')
                ->where('id', $details->assign_by)
                ->select('id', 'employee_name', 'profile_image', 'position')
                ->first();

            // If the employee exists, fetch the role name based on the position
            if ($assignedEmployee) {
                // Get the role name based on the position
                $rolename = DB::connection('mysql_medics_hrms')
                    ->table('roles')
                    ->where('id', $assignedEmployee->position) // Use the position value to find the role
                    ->value('name'); // Assume 'name' is the column holding the role name
                // dd($rolename);
                // Attach the role name to the assignedEmployee object
                $assignedEmployee->role_name = $rolename;
                $assignedEmployee->role_id = $assignedEmployee->position;
            }

            if ($userhrms) {
                // Add employee details to the response
                $response = [
                    'project_details' => $details,
                    'employee_details' => [
                        'id' => $userhrms->id,
                        'employee_name' => $userhrms->employee_name,
                    ],
                    'assigned_employee' => $assignedEmployee,
                ];
            } else {
                // Handle case where employee is not found
                $response = [
                    'project_details' => $details,
                    'employee_details' => null,
                    'message' => 'Employee not found',
                ];
            }
        } else {
            // Handle case where project details are not found
            $response = [
                'project_details' => null,
                'message' => 'Project not found',
            ];
        }

        $employees = User::with(['createdByUser'])
            ->whereIn('position', [7, 8, 10, 11])
            ->select('id', 'employee_name', 'profile_image', 'position')
            ->get();

        $employees->each(function ($employee) {
            $rolename = DB::connection('mysql_medics_hrms')
                ->table('roles')
                ->where('id', $employee->position)
                ->first();

            $employee->role_name = $rolename;
        });

        // Return the response
        return response()->json([
            'response' => $response,
            'employees' => $employees
        ]);
    }

    //getting email from admin, projectmanager,team manager

    public function getEmails()
    {
        $positions = [13, 14];

        // Convert positions to lowercase for consistent comparison
        // $lowercasePositions = array_map('strtolower', $positions);

        try {
            $employees = User::with(['createdByUser'])->where('position', '!=', 'Admin')
                ->where('position', '!=', '13')
                ->where('position', '!=', '14')
                ->select('id', 'employee_name', 'profile_image', 'position')
                ->get();

            $result = [];
            foreach ($employees as $employee) {
                $position = strtolower($employee->position);

                // Ensure the position key exists in the result array
                if (!isset($result[$position])) {
                    $result[$position] = [];
                }

                // Add the employee details to the correct position key in the result array
                $result[$position][] = [
                    'id' => $employee->employee_id,
                    'email' => $employee->email_address
                ];
            }

            return response()->json($result);
        } catch (\Exception $e) {
            // Log the exception and return an error response
            Log::error($e->getMessage());
            return response()->json(['error' => 'Unable to fetch data'], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        Log::info('File uploaded successfully:', $request->all());

        $selectedOption = $request->type_of_work;
        $customId = '';

        DB::transaction(function () use ($selectedOption, $request, &$customId, $id) {
            // Fetch existing entry
            $details = EntryProcessModel::find($id);
            if (!$details) {
                throw new \Exception('Entry not found');
            }

            // // Check if type_of_work already exists for another record
            // $existingEntry = EntryProcessModel::where('type_of_work', $selectedOption)
            //     ->where('id', '!=', $id)
            //     ->first();

            // if ($existingEntry) {
            //     throw new \Exception("Type of work '$selectedOption' already exists in another record.");
            // }

            // Check if project_id already exists
            if ($details->project_id !== $request->project_id) {
                $duplicateProjectId = EntryProcessModel::where('project_id', $request->project_id)
                    ->where('id', '!=', $id)
                    ->where('is_deleted', 0)
                    ->exists();

                if ($duplicateProjectId) {
                    throw new \Exception("Project ID '{$request->project_id}' is already in use.");
                }
            }

            // Generate custom ID only if type_of_work changes
            if ($details->type_of_work !== $selectedOption) {
                $lastEntry = EntryProcessModel::where('type_of_work', $selectedOption)
                    ->orderBy('id', 'desc')
                    ->lockForUpdate()
                    ->first();

                $increment = $lastEntry ? (int)substr($lastEntry->project_id, strlen($selectedOption) + 1) + 1 : 1;
                $formattedIncrement = str_pad($increment, 3, '0', STR_PAD_LEFT);
                $customId = $selectedOption . '-' . $formattedIncrement;
            } else {
                $customId = $details->project_id; // Keep the existing project_id
            }

            // Update entry details
            $details->entry_date = $request->entry_date ;
            $details->title = $request->title;
            // $details->title = isset($request->title) ? trimWithEllipsis($request->title, 50) : null;

            $details->project_id = $customId;
            $details->type_of_work = $request->type_of_work;
            $details->others = $request->others;
            $details->client_name = $request->client_name;
            $details->email = $request->email;
            $details->contact_number = $request->contact_number;
            $details->institute = $request->institute;
            $details->department = $request->department;
            $details->profession = $request->profession;
            $details->budget = $request->budget;
            $details->process_status = $request->process_status;
            $details->process_date = $request->process_date;
            $details->hierarchy_level = $request->hierarchy_level;
            $details->comment_box = $request->comment_box;
            $details->else_project_manager = $request->else_project_manager;

            //writer
            $details->writer = $request->writer;
            $details->writer_assigned_date = $request->writer_assigned_date;
            $details->writer_status = $request->writer_status;
            $details->writer_status_date = $request->writer_status_date;
            $details->writer_project_duration = $request->writer_project_duration;
            $details->writer_duration_unit = $request->writer_duration_unit;

            //reviewer
            $details->reviewer = $request->reviewer;
            $details->reviewer_assigned_date = $request->reviewer_assigned_date;
            $details->reviewer_status = $request->reviewer_status;
            $details->reviewer_status_date = $request->reviewer_status_date;
            $details->reviewer_project_duration = $request->reviewer_project_duration;
            $details->reviewer_duration_unit = $request->reviewer_duration_unit;

            //statistican
            $details->statistican = $request->statistican;
            $details->statistican_assigned_date = $request->statistican_assigned_date;
            $details->statistican_status = $request->statistican_status;
            $details->statistican_status_date = $request->statistican_status_date;
            $details->statistican_project_duration = $request->statistican_project_duration;
            $details->statistican_duration_unit = $request->statistican_duration_unit;

            //jornal
            $details->journal = $request->journal;
            $details->journal_assigned_date = $request->journal_assigned_date;
            $details->journal_status = $request->journal_status;
            $details->journal_status_date = $request->journal_status_date;
            $details->journal_duration_unit = $request->journal_duration_unit;
            $details->journal_project_duration = $request->journal_project_duration;
            $details->status = $request->status ?? $details->status;
            $details->is_deleted = $request->is_deleted ?? $details->is_deleted;
            $details->created_by = $request->created_by ?? $details->created_by;
            $details->save();


            //project status
            // Fetch the project status record using the project ID
            $projectstatus = ProjectStatus::where('project_id', $id)->first();

            if (!$projectstatus) {
                return response()->json(['error' => 'Project status not found'], 404);
            }

            // Check each field individually and update projectstatus
            if (empty($details->journal)) {
                $projectstatus->journal_id = null;
                $projectstatus->journal_status = 'pending';
            } else {
                $projectstatus->journal_id = $details->journal;
            }

            if (empty($details->writer)) {
                $projectstatus->writer_id = null;
                $projectstatus->writer_status = 'pending';
            } else {
                $projectstatus->writer_id = $request->writer ?? $projectstatus->writer_id;
            }

            if (empty($details->reviewer)) {
                $projectstatus->reviewer_id = null;
                $projectstatus->reviewer_status = 'pending';
            } else {
                $projectstatus->reviewer_id = $request->reviewer ?? $projectstatus->reviewer_id;
            }

            if (empty($details->statistican)) {
                $projectstatus->statistican_id = null;
                $projectstatus->statistican_status = 'pending';
            } else {
                $projectstatus->statistican_id = $request->statistican ?? $projectstatus->statistican_id;
            }

            // Save the updated project status
            $projectstatus->save();

            // Project Logs
            if ($request->writer) {
                ProjectLogs::create([
                    'project_id' => $details->id,
                    'employee_id' => $request->writer,
                    'assigned_date' => $request->writer_assigned_date,
                    'status' => $request->writer_status,
                    'status_date' => $request->writer_status_date,
                    'status_type' => 'writer',
                    'created_by' => $request->created_by,
                    'created_date' => date('Y-m-d H:i:s')
                ]);
            }

            if ($request->reviewer) {
                ProjectLogs::create([
                    'project_id' => $details->id,
                    'employee_id' => $request->reviewer,
                    'assigned_date' => $request->reviewer_assigned_date,
                    'status' => $request->reviewer_status,
                    'status_date' => $request->reviewer_status_date,
                    'status_type' => 'reviewer',
                    'created_by' => $request->created_by,
                    'created_date' => date('Y-m-d H:i:s')
                ]);
            }

            if ($request->statistican) {
                ProjectLogs::create([
                    'project_id' => $details->id,
                    'employee_id' => $request->statistican,
                    'assigned_date' => $request->statistican_assigned_date,
                    'status' => $request->statistican_status,
                    'status_date' => $request->statistican_status_date,
                    'status_type' => 'statistican',
                    'created_by' => $request->created_by,
                    'created_date' => date('Y-m-d H:i:s')
                ]);
            }

            if ($request->journal) {
                ProjectLogs::create([
                    'project_id' => $details->id,
                    'employee_id' => $request->journal,
                    'assigned_date' => $request->journal_assigned_date,
                    'status' => $request->journal_status,
                    'status_date' => $request->journal_status_date,
                    'status_type' => 'journal',
                    'created_by' => $request->created_by,
                    'created_date' => date('Y-m-d H:i:s')
                ]);
            }

            //latest update
            if ($request->has('entryprocess_documents') && is_array($request->entryprocess_documents)) {
                $entryprocessDocuments = []; // To store formatted document data for response
                $defaultSpecificOption = null; // Store the first valid specificOption

                foreach ($request->entryprocess_documents as $document) {
                    // Validate that specificOption and file keys exist and are arrays
                    if (isset($document['file']) && is_array($document['file'])) {
                        // Use specificOption from the first valid entry if current specificOption is empty
                        if (isset($document['specificOption']) && is_array($document['specificOption']) && !empty($document['specificOption'])) {
                            $defaultSpecificOption = $document['specificOption']; // Set as default for subsequent entries
                        } elseif ($defaultSpecificOption !== null) {
                            $document['specificOption'] = $defaultSpecificOption; // Use default specificOption if it's available
                        }

                        $fileNames = [];

                        $entryDocument = new EntryDocument();
                        $entryDocument->entry_process_model_id = $details->id;
                        $entryDocument->select_document = json_encode($document['specificOption'], JSON_UNESCAPED_UNICODE);
                        $entryDocument->created_by = $request->created_by ?? '-';
                        $entryDocument->save();

                        // Process each file in the `file` array
                        foreach ($document['file'] as $file) {
                            if (!empty($file)) {
                                // Get original file name and extension
                                $originalName = $file->getClientOriginalName();
                                $originalExtension = $file->getClientOriginalExtension();

                                // Remove spaces, special characters, and underscores, and convert to lowercase
                                $cleanedName = strtolower(preg_replace('/[^a-z0-9]+/i', '-', pathinfo($originalName, PATHINFO_FILENAME)));
                                $cleanedName = str_replace('_', '', $cleanedName); // Remove underscores
                                // Generate unique file name while retaining the original extension
                                $uniqueName = $customId . '-' . time() . '-' . uniqid() . '.' . $originalExtension;

                                // Define the storage path
                                $path = public_path('uploads');

                                // Ensure the directory exists
                                if (!is_dir($path)) {
                                    mkdir($path, 0775, true);
                                }

                                // Move the file to the upload directory
                                $file->move($path, $uniqueName);

                                // Save file information in the `entry_documents_list` table
                                $documentList = new EntryDocumentsList();
                                $documentList->document_id = $entryDocument->id;
                                $documentList->file = $uniqueName; // Save the unique file name
                                $documentList->original_name = $cleanedName . '.' . $originalExtension; // Save the cleaned original name with the extension
                                $documentList->save();

                                // Add file name to the response array
                                $fileNames[] = $uniqueName;
                            }
                        }

                        // Format data for the response
                        $entryprocessDocuments[] = [
                            'specificOption' => $document['specificOption'],
                            'file' => $fileNames
                        ];
                    }
                }

                // Return a successful response
                return response()->json([
                    'entryprocess_documents' => $entryprocessDocuments
                ], 200);
            } else {
                return response()->json(['error' => 'Invalid input data'], 400);
            }

            $roles = [
                'writer' => 'Writer',
                'reviewer' => 'Reviewer',
                'statistican' => 'Statistician',
                'journal' => 'Journal'
            ];

            foreach ($roles as $key => $role) {
                if (!empty($request->$key)) {
                    $userDetails = User::where('id', $request->$key)->first();

                    if ($userDetails) {
                        if (!empty($userDetails->email_address)) {
                            $durationKey = $key . '_project_duration'; // Dynamically form the duration key
                            $durationUnit = $key . '_duration_unit'; // Dynamically form the duration unit key
                            try {
                                Mail::to($userDetails->email_address)->send(new AssignmentNotificationMail([
                                    'name' => $userDetails->employee_name,
                                    'role' => $role,
                                    'project_id' => $customId,
                                    'title' => $details->title,
                                    'duration' => $details->$durationKey, // Dynamically fetch duration
                                    'unit' => $details->$durationUnit // Dynamically fetch duration unit
                                ]));
                                Log::info("Email sent to {$role} ({$userDetails->email_address}).");
                            } catch (\Exception $e) {
                                Log::error("Failed to send email to {$role}: " . $e->getMessage());
                            }
                        } else {
                            Log::error("{$role} email is empty or invalid.");
                        }
                    } else {
                        Log::error("{$role} not found.");
                    }
                } else {
                    Log::error("No {$role} ID provided.");
                }
            }

            // Fetch employee emails and send notifications
            $employeeEmails = [];


            // if (!empty($request->writer)) {
            //     $writerDetails = User::where('id', $request->writer)->first();
            //     if ($writerDetails) {
            //         if (!empty($writerDetails->email_address)) {
            //             Mail::to($writerDetails->email_address)->send(new AssignmentNotificationMail([
            //                 'name' => $writerDetails->employee_name,       // Writer's name
            //                 'role' => 'Writer',                  // Role
            //                 'project_id' => $customId,           // Project ID
            //                 'project_title' => $request->title,  // Project title
            //             ]));
            //         } else {
            //             Log::error('Writer email is empty or invalid');
            //         }
            //     } else {
            //         Log::error('Writer not found');
            //     }
            // } else {
            //     Log::error('No writer ID provided');
            // }

            // // Reviewer
            // if (!empty($request->reviewer)) {
            //     $writerDetails = User::where('id', $request->reviewer)->first();
            //     if ($writerDetails) {
            //         if (!empty($writerDetails->email_address)) {
            //             Mail::to($writerDetails->email_address)->send(new AssignmentNotificationMail([
            //                 'name' => $writerDetails->employee_name,       // Writer's name
            //                 'role' => 'Reviewer',                  // Role
            //                 'project_id' => $customId,           // Project ID
            //                 'project_title' => $request->title,  // Project title
            //             ]));
            //         } else {
            //             Log::error('Reviewer email is empty or invalid');
            //         }
            //     } else {
            //         Log::error('reviewer not found');
            //     }
            // } else {
            //     Log::error('No reviewer ID provided');
            // }

            // // Statistician
            // if (!empty($request->statistican)) {
            //     $writerDetails = User::where('id', $request->statistican)->first();
            //     if ($writerDetails) {
            //         if (!empty($writerDetails->email_address)) {
            //             Mail::to($writerDetails->email_address)->send(new AssignmentNotificationMail([
            //                 'name' => $writerDetails->employee_name,       // Writer's name
            //                 'role' => 'Statistican',                  // Role
            //                 'project_id' => $customId,           // Project ID
            //                 'project_title' => $request->title,  // Project title
            //             ]));
            //         } else {
            //             Log::error('statistican email is empty or invalid');
            //         }
            //     } else {
            //         Log::error('statistican not found');
            //     }
            // } else {
            //     Log::error('No statistican ID provided');
            // }

            // // Journal
            // if (!empty($request->journal)) {
            //     $writerDetails = User::where('id', $request->journal)->first();
            //     if ($writerDetails) {
            //         if (!empty($writerDetails->email_address)) {
            //             Mail::to($writerDetails->email_address)->send(new AssignmentNotificationMail([
            //                 'name' => $writerDetails->employee_name,       // Writer's name
            //                 'role' => 'Journal',                  // Role
            //                 'project_id' => $customId,           // Project ID
            //                 'project_title' => $request->title,  // Project title
            //             ]));
            //         } else {
            //             Log::error('email is empty or invalid');
            //         }
            //     } else {
            //         Log::error('Journal not found');
            //     }
            // } else {
            //     Log::error('No journal ID provided');
            // }
        });
        // function trimWithEllipsis($text, $maxLength = 50) {
        //     if (strlen($text) > $maxLength) {
        //         $trimmed = substr($text, 0, $maxLength);
        //         $trimmed = rtrim($trimmed); // Remove trailing whitespace
        //         return $trimmed . '...';
        //     }
        //     return $text;
        // }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $details = EntryProcessModel::where('is_deleted', 0)->find($id);
        $details->is_deleted = 1;
        $details->status = 0;
        $details->save();
        return response()->json($details);
    }

    public function documentDelete(string $id)
    {
        // Find the payment detail by ID
        $paymentDetail = EntryDocumentsList::find($id);

        if (!$paymentDetail) {
            return response()->json(['message' => 'Entry process details not found'], 404);
        }
        // Update the is_deleted field to 1
        $paymentDetail->is_deleted = 1;
        $paymentDetail->save();

        return response()->json(['message' => 'Entry process document deleted successfully']);
    }


    public function documentRenameDoc(Request $request, string $id)
    {
        $document = EntryDocumentsList::find($id);

        if (!$document) {
            return response()->json(['message' => 'Entry process document not found'], 404);
        }

        $newName = $request->name . '.' . $request->extension;
        $document->original_name = $newName;
        $document->save();

        return response()->json(['message' => 'Document renamed successfully']);
    }

    public function filterID(string $id)
    {
        $details = EntryProcessModel::where('is_deleted', 0)->find($id);
        return response()->json($details);
    }

    public function fetchInstitutions()
    {

        $institutions = InstitutionModel::where('is_deleted', 0)
            ->where('status', 'active')
            ->get(['name', 'id']);

        return response()->json($institutions);
    }

    public function fetchProjectTitle()
    {
        $projectTitle = EntryProcessModel::where('is_deleted', 0)
            ->groupBy('id')
            ->selectRaw('id, MAX(title) as title, MAX(project_id) as project_id')
            ->get();

        return response()->json($projectTitle);
    }

    public function fetchDepartments()
    {
        $departments = DepartmentModel::where('is_deleted', 0)
            ->where('status', 'active')
            ->get(['name', 'id']);
        return response()->json($departments);
    }

    public function fetchProfessions()
    {
        $professions = ProfessionModel::where('is_deleted', 0)
            ->where('status', 'active')
            ->get(['name', 'id']);
        return response()->json($professions);
    }

    public function indexProjectId()
    {
        $entries = EntryProcessModel::with(['paymentStatusModel', 'pendingStatusModel'])->where('is_deleted', 0)->get();
        return response()->json($entries);
    }


    public function showProjectId($project_id)
    {
        $entry = EntryProcessModel::with(['paymentStatusModel', 'pendingStatusModel'])->where('is_deleted', 0)->where('project_id', $project_id)->first();

        if (!$entry) {
            return response()->json(['message' => 'Entry process not found'], 404);
        }

        return response()->json($entry);
    }


    public function updateProjectId(Request $request, $project_id)
    {
        // Find the entry process by project_id
        $entry = EntryProcessModel::where('project_id', $project_id)->where('is_deleted', 0)->first();

        // Check if the entry process exists
        if (!$entry) {
            return response()->json(['message' => 'Entry process not found'], 404);
        }
        // Helper to clean date fields
        $nullifyIfEmpty = fn($value) => empty($value) ? null : $value;
        // Update entry process fields
        $entry->entry_date = $request->entry_date ?? null;
        $entry->title = $request->title ?? null;
        $entry->type_of_work = $request->type_of_work ?? null;
        $entry->others = $request->others ?? null;
        $entry->select_document = $request->select_document ?? null;
        $entry->file = $request->file ?? null;
        $entry->client_name = $request->client_name ?? null;
        $entry->email = $request->email ?? null;
        $entry->contact_number = $request->contact_number ?? null;
        $entry->institute = $request->institute ?? null;
        $entry->department = $request->department ?? null;
        $entry->profession = $request->profession ?? null;
        $entry->budget = $request->budget ?? null;
        $entry->hierarchy_level = $request->hierarchy_level ?? null;
        $entry->comment_box = $request->comment_box ?? null;
        $entry->writer = $request->writer ?? null;
        $entry->writer_assigned_date = $request->writer_assigned_date ?? null;
        $entry->writer_status = $request->writer_status ?? null;
        $entry->writer_status_date = $request->writer_status_date ?? null;
        $entry->reviewer = $request->reviewer ?? null;
        $entry->reviewer_assigned_date = $request->reviewer_assigned_date ?? null;
        $entry->reviewer_status = $request->reviewer_status ?? null;
        $entry->reviewer_status_date = $request->reviewer_status_date ?? null;
        $entry->statistican = $request->statistican ?? null;
        $entry->statistican_assigned_date = $request->statistican_assigned_date ?? null;
        $entry->statistican_status = $request->statistican_status ?? null;
        $entry->statistican_status_date = $request->statistican_status_date ?? null;
        $entry->status = $request->status ?? 1;
        $entry->is_deleted = $request->is_deleted ?? 0;
        $entry->created_by = $request->created_by ?? 'Aryu';
        $entry->save();

        // Update payment process
        $paymentProcess = PaymentStatusModel::where('project_id', $project_id)->first();

        if ($paymentProcess) {
            // Ensure process_title is not null
            $paymentProcess->process_title = $request->process_title ?? 'Default Process Title';
            $paymentProcess->budget = $request->budget ?? ' ';
            $paymentProcess->payment_one = $request->payment_one ?? ' ';
            $paymentProcess->payment_one_date = $nullifyIfEmpty($request->payment_one_date);
            $paymentProcess->payment_two = $request->payment_two ?? '';
            $paymentProcess->payment_two_date = $nullifyIfEmpty($request->payment_two_date);
            $paymentProcess->payment_three = $request->payment_three ?? '';
            $paymentProcess->payment_three_date = $nullifyIfEmpty($request->payment_three_date);
            $paymentProcess->writer_payment = $request->writer_payment ?? '';
            $paymentProcess->writer_payment_date = $nullifyIfEmpty($request->writer_payment_date);
            $paymentProcess->reviewer_payment = $request->reviewer_payment ?? '';
            $paymentProcess->reviewer_payment_date = $nullifyIfEmpty($request->reviewer_payment_date);
            $paymentProcess->statistican_payment = $request->statistican_payment ?? '';
            $paymentProcess->statistican_payment_date = $nullifyIfEmpty($request->statistican_payment_date);
            $paymentProcess->journal_payment = $request->journal_payment ?? '';
            $paymentProcess->journal_payment_date = $nullifyIfEmpty($request->journal_payment_date);
            $paymentProcess->payment_status = $request->payment_status ?? '';
            $paymentProcess->status = $request->status ?? 1;
            $paymentProcess->is_deleted = $request->is_deleted ?? 0;
            $paymentProcess->save();
        }

        // Update pending process
        $pendingProcess = PendingStatusModel::where('project_id', $project_id)->first();

        if ($pendingProcess) {
            $pendingProcess->writer_pending_days = $request->writer_pending_days ?? null;
            $pendingProcess->reviewer_pending_days = $request->reviewer_pending_days ?? null;
            $pendingProcess->project_pending_days = $request->project_pending_days ?? null;
            $pendingProcess->writer_payment_due_date = $request->writer_payment_due_date ?? null;
            $pendingProcess->reviewer_payment_due_date = $request->reviewer_payment_due_date ?? null;
            $pendingProcess->status = $request->status ?? 1;
            $pendingProcess->save();
        }

        return response()->json([
            'entry_process' => $entry,
            'payment_process' => $paymentProcess ?? 'No payment process found',
            'pending_process' => $pendingProcess ?? 'No pending process found'
        ]);
    }



    //getting the value of type_of_work and fetch the value from project_id for each type_of_work
    public function fetchProjectId(Request $request)
    {
        $query = EntryProcessModel::where('is_deleted', 0)
            ->with(['paymentStatusModel', 'pendingStatusModel']);

        $totalCount = EntryProcessModel::where('is_deleted', 0)->count();

        $validColumns = [
            'entry_date',
            'title',
            'project_id',
            'type_of_work',
            'others',
            'select_document',
            'file',
            'client_name',
            'email',
            'contact_number',
            'institute',
            'department',
            'profession',
            'budget',
            'hierarchy_level',
            'comment_box',
            'writer',
            'writer_assigned_date',
            'writer_status',
            'writer_status_date',
            'writer_project_duration',
            'reviewer',
            'reviewer_assigned_date',
            'reviewer_status',
            'reviewer_status_date',
            'reviewer_project_duration',
            'statistican',
            'statistican_assigned_date',
            'statistican_status',
            'statistican_status_date',
            'statistican_project_duration',
            'created_by'
        ];

        $position = $request->get('position');
        $typeOfWork = $request->get('type_of_work');
        $createdBy = $request->get('created_by');

        $countQuery = clone $query;

        if ($position && in_array($position, $validColumns)) {
            $query->whereNotNull($position);
            $countQuery->whereNotNull($position);
        }

        // Dynamically filter by 'type_of_work' if provided
        if ($typeOfWork) {
            $query->where('type_of_work', $typeOfWork);
            $countQuery->where('type_of_work', $typeOfWork);
        }

        // Dynamically filter by 'created_by' if provided
        if ($createdBy) {
            $query->where('created_by', $createdBy);
            $countQuery->where('created_by', $createdBy);
        }

        // Retrieve the filtered data
        $data = $query->get($validColumns);

        // Count the filtered results using the cloned query
        $filteredCount = $countQuery->count();

        // Prepare and return the response
        return response()->json([
            // 'data' => $data,
            'filtered_count' => $filteredCount,
            'total_count' => $totalCount,
            'position' => $position,
            'type_of_work' => $typeOfWork
        ]);
    }

    public function dashboardProjectList(Request $request)
    {
        $position = $request->get('position');
        if ($position === 'project_manager' || $position === 'team_coordinator' || $position === 'admin') {
            $entries = EntryProcessModel::where('is_deleted', 0)->get();

            $projectStatusList = EntryProcessModel::with(['projectStatus' => function ($query) {
                $query->where(function ($q) {
                    $q->where('writer_status', 'rejected')
                        ->orWhere('reviewer_status', 'rejected')
                        ->orWhere('journal_status', 'rejected')
                        ->orWhere('statistican_status', 'rejected');
                });
            }])
                ->where('is_deleted', 0)
                ->get();


            $projectStatusCount = EntryProcessModel::with(['projectStatus' => function ($query) {
                $query->where(function ($q) {
                    $q->where('writer_status', '!=', 'pending')
                        ->orWhere('reviewer_status', '!=', 'pending')
                        ->orWhere('journal_status', '!=', 'pending')
                        ->orWhere('statistican_status', '!=', 'pending');
                });
            }])->where('is_deleted', 0)->count();


            $paymentEntries = PaymentStatusModel::get();

            // Initialize counters
            $typeOfWorkCounts = [
                'manuscript' => 0,
                'thesis' => 0,
                'statistics' => 0,
                'presentation' => 0,
                'others' => 0,
            ];
            $processStatusCounts = [
                'not_assigned' => 0,
                'pending_author' => 0,
                'withdrawal' => 0,
                'in_progress' => 0,
                'completed' => 0,
            ];
            $journalStatusCounts = [
                'pending_author' => 0,
                'waiting_for_submission' => 0,
                'peer_review' => 0,
                'review_client' => 0,
                'rejected' => 0,
            ];
            $completedCounts = $typeOfWorkCounts;

            $urgentImportantCount = 0;
            $notAssignedCount = 0;
            $projectDelayCount = 0;
            $freelancerPaymentCount = 0;
            $writerCount = 0;
            $reviewerCount = 0;

            $writerStatusCounts = [
                'completed' => 0,
                'on_going' => 0,
                'correction_1' => 0,
                'correction_2' => 0,
                'plag_correction' => 0,
            ];

            $reviewerStatusCounts = [
                'completed' => 0,
                'on_going' => 0,
                'correction_1' => 0,
                'correction_2' => 0,
                'plag_correction' => 0,
            ];


            $paymentStatusCounts = [
                'advance_pending' => 0,
                'partial_payment_pending' => 0,
                'payment_pending' => 0,
                'presentation' => 0,
                'balance_to_pay' => 0,
            ];

            $freelancers = []; // Array to store freelancer details

            // Process each entry
            foreach ($entries as $entry) {
                // Count type_of_work
                if (isset($typeOfWorkCounts[$entry->type_of_work])) {
                    $typeOfWorkCounts[$entry->type_of_work]++;
                }

                //Count process_status
                if (isset($processStatusCounts[$entry->process_status])) {
                    $processStatusCounts[$entry->process_status]++;
                }

                // Count journal_status
                if (isset($entry->journal_status) && isset($journalStatusCounts[$entry->journal_status])) {
                    $journalStatusCounts[$entry->journal_status]++;
                }

                // Count completed
                if ($entry->process_status === 'completed' && isset($completedCounts[$entry->type_of_work])) {
                    $completedCounts[$entry->type_of_work]++;
                }

                // Count other metrics
                if ($entry->hierarchy_level === 'urgent_important') {
                    $urgentImportantCount++;
                }
                if ($entry->process_status === 'not_assigned') {
                    $notAssignedCount++;
                }
                // if ($entry->project_delayed) {   //doute
                //     $projectDelayCount++;
                // }

                if ($entry->process_status !== 'completed') {
                    $projectDelayCount++;
                }

                //writer count

                // Count writer and reviewer
                if (!empty($entry->writer)) { // Check if writer exists
                    $writerCount++;
                }
                if (!empty($entry->reviewer)) { // Check if reviewer exists
                    $reviewerCount++;
                }

                // Initialize counts if not already initialized
                $writerStatusCounts = $writerStatusCounts ?? [];
                $reviewerStatusCounts = $reviewerStatusCounts ?? [];

                if ($entry->type_of_work === 'manuscript') {
                    // Increment writer status count
                    if (isset($writerStatusCounts[$entry->writer_status])) {
                        $writerStatusCounts[$entry->writer_status]++;
                    } else {
                        $writerStatusCounts[$entry->writer_status] = 1;
                    }

                    // Increment reviewer status count
                    if (isset($reviewerStatusCounts[$entry->reviewer_status])) {
                        $reviewerStatusCounts[$entry->reviewer_status]++;
                    } else {
                        $reviewerStatusCounts[$entry->reviewer_status] = 1;
                    }
                }

                if ($entry->writer || $entry->reviewer || $entry->statistican || $entry->journal) {
                    // Prepare a list of IDs to check
                    $idsToCheck = [];

                    if ($entry->writer) {
                        $idsToCheck[] = $entry->writer;
                    }
                    if ($entry->reviewer) {
                        $idsToCheck[] = $entry->reviewer;
                    }
                    if ($entry->statistican) {
                        $idsToCheck[] = $entry->statistican;
                    }
                    if ($entry->journal) {
                        $idsToCheck[] = $entry->journal;
                    }

                    // Fetch users from HRMS where the ID matches any of the ones we need
                    $userhrms = DB::connection('mysql_medics_hrms')
                        ->table('employee_details')
                        ->whereIn('id', $idsToCheck) // Use whereIn to check multiple IDs
                        ->get();

                    // Iterate over the fetched users and check if they are freelancers
                    foreach ($userhrms as $user) {
                        if ($user->employee_type === 'freelancers') {
                            $freelancerPaymentCount++;


                            // Add freelancer details to the list
                            $freelancers[] = [
                                'id' => $user->id,
                                'name' => $user->employee_name,
                                'employee_type' => $user->employee_type,
                                'email' => $user->email_address,
                                'project_id' => $entry->project_id,
                                'entry_date' => $entry->entry_date,
                                'hierarchy_level' => $entry->hierarchy_level,
                                'type_of_work' => $entry->type_of_work,
                            ];
                        }
                    }
                }
            }

            foreach ($paymentEntries as $pentry) {

                //Count payment status
                if (isset($paye[$pentry->process_status])) {
                    $paymentStatusCounts[$entry->payment_status]++;
                }
            }
            // Prepare the output
            $filteredCount = $entries->count(); // Example of filtering
            $totalCount = EntryProcessModel::where('is_deleted', 0)->count();

            // Get monthwise data
            $monthwiseData = $this->monthWiseTable($position);


            //in progress

            $statuses = ['in_progress', 'not_assigned'];


            $inProgress = EntryProcessModel::with(['userData'])->select('id', 'title', 'type_of_work', 'process_status', 'project_id', 'entry_date', 'writer', 'writer_assigned_date', 'reviewer', 'reviewer_assigned_date', 'statistican', 'statistican_assigned_date', 'journal', 'journal_assigned_date', 'hierarchy_level')->whereIn('process_status', $statuses)->where('is_deleted', 0)->orderBy('id','desc')->get();
            $inProgressCount = EntryProcessModel::with(['userData'])->select('id', 'title', 'type_of_work', 'process_status', 'project_id', 'entry_date', 'writer', 'writer_assigned_date', 'reviewer', 'reviewer_assigned_date', 'statistican', 'statistican_assigned_date', 'journal', 'journal_assigned_date', 'hierarchy_level')->whereIn('process_status', $statuses)->where('is_deleted', 0)->count();


            //To-do list
            $tasks = EntryProcessModel::with(['userData'])->select('id', 'title', 'type_of_work', 'process_status', 'project_id', 'entry_date', 'writer', 'writer_assigned_date', 'reviewer', 'reviewer_assigned_date', 'statistican', 'statistican_assigned_date', 'journal', 'journal_assigned_date', 'assign_by', 'hierarchy_level')->whereIn('process_status', ['to_do', 'not_assigned'])->where('is_deleted', 0)->orderBy('id','desc')
                ->get();
            $to_docount = EntryProcessModel::with(['userData'])->select('id', 'title', 'type_of_work', 'process_status', 'project_id', 'entry_date', 'writer', 'writer_assigned_date', 'reviewer', 'reviewer_assigned_date', 'statistican', 'statistican_assigned_date', 'journal', 'journal_assigned_date', 'assign_by', 'hierarchy_level')->whereIn('process_status', ['to_do', 'not_assigned'])->where('is_deleted', 0)
                ->count();

            //in work list
            $inWorks = EntryProcessModel::with(['userData'])->select('id', 'title', 'type_of_work', 'process_status', 'project_id', 'entry_date', 'writer', 'writer_assigned_date', 'reviewer', 'reviewer_assigned_date', 'statistican', 'statistican_assigned_date', 'journal', 'journal_assigned_date', 'hierarchy_level')->where('process_status', 'pending_author')->where('is_deleted', 0)->orderBy('id','desc')->get();
            $inWorksCount = EntryProcessModel::with(['userData'])->select('id', 'title', 'type_of_work', 'process_status', 'project_id', 'entry_date', 'writer', 'writer_assigned_date', 'reviewer', 'reviewer_assigned_date', 'statistican', 'statistican_assigned_date', 'journal', 'journal_assigned_date', 'hierarchy_level')->where('process_status', 'pending_author')->where('is_deleted', 0)->count();

            //review list

            $reviews = EntryProcessModel::with(['userData'])->select('id', 'title', 'type_of_work', 'process_status', 'project_id', 'entry_date', 'writer', 'writer_assigned_date', 'reviewer', 'reviewer_assigned_date', 'statistican', 'statistican_assigned_date', 'journal', 'journal_assigned_date', 'hierarchy_level')->where('process_status', 'withdrawal')->where('is_deleted', 0)->orderBy('id','desc')->get();

            $reviewsCount = EntryProcessModel::with(['userData'])->select('id', 'title', 'type_of_work', 'process_status', 'project_id', 'entry_date', 'writer', 'writer_assigned_date', 'reviewer', 'reviewer_assigned_date', 'statistican', 'statistican_assigned_date', 'journal', 'journal_assigned_date', 'hierarchy_level')->where('process_status', 'withdrawal')->where('is_deleted', 0)->count();

            //completed list
            $completed = EntryProcessModel::with(['userData'])->select('id', 'title', 'type_of_work', 'process_status', 'project_id', 'entry_date', 'writer', 'writer_assigned_date', 'reviewer', 'reviewer_assigned_date', 'statistican', 'statistican_assigned_date', 'journal', 'journal_assigned_date', 'hierarchy_level')->where('process_status', 'completed')->where('is_deleted', 0)->orderBy('id','desc')->get();
            $completedCount = EntryProcessModel::with(['userData'])->select('id', 'title', 'type_of_work', 'process_status', 'project_id', 'entry_date', 'writer', 'writer_assigned_date', 'reviewer', 'reviewer_assigned_date', 'statistican', 'statistican_assigned_date', 'journal', 'journal_assigned_date', 'hierarchy_level')->where('process_status', 'completed')->where('is_deleted', 0)->count();

            //Correction list

            $corrections = EntryProcessModel::with(['userData'])->select('id', 'title', 'type_of_work', 'process_status', 'project_id', 'entry_date', 'writer', 'writer_assigned_date', 'reviewer', 'reviewer_assigned_date', 'statistican', 'statistican_assigned_date', 'journal', 'journal_assigned_date', 'hierarchy_level')->where('process_status', 'in_progress')->where('is_deleted', 0)->orderBy('id','desc')->get();
            $correctionsCount = EntryProcessModel::with(['userData'])->select('id', 'title', 'type_of_work', 'process_status', 'project_id', 'entry_date', 'writer', 'writer_assigned_date', 'reviewer', 'reviewer_assigned_date', 'statistican', 'statistican_assigned_date', 'journal', 'journal_assigned_date', 'hierarchy_level')->where('process_status', 'in_progress')->where('is_deleted', 0)->count();

            //people wise response data
            // Fetch all records from the People model
            $totalProjects = People::with(['createdByUser'])
                ->where('employee_name', '!=', 'Admin')
                ->whereIn('position', [7, 8, 10, 11])
                ->get();

            // Loop through each person and count based on their position
            foreach ($totalProjects as $entry) {
                // Get the employee's position
                $emp_pos = $entry->position;
                $emp_id = $entry->id;

                // Initialize count variables for each role
                $writerCount = 0;
                $reviewerCount = 0;
                $journalCount = 0;
                $statisticanCount = 0;
                $writerPendingCount = 0;
                $reviewerPendingCount = 0;
                $statisticanPendingCount = 0;
                $journalPendingCount = 0;


                // Initialize count variables for each role
                $completedProjects = 0;
                $completedIn4Days = 0;
                $completedIn5To8Days = 0;
                $completedInMoreThan8Days = 0;
                $projectlist = [];

                // Check based on the position and get the count for related entries in EntryProcessModel
                if ($emp_pos == 7) {
                    // If the position is 7, check the writer column
                    $writerCount = EntryProcessModel::where('writer', $emp_id)->where('is_deleted', 0)->count();
                    $writerDAtaId = EntryProcessModel::where('writer', $emp_id)->where('is_deleted', 0)->get()->pluck('id');
                    $writerPendingCount = EntryProcessModel::where('writer', $emp_id)->where('is_deleted', 0)->where('process_status', '!=', 'completed')->count();
                    // $projectlist = ProjectLogs::where('employee_id', $emp_id)->count();
                    $projectlist = ProjectLogs::where('employee_id', $emp_id)->whereIn('project_id', $writerDAtaId)->get();
                } elseif ($emp_pos == 8) {
                    // If the position is 8, check the reviewer column
                    $reviewerCount = EntryProcessModel::where('reviewer', $emp_id)->where('is_deleted', 0)->count();
                    $writerDAtaId = EntryProcessModel::where('reviewer', $emp_id)->where('is_deleted', 0)->get()->pluck('id');
                    $reviewerPendingCount = EntryProcessModel::where('reviewer', $emp_id)->where('is_deleted', 0)->where('process_status', '!=', 'completed')->count();
                    $projectlist = ProjectLogs::where('employee_id', $emp_id)->whereIn('project_id', $writerDAtaId)->get();
                } elseif ($emp_pos == 10) {
                    // If the position is 10, check the journal column
                    $journalCount = EntryProcessModel::where('journal', $emp_id)->where('is_deleted', 0)->count();
                    $writerDAtaId = EntryProcessModel::where('journal', $emp_id)->where('is_deleted', 0)->get()->pluck('id');
                    $journalPendingCount = EntryProcessModel::where('journal', $emp_id)->where('is_deleted', 0)->where('process_status', '!=', 'completed')->count();
                    $projectlist = ProjectLogs::where('employee_id', $emp_id)->whereIn('project_id', $writerDAtaId)->get();
                } elseif ($emp_pos == 11) {
                    // If the position is 11, check the statistican column
                    $statisticanCount = EntryProcessModel::where('statistican', $emp_id)->where('is_deleted', 0)->count();
                    $writerDAtaId = EntryProcessModel::where('statistican', $emp_id)->where('is_deleted', 0)->get()->pluck('id');
                    $statisticanPendingCount = EntryProcessModel::where('statistican', $emp_id)->where('is_deleted', 0)->where('process_status', '!=', 'completed')->count();
                    $projectlist = ProjectLogs::where('employee_id', $emp_id)->whereIn('project_id', $writerDAtaId)->get();
                }

                // Loop through the projects to calculate the date differences
                foreach ($projectlist as $project) {
                    // Check if the project status is 'completed'
                    if ($project->status == 'completed') {
                        // Get the status_date of the project
                        $statusDate = Carbon::parse($project->status_date);

                        // Use status_date as the reference (no need for current date comparison)
                        $completedDate = Carbon::parse($project->status_date);

                        // Get the difference in days from the 'completed' status date
                        $daysDifference = $completedDate->diffInDays($statusDate); // This will compare status_date to itself (adjust if another date is needed)

                        // Increment the corresponding counter based on the number of days since completion
                        if ($daysDifference < 4) {
                            $completedIn4Days++;
                        } elseif ($daysDifference >= 5 && $daysDifference <= 8) {
                            $completedIn5To8Days++;
                        } elseif ($daysDifference > 8) {
                            $completedInMoreThan8Days++;
                        }
                    }
                }


                // Add the counts to the person's data for response
                $entry->writer_count = $writerCount;
                $entry->reviewer_count = $reviewerCount;
                $entry->journal_count = $journalCount;
                $entry->statistican_count = $statisticanCount;
                $entry->writerPendingCount = $writerPendingCount;
                $entry->reviewerPendingCount = $reviewerPendingCount;
                $entry->statisticanPendingCount = $statisticanPendingCount;
                $entry->journalPendingCount = $journalPendingCount;

                // Add the calculated counts to the person's data for response
                $entry->completed_in_4_days = $completedIn4Days;
                $entry->completed_in_5_to_8_days = $completedIn5To8Days;
                $entry->completed_in_more_than_8_days = $completedInMoreThan8Days;
            }


            //inhouse projects
            $totalProjectsInhouse = People::with(['createdByUser'])
                ->where('employee_name', '!=', 'Admin')
                ->where('employee_type', '!=', 'freelancers')
                ->whereIn('position', [7, 8, 10, 11])
                ->get();

            // Loop through each person and count based on their position
            foreach ($totalProjectsInhouse as $entry) {
                // Get the employee's position
                $emp_pos = $entry->position;
                $emp_id = $entry->id;

                // Initialize count variables for each role
                $writerCount = $reviewerCount = $journalCount = $statisticanCount = 0;
                $writerongoingCount = $writerneadCount = $writercorrectionCount = 0;
                $writerPendingCount = $reviewerPendingCount = $statisticanPendingCount = $journalPendingCount = 0;
                $reviewerongoingCount = $reviewerneadCount = $reviewercorrectionCount = 0;



                // Initialize count variables for each role
                $completedProjects = 0;
                $completedIn4Days = 0;
                $completedIn5To8Days = 0;
                $completedInMoreThan8Days = 0;
                $projectlist = [];

                // Check based on the position and get the count for related entries in EntryProcessModel
                if ($emp_pos == 7) {
                    // If the position is 7, check the writer column
                    $writerCount = EntryProcessModel::where('writer', $emp_id)->where('is_deleted', 0)->count();
                    $writerDAtaId = EntryProcessModel::where('writer', $emp_id)->where('is_deleted', 0)->get()->pluck('id');
                    $writerPendingCount = EntryProcessModel::where('writer', $emp_id)->where('is_deleted', 0)->where('process_status', '!=', 'completed')->count();
                    $writerongoingCount = EntryProcessModel::where('writer', $emp_id)->where('is_deleted', 0)->where('writer_status', 'on_going')->count();
                    $writerneadCount = EntryProcessModel::where('writer', $emp_id)->where('is_deleted', 0)->where('writer_status', 'need_support')->count();
                    $writercorrectionCount = EntryProcessModel::where('writer', $emp_id)->where('is_deleted', 0)->whereIn('writer_status', ['correction_1', 'correction_2', 'correction_3', 'plag_correction'])->count();
                    // $projectlist = ProjectLogs::where('employee_id', $emp_id)->count();
                    $projectlist = ProjectLogs::where('employee_id', $emp_id)->whereIn('project_id', $writerDAtaId)->get();
                } elseif ($emp_pos == 8) {
                    // If the position is 8, check the reviewer column
                    $reviewerCount = EntryProcessModel::where('reviewer', $emp_id)->where('is_deleted', 0)->count();
                    $writerDAtaId = EntryProcessModel::where('reviewer', $emp_id)->where('is_deleted', 0)->get()->pluck('id');
                    $reviewerPendingCount = EntryProcessModel::where('reviewer', $emp_id)->where('is_deleted', 0)->where('process_status', '!=', 'completed')->count();
                    $reviewerongoingCount = EntryProcessModel::where('reviewer', $emp_id)->where('is_deleted', 0)->where('reviewer_status', 'on_going')->count();
                    $reviewerneadCount = EntryProcessModel::where('reviewer', $emp_id)->where('is_deleted', 0)->where('reviewer_status', 'need_support')->count();
                    $reviewercorrectionCount = EntryProcessModel::where('reviewer', $emp_id)->where('is_deleted', 0)->whereIn('reviewer_status', ['correction_1', 'correction_2', 'correction_3', 'plag_correction'])->count();
                    $projectlist = ProjectLogs::where('employee_id', $emp_id)->whereIn('project_id', $writerDAtaId)->get();
                } elseif ($emp_pos == 10) {
                    // If the position is 10, check the journal column
                    $journalCount = EntryProcessModel::where('journal', $emp_id)->where('is_deleted', 0)->count();
                    $writerDAtaId = EntryProcessModel::where('journal', $emp_id)->where('is_deleted', 0)->get()->pluck('id');
                    $journalPendingCount = EntryProcessModel::where('journal', $emp_id)->where('is_deleted', 0)->where('process_status', '!=', 'completed')->count();
                    $projectlist = ProjectLogs::where('employee_id', $emp_id)->whereIn('project_id', $writerDAtaId)->get();
                } elseif ($emp_pos == 11) {
                    // If the position is 11, check the statistican column
                    $statisticanCount = EntryProcessModel::where('statistican', $emp_id)->where('is_deleted', 0)->count();
                    $writerDAtaId = EntryProcessModel::where('statistican', $emp_id)->where('is_deleted', 0)->get()->pluck('id');
                    $statisticanPendingCount = EntryProcessModel::where('statistican', $emp_id)->where('is_deleted', 0)->where('process_status', '!=', 'completed')->count();
                    $projectlist = ProjectLogs::where('employee_id', $emp_id)->whereIn('project_id', $writerDAtaId)->get();
                }

                // Loop through the projects to calculate the date differences
                foreach ($projectlist as $project) {
                    // Check if the project status is 'completed'
                    if ($project->status == 'completed') {
                        // Get the status_date of the project
                        $statusDate = Carbon::parse($project->status_date);

                        // Use status_date as the reference (no need for current date comparison)
                        $completedDate = Carbon::parse($project->status_date);

                        // Get the difference in days from the 'completed' status date
                        $daysDifference = $completedDate->diffInDays($statusDate); // This will compare status_date to itself (adjust if another date is needed)

                        // Increment the corresponding counter based on the number of days since completion
                        if ($daysDifference < 4) {
                            $completedIn4Days++;
                        } elseif ($daysDifference >= 5 && $daysDifference <= 8) {
                            $completedIn5To8Days++;
                        } elseif ($daysDifference > 8) {
                            $completedInMoreThan8Days++;
                        }
                    }
                }


                // Add the counts to the person's data for response
                $entry->writer_count = $writerCount;
                $entry->reviewer_count = $reviewerCount;
                $entry->journal_count = $journalCount;
                $entry->statistican_count = $statisticanCount;
                $entry->writerPendingCount = $writerPendingCount;
                $entry->reviewerPendingCount = $reviewerPendingCount;
                $entry->statisticanPendingCount = $statisticanPendingCount;
                $entry->journalPendingCount = $journalPendingCount;

                $entry->writerongoingCount  = $writerongoingCount ;
                $entry->reviewerongoingCount  = $reviewerongoingCount ;

                $entry->writerneadCount  = $writerneadCount ;
                $entry->reviewerneadCount  = $reviewerneadCount ;

                $entry->writercorrectionCount  = $writercorrectionCount ;
                $entry->reviewercorrectionCount  = $reviewercorrectionCount ;

                // Add the calculated counts to the person's data for response
                $entry->completed_in_4_days = $completedIn4Days;
                $entry->completed_in_5_to_8_days = $completedIn5To8Days;
                $entry->completed_in_more_than_8_days = $completedInMoreThan8Days;
            }

            //freelancer
            $totalProjectsFreelancer = People::with(['createdByUser'])
                ->where('employee_name', '!=', 'Admin')
                ->where('employee_type', '=', 'freelancers')
                ->whereIn('position', [7, 8, 10, 11])
                ->get();

            // Loop through each person and count based on their position
            foreach ($totalProjectsFreelancer as $entry) {
                // Get the employee's position
                $emp_pos = $entry->position;
                $emp_id = $entry->id;

                // Initialize count variables for each role
                $writerCount = 0;
                $reviewerCount = 0;
                $journalCount = 0;
                $statisticanCount = 0;
                $writerPendingCount = 0;
                $reviewerPendingCount = 0;
                $statisticanPendingCount = 0;
                $journalPendingCount = 0;

                $writerongoingCount = $writerneadCount = $writercorrectionCount = 0;
                $reviewerongoingCount = $reviewerneadCount = $reviewercorrectionCount = 0;


                // Initialize count variables for each role
                $completedProjects = 0;
                $completedIn4Days = 0;
                $completedIn5To8Days = 0;
                $completedInMoreThan8Days = 0;
                $projectlist = [];

                // Check based on the position and get the count for related entries in EntryProcessModel
                if ($emp_pos == 7) {
                    // If the position is 7, check the writer column
                    $writerCount = EntryProcessModel::where('writer', $emp_id)->where('is_deleted', 0)->count();
                    $writerDAtaId = EntryProcessModel::where('writer', $emp_id)->where('is_deleted', 0)->get()->pluck('id');
                    $writerPendingCount = EntryProcessModel::where('writer', $emp_id)->where('is_deleted', 0)->where('process_status', '!=', 'completed')->count();
                    $writerongoingCount = EntryProcessModel::where('writer', $emp_id)->where('is_deleted', 0)->where('writer_status', 'on_going')->count();
                    $writerneadCount = EntryProcessModel::where('writer', $emp_id)->where('is_deleted', 0)->where('writer_status', 'need_support')->count();
                    $writercorrectionCount = EntryProcessModel::where('writer', $emp_id)->where('is_deleted', 0)->whereIn('writer_status', ['correction_1', 'correction_2', 'correction_3', 'plag_correction'])->count();
                    // $projectlist = ProjectLogs::where('employee_id', $emp_id)->count();
                    $projectlist = ProjectLogs::where('employee_id', $emp_id)->whereIn('project_id', $writerDAtaId)->get();
                } elseif ($emp_pos == 8) {
                    // If the position is 8, check the reviewer column
                    $reviewerCount = EntryProcessModel::where('reviewer', $emp_id)->where('is_deleted', 0)->count();
                    $writerDAtaId = EntryProcessModel::where('reviewer', $emp_id)->where('is_deleted', 0)->get()->pluck('id');
                    $reviewerPendingCount = EntryProcessModel::where('reviewer', $emp_id)->where('is_deleted', 0)->where('process_status', '!=', 'completed')->count();
                    $reviewerongoingCount = EntryProcessModel::where('reviewer', $emp_id)->where('is_deleted', 0)->where('reviewer_status', 'on_going')->count();
                    $reviewerneadCount = EntryProcessModel::where('reviewer', $emp_id)->where('is_deleted', 0)->where('reviewer_status', 'need_support')->count();
                    $reviewercorrectionCount = EntryProcessModel::where('reviewer', $emp_id)->where('is_deleted', 0)->whereIn('reviewer_status', ['correction_1', 'correction_2', 'correction_3', 'plag_correction'])->count();
                    $projectlist = ProjectLogs::where('employee_id', $emp_id)->whereIn('project_id', $writerDAtaId)->get();
                } elseif ($emp_pos == 10) {
                    // If the position is 10, check the journal column
                    $journalCount = EntryProcessModel::where('journal', $emp_id)->where('is_deleted', 0)->count();
                    $writerDAtaId = EntryProcessModel::where('journal', $emp_id)->where('is_deleted', 0)->get()->pluck('id');
                    $journalPendingCount = EntryProcessModel::where('journal', $emp_id)->where('is_deleted', 0)->where('process_status', '!=', 'completed')->count();
                    $projectlist = ProjectLogs::where('employee_id', $emp_id)->whereIn('project_id', $writerDAtaId)->get();
                } elseif ($emp_pos == 11) {
                    // If the position is 11, check the statistican column
                    $statisticanCount = EntryProcessModel::where('statistican', $emp_id)->where('is_deleted', 0)->count();
                    $writerDAtaId = EntryProcessModel::where('statistican', $emp_id)->where('is_deleted', 0)->get()->pluck('id');
                    $statisticanPendingCount = EntryProcessModel::where('statistican', $emp_id)->where('is_deleted', 0)->where('process_status', '!=', 'completed')->count();
                    $projectlist = ProjectLogs::where('employee_id', $emp_id)->whereIn('project_id', $writerDAtaId)->get();
                }

                // Loop through the projects to calculate the date differences
                foreach ($projectlist as $project) {
                    // Check if the project status is 'completed'
                    if ($project->status == 'completed') {
                        // Get the status_date of the project
                        $statusDate = Carbon::parse($project->status_date);

                        // Use status_date as the reference (no need for current date comparison)
                        $completedDate = Carbon::parse($project->status_date);

                        // Get the difference in days from the 'completed' status date
                        $daysDifference = $completedDate->diffInDays($statusDate); // This will compare status_date to itself (adjust if another date is needed)

                        // Increment the corresponding counter based on the number of days since completion
                        if ($daysDifference < 4) {
                            $completedIn4Days++;
                        } elseif ($daysDifference >= 5 && $daysDifference <= 8) {
                            $completedIn5To8Days++;
                        } elseif ($daysDifference > 8) {
                            $completedInMoreThan8Days++;
                        }
                    }
                }


                // Add the counts to the person's data for response
                $entry->writer_count = $writerCount;
                $entry->reviewer_count = $reviewerCount;
                $entry->journal_count = $journalCount;
                $entry->statistican_count = $statisticanCount;
                $entry->writerPendingCount = $writerPendingCount;
                $entry->reviewerPendingCount = $reviewerPendingCount;
                $entry->statisticanPendingCount = $statisticanPendingCount;
                $entry->journalPendingCount = $journalPendingCount;


                $entry->writerongoingCount  = $writerongoingCount ;
                $entry->reviewerongoingCount  = $reviewerongoingCount ;

                $entry->writerneadCount  = $writerneadCount ;
                $entry->reviewerneadCount  = $reviewerneadCount ;

                $entry->writercorrectionCount  = $writercorrectionCount ;
                $entry->reviewercorrectionCount  = $reviewercorrectionCount ;

                // Add the calculated counts to the person's data for response
                $entry->completed_in_4_days = $completedIn4Days;
                $entry->completed_in_5_to_8_days = $completedIn5To8Days;
                $entry->completed_in_more_than_8_days = $completedInMoreThan8Days;
            }


            //thesis project 
            $allWriterData = [];

            $totalProjectsthwriter = People::with(['createdByUser'])
                ->where('position', 7)
                ->get();

            // Loop through each freelancer and accumulate the counts
            foreach ($totalProjectsthwriter as $entry) {
                $emp_id = $entry->id;
                $emp_name = $entry->employee_name;

                // Count projects for each status
                $projects = EntryProcessModel::where('type_of_work', 'thesis')
                    ->where('writer', $emp_id)
                    ->where('is_deleted', 0)
                    ->count();

                $projectspending = EntryProcessModel::where('type_of_work', 'thesis')
                    ->where('writer', $emp_id)
                    ->where('writer_status', 'to_do')
                    ->where('is_deleted', 0)
                    ->count();

                $projectsongoing = EntryProcessModel::where('type_of_work', 'thesis')
                    ->where('writer', $emp_id)
                    ->where('writer_status', 'on_going')
                    ->where('is_deleted', 0)
                    ->count();

                $projectneadsupport = EntryProcessModel::where('type_of_work', 'thesis')
                    ->where('writer', $emp_id)
                    ->where('writer_status', 'need_support')
                    ->where('is_deleted', 0)
                    ->count();

                $projectcorrection = EntryProcessModel::where('type_of_work', 'thesis')
                    ->where('writer', $emp_id)
                    ->whereIn('writer_status', ['correction_1', 'correction_2', 'correction_3'])
                    ->where('is_deleted', 0)
                    ->count();

                $projectplagcorrection = EntryProcessModel::where('type_of_work', 'thesis')
                    ->where('writer', $emp_id)
                    ->where('writer_status', 'plag_correction')
                    ->where('is_deleted', 0)
                    ->count();

                // Accumulate the counts into $writerdata
                $allWriterData[] = [
                    'id' => $emp_id,
                    'position' => 7,
                    'employee_name' => $emp_name,
                    'total_project' => $projects,
                    'pending' => $projectspending,
                    'on_going' => $projectsongoing,
                    'need_support' => $projectneadsupport,
                    'correction' => $projectcorrection,
                    'plag_correction' => $projectplagcorrection,
                ];
            }

            $urgentDataList = EntryProcessModel::where('hierarchy_level', 'urgent_important')->where('is_deleted', 0)->orderBy('id','desc')->get();
            $urgentDataListCount = EntryProcessModel::where('hierarchy_level', 'urgent_important')->where('is_deleted', 0)->count();

            $projectdelayDataList = EntryProcessModel::where('process_status', '!=', 'completed')->where('is_deleted', 0)->orderBy('id','desc')->get();
            $projectdelayDataCount = EntryProcessModel::where('process_status', '!=', 'completed')->where('is_deleted', 0)->count();


            // Prepare response data
            $responseData = [
                'peoplewise' => $totalProjects,
                'peopleInhouse' => $totalProjectsInhouse,
                'peopleExternal' => $totalProjectsFreelancer,
                'peopleWriterExternal' => $allWriterData,
                'typeofwork' => $typeOfWorkCounts,
                'writerCount' => $writerCount,
                'reviewerCount' => $reviewerCount,
                'process_staus' => $processStatusCounts,
                'journal_status' => $journalStatusCounts,
                'completedCount' => $completedCounts,
                'emergencywork' => $urgentImportantCount,
                'not_assigned' => $notAssignedCount,
                'project_delaycount' => $projectDelayCount,
                'freelancet_payment_count' => $freelancerPaymentCount,
                'filtered_count' => $filteredCount,
                'total_count' => $totalCount,
                'position' => $position,
                'type_of_work' => array_keys($typeOfWorkCounts),
                'monthwiseData' => $monthwiseData,
                'to_do_data' => $tasks,
                'to_do_count' => $to_docount,
                'inWorks' => $inWorks,
                'inworks_count' => $inWorksCount,
                'reviews' => $reviews,
                'reviewcount' => $reviewerCount,
                'completed' => $completed,
                'completeCount' => $completedCount,
                'corrections' => $corrections,
                'correctionCount' => $correctionsCount,
                'writerStatusCounts' => $writerStatusCounts,
                'reviewerStatusCounts' => $reviewerStatusCounts,
                'paymentStatusCounts' => $paymentStatusCounts,
                'inProgress' => $inProgress,
                'inProgressCount' => $inProgressCount,
                'urgentDataList' => $urgentDataList,
                'urgentDataListCount' => $urgentDataListCount,
                'projectdelayDataList' => $projectdelayDataList,
                'projectdelayDataCount' => $projectdelayDataCount,
                'freelancerProjecctList' => $freelancers,
                'freelancerProjecctCount' => $freelancerPaymentCount,
                'projectStatusList' => $projectStatusList,
                'projectStatusCount' => $projectStatusCount
            ];

            return response()->json($responseData);
        } else if ($position === '7' || $position === '8') {

            $created_by = $request->created_by;

            // Get entries based on position
            if ($position === '7') {
                $position_e = 'writer';
                $entries = EntryProcessModel::where('is_deleted', 0)->where('writer', $created_by)->get();
            } else {
                $position_e = 'reviewer';
                $entries = EntryProcessModel::where('is_deleted', 0)->where('reviewer', $created_by)->get();
            }

            // Initialize counters and data structures
            $urgentImportantCount = 0;
            $notAssignedCount = 0;
            $writerCount = 0;
            $reviewerCount = 0;

            $manuscriptData = [
                'completed' => ['writer' => 0, 'reviewing' => 0],
                'plag_correction' => ['writer' => 0, 'reviewing' => 0],
                'correction_1' => ['writer' => 0, 'reviewing' => 0],
                'ongoing' => ['writer' => 0, 'reviewing' => 0],
            ];

            $thesisData = [
                'completed' => ['writer' => 0, 'reviewing' => 0],
                'plag_correction' => ['writer' => 0, 'reviewing' => 0],
                'correction_1' => ['writer' => 0, 'reviewing' => 0],
                'ongoing' => ['writer' => 0, 'reviewing' => 0],
            ];

            // Process entries
            // Fetch all entries for the current writer/reviewer at once
            $entries = EntryProcessModel::where(function ($query) use ($created_by) {
                $query->where('writer', $created_by)
                    ->orWhere('reviewer', $created_by);
            })->where('is_deleted', 0)
                ->get();

            // Initialize counters for urgent/important and not assigned entries
            $urgentImportantCount = 0;
            $notAssignedCount = 0;

            // Initialize manuscript and thesis data
            $manuscriptData = [
                'completed' => ['writer' => 0, 'reviewing' => 0],
                'plag_correction' => ['writer' => 0, 'reviewing' => 0],
                'correction' => ['writer' => 0, 'reviewing' => 0],
                'ongoing' => ['writer' => 0, 'reviewing' => 0],
            ];
            $thesisData = $manuscriptData; // Initialize thesisData similarly

            // Initialize writer and reviewer count variables
            $writerCount = 0;
            $reviewerCount = 0;

            $correctionStatuses = ['correction_1', 'correction_2', 'correction_3'];

            foreach ($entries as $entry) {
                // Count urgent/important and not assigned entries
                if ($entry->hierarchy_level === 'urgent_important') {
                    $urgentImportantCount++;
                }
                if ($entry->process_status === 'not_assigned') {
                    $notAssignedCount++;
                }

                // Writer and reviewer count (only count once, outside the loop)
                if ($entry->writer === $created_by) {
                    $writerCount++;
                }
                if ($entry->reviewer === $created_by) {
                    $reviewerCount++;
                }

                // Process manuscript or thesis data
                $data = null;
                if ($entry->type_of_work === 'manuscript') {
                    $data = &$manuscriptData;
                } elseif ($entry->type_of_work === 'thesis') {
                    $data = &$thesisData;
                }

                if ($data) {
                    // Writer-specific logic
                    if ($entry->writer === $created_by) {
                        $data['completed']['writer'] += ($entry->writer_status === 'completed') ? 1 : 0;
                        $data['plag_correction']['writer'] += ($entry->writer_status === 'plag_correction') ? 1 : 0;
                        $data['ongoing']['writer'] += in_array($entry->writer_status, ['on_going', 'need_support']) ? 1 : 0;

                        foreach ($correctionStatuses as $status) {
                            $data['correction']['writer'] += ($entry->writer_status === $status) ? 1 : 0;
                        }
                    }

                    // Reviewer-specific logic
                    if ($entry->reviewer === $created_by) {
                        $data['completed']['reviewing'] += ($entry->reviewer_status === 'completed') ? 1 : 0;
                        $data['plag_correction']['reviewing'] += ($entry->reviewer_status === 'plag_correction') ? 1 : 0;
                        $data['ongoing']['reviewing'] += in_array($entry->reviewer_status, ['on_going', 'need_support']) ? 1 : 0;

                        foreach ($correctionStatuses as $status) {
                            $data['correction']['reviewing'] += ($entry->reviewer_status === $status) ? 1 : 0;
                        }
                    }
                }
            }


            // Define a function to get tasks based on status and position
            function getTasksByStatus($position_e, $created_by, $statusArray = null, $statusField = null)
            {
                $query = EntryProcessModel::with(['paymentProcess'])
                    ->select('id', 'title', 'type_of_work', 'process_status', 'project_id', 'entry_date', 'hierarchy_level')
                    ->where('is_deleted', 0)
                    ->where($position_e, $created_by);

                if ($statusArray) {
                    // Apply the respective status check based on the position (writer or reviewer)
                    if ($position_e === 'writer') {
                        $query->whereIn('writer_status', $statusArray);
                    } elseif ($position_e === 'reviewer') {
                        $query->whereIn('reviewer_status', $statusArray);
                    }
                }

                if ($statusField) {
                    // Select specific status date field based on the position
                    if ($position_e === 'writer') {
                        $query->addSelect('writer_status_date');
                    } elseif ($position_e === 'reviewer') {
                        $query->addSelect('reviewer_status_date');
                    }
                }

                return $query;
            }

            // Fetch tasks based on status and position for each case
            $tasks = getTasksByStatus($position_e, $created_by, ['to_do'], 'writer_status_date')->orderBy('id','desc')->get();
            $tasksCount = getTasksByStatus($position_e, $created_by, ['to_do'], 'writer_status_date')->count();

            $inWorks = getTasksByStatus($position_e, $created_by, ['on_going', 'need_support'], 'writer_status_date')->orderBy('id','desc')->get();
            $inWorksCount = getTasksByStatus($position_e, $created_by, ['on_going', 'need_support'], 'writer_status_date')->count();

            $reviews = getTasksByStatus($position_e, $created_by, ['reviews'], 'writer_status_date')->orderBy('id','desc')->get();
            $reviewsCount = getTasksByStatus($position_e, $created_by, ['reviews'], 'writer_status_date')->count();

            $completed = getTasksByStatus($position_e, $created_by, ['completed'], 'writer_status_date')->orderBy('id','desc')->get();
            $completedCount = getTasksByStatus($position_e, $created_by, ['completed'], 'writer_status_date')->count();

            $corrections = getTasksByStatus($position_e, $created_by, ['correction_1', 'correction_2', 'correction_3'], 'writer_status_date')->orderBy('id','desc')->get();
            $correctionsCount = getTasksByStatus($position_e, $created_by, ['correction_1', 'correction_2', 'correction_3'], 'writer_status_date')->count();


            // Prepare the response data
            $filteredCount = $entries->count();
            $totalCount = EntryProcessModel::where('is_deleted', 0)->count();

            $responseData = [
                'emergencywork' => $urgentImportantCount,
                'not_assigned' => $notAssignedCount,
                'manuscript_data' => $manuscriptData,
                'thesisData' => $thesisData,
                'filtered_count' => $filteredCount,
                'total_count' => $totalCount,
                'position' => $position,
                'to_do_data' => $tasks,
                'to_do_count' => $tasksCount,
                'inWorks' => $inWorks,
                'inWorksCount' => $inWorksCount,
                'reviews' => $reviews,
                'reviewerCount' => $reviewerCount,
                'completed' => $completed,
                'completedCount' => $completedCount,
                'corrections' => $corrections,
                'correctionsCount' => $correctionsCount,
                'writerCount' => $writerCount,
                'reviewerCount' => $reviewerCount,
            ];

            return response()->json($responseData);
        }

        return [];
    }

    public function monthWiseTable($position)
    {
        if ($position === 'project_manager' || $position === 'admin') {
            // Get entries for the current year
            $currentYear = date('Y');
            $entries = EntryProcessModel::where('is_deleted', 0)
                ->whereYear('entry_date', $currentYear)
                ->get();

            // Initialize an array to store monthly data
            $monthlyData = [];

            // Loop through each month of the year
            for ($month = 1; $month <= 12; $month++) {
                $monthEntries = $entries->filter(function ($entry) use ($month) {
                    return date('m', strtotime($entry->entry_date)) == $month;
                });

                $typeOfWorkCounts = [
                    'manuscript' => 0,
                    'thesis' => 0,
                    'statistics' => 0,
                    'presentation' => 0,
                    'others' => 0,
                ];

                // Count entries by type_of_work
                foreach ($monthEntries as $entry) {
                    if (isset($typeOfWorkCounts[$entry->type_of_work])) {
                        $typeOfWorkCounts[$entry->type_of_work]++;
                    }
                }

                // Calculate total count for the month
                $totalCount = array_sum($typeOfWorkCounts);

                // Calculate percentages for each type_of_work
                $percentages = [];
                foreach ($typeOfWorkCounts as $key => $count) {
                    $percentages[$key] = $totalCount > 0 ? round(($count / $totalCount) * 100, 2) : 0;
                }

                // Calculate target percentage (based on a specific calculation you want)
                $targetPercentage = $totalCount > 0 ? round(($totalCount / 92), 2) : 0;

                // Add data for the current month
                $monthlyData[] = [
                    'month' => date('F', mktime(0, 0, 0, $month, 1)),
                    'manuscript' => $typeOfWorkCounts['manuscript'],
                    'statistics' => $typeOfWorkCounts['statistics'],
                    'thesis' => $typeOfWorkCounts['thesis'],
                    'others' => $typeOfWorkCounts['others'],
                    'presentation' => $typeOfWorkCounts['presentation'],
                    'total' => $totalCount,
                    'manuscript_percentage' => $percentages['manuscript'],
                    'statistics_percentage' => $percentages['statistics'],
                    'thesis_percentage' => $percentages['thesis'],
                    'target_percentage' => $targetPercentage,
                ];
            }

            return $monthlyData;  // Return the processed data directly, not as a response
        }

        return [];
    }

    public function projectStatus(Request $request, $id)
    {
        // $id = $request->project_id;
        // Find the project by ID
        $project = EntryProcessModel::find($id);

        if ($project) {
            // Check if 'project_status' is provided in the request
            if ($request->has('status') && $request->type !== 'assignstatus') {
                $project->process_status = $request->status;
            }


            if ($request->status === 'completed') {
                // Prepare email recipients
                $emails = [
                    'projectManager' => User::where('position', 13)->first()?->email_address,
                    'teamManager' => User::where('position', 14)->first()?->email_address,
                    'adminEmail' => User::where('position', 'Admin')->first()?->email_address,
                ];

                $employeedetails = User::with(['createdByUser'])->where('id', $request->created_by)->first();
                $projectDetails = EntryProcessModel::where('id', $id)->first();

                if (!empty($emails['projectManager']) && !empty($emails['teamManager']) && !empty($emails['adminEmail']) && !empty($employeedetails->email_address)) {
                    try {
                        // Send email to writer with CC to others
                        Mail::to($emails['projectManager'], $emails['teamManager'])
                            ->cc($emails['adminEmail'])
                            ->send(new TaskCompleteEmail([
                                'projectManagerEmail' => $emails['projectManager'],
                                'teamManagerEmail' => $emails['teamManager'],
                                'adminEmail' => $emails['adminEmail'],
                                'writer_status' => $project->process_status,
                                'employee_name' => $employeedetails->employee_name,
                                'role' => $employeedetails->createdByUser?->name,
                                'phone_number' => $employeedetails->phone_number,
                                'project_id' => $projectDetails->project_id
                            ]));

                        return response()->json(['success' => true, 'message' => 'Email sent successfully.']);
                    } catch (\Exception $e) {
                        Log::error('Mail failed: ' . $e->getMessage());

                        return response()->json(['success' => false, 'message' => 'Failed to send email.']);
                    }
                } else {
                    Log::error('One or more email addresses are missing.');
                    return response()->json(['success' => false, 'message' => 'One or more email addresses are missing.']);
                }
            }

            // Check if 'assign_by' is provided in the request
            if ($request->has('assign_by')) {
                $project->assign_by = $request->assign_by;
                $project->assign_date =  Carbon::now();

                if ($request->position === '7') {
                    $project->writer = $request->assign_by;
                    $project->writer_assigned_date =  Carbon::now()->toDateString();
                }

                if ($request->position === '8') {
                    $project->reviewer = $request->assign_by;
                    $project->reviewer_assigned_date =  Carbon::now()->toDateString();
                }

                if ($request->position === '11') {
                    $project->statistican = $request->assign_by;
                    $project->statistican_assigned_date =  Carbon::now()->toDateString();
                }

                if ($request->position === '10') {
                    $project->journal = $request->assign_by;
                    $project->journal_assigned_date =  Carbon::now()->toDateString();
                }
            }

            if ($request->type === 'assignstatus') {
                if ($request->position === 'writer') {
                    $project->writer_status = $request->status;
                }
                if ($request->position === 'reviewer') {
                    $project->reviewer_status = $request->status;
                }
                if ($request->position === 'statistican') {
                    $project->statistican_status = $request->status;
                }
                if ($request->position === 'journal') {
                    $project->journal_status = $request->status;
                }
            }
            $emailcheck = false;

            // Handle 'accepted' or 'rejected' types for project status
            if ($request->type === 'accepted' || $request->type === 'rejected') {
                // if ($request->type === 'accepted') {
                $projectstatus = ProjectStatus::where('project_id', $id)->first();
                $entrystatus = EntryProcessModel::where('id', $id)->first();

                if ($projectstatus) {
                    if ($request->project_status === 'writer') {
                        $projectstatus->writer_status = $request->type;
                        $entrystatus->writer_status = 'on_going';
                    }

                    if ($request->project_status === 'reviewer') {
                        $projectstatus->reviewer_status = $request->type;
                        $entrystatus->reviewer_status = 'on_going';
                    }

                    // Save the project status if updated
                    $projectstatus->save();
                    $entrystatus->save();
                }

                if ($request->type === 'accepted') {
                    $emailcheck = true;
                }
            }

            // Save the updated project details
            $project->save();

            if ($emailcheck == true) {

                // Prepare email recipients
                $emails = [
                    'projectManager' => User::where('position', 13)->first()?->email_address,
                    'teamManager' => User::where('position', 14)->first()?->email_address,
                    'adminEmail' => User::where('position', 'Admin')->first()?->email_address,
                ];

                $employeedetails = User::with(['createdByUser'])->where('id', $request->created_by)->first();
                $projectDetails = EntryProcessModel::where('id', $id)->first();

                if (!empty($emails['projectManager']) && !empty($emails['teamManager']) && !empty($emails['adminEmail']) && !empty($employeedetails->email_address)) {
                    try {
                        // Send email to writer with CC to others
                        Mail::to($emails['projectManager'], $emails['teamManager'])
                            ->cc($emails['adminEmail'])
                            ->send(new TaskEmail([
                                'projectManagerEmail' => $emails['projectManager'],
                                'teamManagerEmail' => $emails['teamManager'],
                                'adminEmail' => $emails['adminEmail'],
                                'writer_status' => $project->process_status,
                                'employee_name' => $employeedetails->employee_name,
                                'role' => $employeedetails->createdByUser?->name,
                                'phone_number' => $employeedetails->phone_number,
                                'project_id' => $projectDetails->project_id
                            ]));

                        return response()->json(['success' => true, 'message' => 'Email sent successfully.']);
                    } catch (\Exception $e) {
                        Log::error('Mail failed: ' . $e->getMessage());

                        return response()->json(['success' => false, 'message' => 'Failed to send email.']);
                    }
                } else {
                    Log::error('One or more email addresses are missing.');
                    return response()->json(['success' => false, 'message' => 'One or more email addresses are missing.']);
                }
            }
        }
    }

    public function getPendingList(Request $request, $id)
    {
        $paymentsWriterLogs = ProjectLogs::where([
            ['project_id', '=', $id],
            ['status_type', '=', 'writer'],
            ['status', '!=', 'completed'],
        ])->latest()->first();

        // Calculate the days from assigned_date to today for writer logs
        $writerDaysCount = 0;
        if ($paymentsWriterLogs && $paymentsWriterLogs->assigned_date) {
            $assignedDate = Carbon::parse($paymentsWriterLogs->assigned_date);
            $writerDaysCount = $assignedDate->diffInDays(Carbon::now());
        }

        // Get the latest reviewer log
        $paymentsReviewerLogs = ProjectLogs::where([
            ['project_id', '=', $id],
            ['status_type', '=', 'reviewer'],
            ['status', '!=', 'completed'],
        ])->latest()->first();

        // Calculate the days from assigned_date to today for reviewer logs
        $reviewerDaysCount = 0;
        if ($paymentsReviewerLogs && $paymentsReviewerLogs->assigned_date) {
            $assignedDate = Carbon::parse($paymentsReviewerLogs->assigned_date);
            $reviewerDaysCount = $assignedDate->diffInDays(Carbon::now());
        }


        $projectDetails = EntryProcessModel::find($id);

        // Calculate the days from assigned_date to today for reviewer logs
        $projectDaysCount = 0;
        if ($projectDetails && $projectDetails->entry_date) {
            $assignedDate = Carbon::parse($projectDetails->entry_date);
            $projectDaysCount = $assignedDate->diffInDays(Carbon::now());
        }

        // Get the latest writer log with "completed" status
        $completedWriterLogs = ProjectLogs::where([
            ['project_id', '=', $id],
            ['status_type', '=', 'writer'],
            ['status', '=', 'completed'],
        ])->latest()->first();

        // Calculate the writer payment due date (status_date + 21 days)
        $writerPaymentDueDate = 0;
        if ($completedWriterLogs && $completedWriterLogs->status_date) {
            $statusDate = Carbon::parse($completedWriterLogs->status_date);
            $writerPaymentDueDate = $statusDate->addDays(21)->toDateString();
        }


        // Calculate the reviewer payment due date (status_date + 21 days) and days count
        $writerPaymentDueDate = null;
        $writerPaymentDueDaysCount = 0;

        if ($completedWriterLogs && $completedWriterLogs->status_date) {
            $statusDate = Carbon::parse($completedWriterLogs->status_date);

            // Calculate the payment due date (status_date + 21 days)
            $writerPaymentDueDate = $statusDate->addDays(21)->toDateString();

            // Calculate days count from status_date to today
            $writerPaymentDueDaysCount = $statusDate->diffInDays(Carbon::now());
        }

        // Get the latest reviewer log with "completed" status
        $completedReviewerLogs = ProjectLogs::where([
            ['project_id', '=', $id],
            ['status_type', '=', 'reviewer'],
            ['status', '=', 'completed'],
        ])->latest()->first();

        // Calculate the reviewer payment due date (status_date + 21 days) and days count
        $reviewerPaymentDueDate = null;
        $reviewerPaymentDueDaysCount = 0;

        if ($completedReviewerLogs && $completedReviewerLogs->status_date) {
            $statusDate = Carbon::parse($completedReviewerLogs->status_date);

            // Calculate the payment due date (status_date + 21 days)
            $reviewerPaymentDueDate = $statusDate->addDays(21)->toDateString();

            // Calculate days count from status_date to today
            $reviewerPaymentDueDaysCount = $statusDate->diffInDays(Carbon::now());
        }



        // Prepare data to return
        $data = [
            'project_id' => $projectDetails->project_id,
            'writer_days_count' => $writerDaysCount,
            'reviewer_days_count' => $reviewerDaysCount,
            'project_delay_count' => $projectDaysCount,
            'writerPaymentDueDays' => $writerPaymentDueDaysCount,
            'reviewerPaymentDueDate' => $reviewerPaymentDueDaysCount,
            'writerPaymentDueDate' => $writerPaymentDueDate,
            'reviewerPaymentDueD' => $reviewerPaymentDueDate
        ];

        // Return success response
        return response()->json([
            'success' => true,
            'message' => 'Pending list retrieved successfully',
            'data' => $data
        ], 200);
    }

    public function getClientNotification(Request $request, $id)
    {
        $projectDetails = EntryProcessModel::find($id);


        if ($projectDetails) {
            if (!empty($projectDetails->email)) {
                try {
                    Mail::to($projectDetails->email)->send(new ClientEmail([
                        'client_name' => $projectDetails->client_name,
                        'type_of_work' => $projectDetails->type_of_work,
                        'project_id' => $projectDetails->project_id,
                        'project_title' => $projectDetails->title,
                        'project_status' => $projectDetails->project_status,
                        'contact_number' => $projectDetails->contact_number
                    ]));

                    return response()->json(['success' => true, 'message' => 'Email sent successfully.']);
                } catch (\Exception $e) {
                    // Log the error
                    Log::error('Mail failed: ' . $e->getMessage());

                    return response()->json(['success' => false, 'message' => 'Failed to send email.']);
                }
            } else {
                Log::error('Client email is empty or invalid');
                return response()->json(['success' => false, 'message' => 'Client email is empty or invalid.']);
            }
        } else {
            Log::error('Project not found');
            return response()->json(['success' => false, 'message' => 'Project not found.']);
        }
    }



    public function getProjectList(Request $request)
    {
        $type = $request->input('type'); // writer or reviewer
        $createdBy = $request->input('createdby'); // User ID
        $start_date = $request->query('start_date');
        $end_date = $request->query('end_date');

        $typeOfWork = $request->input('type_of_work');

        // Base query for filtering projects
        $query = EntryProcessModel::query();

        // Filter by type (writer or reviewer)
        if ($type === 'writer') {
            $query->where('writer', $createdBy);
        } elseif ($type === 'reviewer') {
            $query->where('reviewer', $createdBy);
        }

        // // Apply additional filters
        // if (!empty($startDate) && !empty($endDate)) {
        //     $query->whereBetween('entry_date', [$startDate, $endDate]);
        // }
        if (!empty($start_date) && !empty($end_date)) {
            $query->whereBetween('entry_date', [$start_date, $end_date]);
        }

        if (!empty($typeOfWork) && $typeOfWork !== 'all') {
            $query->where('type_of_work', $typeOfWork);
        }

        // Fetch data
        $projects = $query->get();

        $typeofwork = EntryProcessModel::where('is_deleted', 0)
            ->select('type_of_work')
            ->groupBy('type_of_work')
            ->get();

        // // Count for writer and reviewer
        // $writerCount = EntryProcessModel::where('writer', $createdBy)->count();
        // $reviewerCount = EntryProcessModel::where('reviewer', $createdBy)->count();

        // Return response
        return response()->json([
            'details' => $projects,
            'typeofwork' => $typeofwork
        ]);
    }


    public function getEmpProjectList(Request $request)
    {
        $type = $request->input('type'); // writer or reviewer
        $createdBy = $request->input('createdby'); // User ID
        $typeOfWork = $request->input('type_of_work');
        $start_date = $request->query('start_date');
        $end_date = $request->query('end_date');

        // Base query for filtering projects
        $query = EntryProcessModel::query()
            ->select('id', 'project_id', 'title', 'type_of_work')->where('is_deleted', 0);

        $completedIn4Days = 0;
        $completedIn5To8Days = 0;
        $completedInMoreThan8Days = 0;
        $statusCounts = [
            'completed_in_4_days' => 0,
            'completed_in_5_to_8_days' => 0,
            'completed_in_more_than_8_days' => 0,
            'other_status' => 0
        ];

        // Filter based on type
        if ($type === '7') {
            $query->where('writer', $createdBy)
                ->addSelect('writer_assigned_date as assigned_date', 'writer_status as status', 'writer as createdby');
        } elseif ($type === '8') {
            $query->where('reviewer', $createdBy)
                ->addSelect('reviewer_assigned_date as assigned_date', 'reviewer_status as status', 'reviewer as createdby');
        } elseif ($type === '10') {
            $query->where('statistican', $createdBy)
                ->addSelect('statistican_assigned_date as assigned_date', 'statistican_status as status', 'statistican as createdby');
        } elseif ($type === '11') {
            $query->where('journal', $createdBy)
                ->addSelect('journal_assigned_date as assigned_date', 'journal_status as status', 'journal as createdby');
        }

        // Filter by type of work
        if (!empty($typeOfWork) && $typeOfWork !== 'all') {
            $query->where('type_of_work', $typeOfWork);
        }

        if (!empty($start_date) && !empty($end_date)) {
            $query->whereBetween('entry_date', [$start_date, $end_date]);
        }
        // Fetch the projects
        $projects = $query->get();

        foreach ($projects as $project) {
            $projectid = $project->id;
            $empid = $project->createdby;
            // Initialize the properties with default values
            $project->completedIn4Days = '-';
            $project->completedIn5To8Days = '-';
            $project->completedInMoreThan8Days = '-';

            // Fetch the latest completed project log
            $projectstatus = ProjectLogs::where('project_id', $projectid)
                ->where('status', '=', 'completed')
                ->where('employee_id', $empid)
                ->latest()
                ->first();

            if ($projectstatus) {
                // Calculate the difference in days
                $statusDateTime = new \DateTime($projectstatus->status_date);
                $completedDateTime = new \DateTime($projectstatus->created_date);
                // Calculate the difference between the dates
                $interval = $statusDateTime->diff($completedDateTime);

                // Get the days difference, excluding the start date
                $daysDifference = $interval->days + 1;
                // Categorize based on day difference
                if ($daysDifference <= 4) {
                    $project->completedIn4Days = '<4 days';
                } elseif ($daysDifference >= 5 && $daysDifference <= 8) {
                    $project->completedIn5To8Days = '5 to 8 days';
                } elseif ($daysDifference >= 9) {
                    $project->completedInMoreThan8Days = '>9 days';
                }
            } else {
                $project->completedIn4Days = '-';
                $project->completedIn5To8Days = '-';
                $project->completedInMoreThan8Days = '-';
            }
        }

        // Fetch distinct types of work
        $typeOfWorkOptions = EntryProcessModel::where('is_deleted', 0)
            ->select('type_of_work')
            ->groupBy('type_of_work')
            ->get();

        // Return response
        return response()->json([
            'details' => $projects,
            'typeofwork' => $typeOfWorkOptions
        ]);
    }

    //getting the upload the  csv file for the phpmyadmin
    public function uploadCsv(Request $request)
    {
        $file = $request->file('file');
        $filename = $file->getClientOriginalName();
        $file->move(public_path('uploads'), $filename);

        $path = public_path('uploads/' . $filename);
        $data = array_map('str_getcsv', file($path));
        $csv_data = array_slice($data, 1);

        foreach ($csv_data as $key => $value) {
            $insert_data = array(
                'Agent' => $value[0],
                'submission_date' => $value[1],
                'policy_status' => $value[2],
                'Eff_Date' => $value[3],
                'issuer' => $value[4],
                'state' => $value[5],
                'ffm_app_id' => $value[6],
                'first_name' => $value[7],
                'last_name' => $value[8],
                'PMPM' => $value[9],
                'Advance' => $value[10],
                'Members' => $value[11],
                'Advance_Excluded_Reason' => $value[12],
                'Post_Date' => $value[13],
                'created_by' => $value[14],
                'is_deleted' => $value[15],
                'created_at' => $value[16],
                'updated_at' => $value[17],
            );

            EntryProcessModel::insert($insert_data);
        }

        return response()->json(['success' => true, 'message' => 'CSV file uploaded successfully.']);
    }

    public function getEmployeeProjectList(Request $request)
    {
        $type = $request->input('type'); // writer or reviewer
        $createdBy = $request->input('createdby'); // User ID
        $typeOfWork = $request->input('type_of_work', 'all'); // Default to 'all'
        $start_date = $request->query('start_date');
        $end_date = $request->query('end_date');

        // Initialize the response data
        $allWriterData = [];

        // Fetch employees based on the type (position)
        $totalProjectsthwriter = EntryProcessModel::where('type_of_work', 'thesis')
            ->where('writer', $createdBy)
            ->where('is_deleted', 0)
            ->get();


        // Response data with type_of_work options
        $typeOfWorkOptions = EntryProcessModel::distinct()->pluck('type_of_work');

        return response()->json([
            'details' => $totalProjectsthwriter,
            'typeofwork' => $typeOfWorkOptions
        ]);
    }
}
