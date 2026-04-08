<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PendingStatusModel;
use App\Models\EntryProcessModel;
class PendingStatusController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $details = PendingStatusModel::where('is_deleted', 0)->orderBy('created_at', 'desc')
        ->get();
        return response()->json($details);
    }

    
    public function store(Request $request)
    {

  // Ensure the project_id exists in entry_processes
  $entry = EntryProcessModel::where('project_id', $request->project_id)->first();

  if (!$entry) {
      return response()->json(['message' => 'Invalid project ID'], 400);
  }

        // DD($request->all());
        $details = new PendingStatusModel();
        $details->project_id = $request->project_id ?? null;
        $details->writer_pending_days = $request->writer_pending_days ?? null;
        $details->reviewer_pending_days = $request->reviewer_pending_days ?? null;
        $details->project_pending_days = $request->project_pending_days ?? null;
        $details->writer_payment_due_date = $request-> writer_payment_due_date ?? null;
        $details->reviewer_payment_due_date= $request->reviewer_payment_due_date ?? null;
        $details->status = $request->status ?? 1;
        $details->is_deleted = $request->is_deleted ?? 0;
        

        $details->save();
        
        return response()->json($details);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $details = PendingStatusModel::find($id);
        return response()->json($details);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $project_id)
    {
        // Find the pending process record by project_id
        $pendingProcess = PendingStatusModel::where('project_id', $project_id)->first();
    
        // Check if the record exists
        if (!$pendingProcess) {
            return response()->json(['message' => 'Pending process not found for the given project_id'], 404);
        }
    
        // Assign values to the record's properties
        $pendingProcess->writer_pending_days = $request->input('writer_pending_days');
        $pendingProcess->reviewer_pending_days = $request->input('reviewer_pending_days');
        $pendingProcess->project_pending_days = $request->input('project_pending_days');
        $pendingProcess->writer_payment_due_date = $request->input('writer_payment_due_date');
        $pendingProcess->reviewer_payment_due_date = $request->input('reviewer_payment_due_date');
        $pendingProcess->status = $request->status ?? 1;
    
        // Save the updated record
        $pendingProcess->save();
    
        return response()->json(['message' => 'Pending process updated successfully', 'data' => $pendingProcess], 200);
    }
    
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $details = PendingStatusModel::find($id);
        $details->is_deleted = 1;
       $details->status =0;
        $details->save();
        return response()->json($details);
    }
}
