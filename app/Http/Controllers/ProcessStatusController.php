<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProcessStatusModel;
use App\Models\Entry;
use App\Models\EntryProcessModel;

class ProcessStatusController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $details = ProcessStatusModel::where('is_deleted', 0)->orderBy('created_at', 'desc')->get();
        return response()->json($details);
    }

    
    public function store(Request $request)
    {
        $details = new ProcessStatusModel();
        $details->process_title = $request->process_title ?? null;
        $details->process_status = $request->process_status ?? null;
        $details->process_status_date = $request->process_status_date ?? null;
        $details->process_commands = $request->process_commands ?? null;
        $details->writer = $request->writer ?? null;
        $details->writer_assigned_date = $request->writer_assigned_date ?? null;
        $details->writer_status = $request->writer_status ?? null;
        $details->writer_status_date = $request->writer_status_date ?? null;
        $details->reviewer = $request->reviewer ?? null;
        $details->reviewer_assigned_date = $request->reviewer_assigned_date ?? null;
        $details->statistican = $request->statistican ?? null;
       $details->statistican_assigned_date = $request->statistican_assigned_date ?? null;
       $details->statistican_status = $request->statistican_status ?? null;
       $details->statistican_status_date = $request->statistican_status_date ?? null;
       $details->reviewer_status = $request->reviewer_status ?? null;
        $details->reviewer_status_date = $request->reviewer_status_date ?? null;
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
        $details = ProcessStatusModel::find($id);
        return response()->json($details);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $details = ProcessStatusModel::find($id);
        $details->process_title = $request->process_title ?? null;
        $details->process_status = $request->process_status ?? null;
        $details->process_status_date = $request->process_status_date ?? null;
        $details->process_commands = $request->process_commands ?? null;
        $details->writer = $request->writer ?? null;
        $details->writer_assigned_date = $request->writer_assigned_date ?? null;
        $details->writer_status = $request->writer_status ?? null;
        $details->writer_status_date = $request->writer_status_date ?? null;
        $details->reviewer = $request->reviewer ?? null;
        $details->reviewer_assigned_date = $request->reviewer_assigned_date ?? null;
        $details->reviewer_status = $request->reviewer_status ?? null;
        $details->reviewer_status_date = $request->reviewer_status_date ?? null;
        $details->statistican = $request->statistican ?? null;
       $details->statistican_assigned_date = $request->statistican_assigned_date ?? null;
       $details->statistican_status = $request->statistican_status ?? null;
       $details->statistican_status_date = $request->statistican_status_date ?? null;
        $details->status = $request->status ?? 1;
        $details->is_deleted = $request->is_deleted ?? 0;
        $details->save();
        return response()->json($details);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $details = ProcessStatusModel::find($id);
        $details->is_deleted = 1;
       $details->status =0;
        $details->save();
        return response()->json($details);
    }



    public function getTitle()
    {
        
        $processes = EntryProcessModel::where('is_deleted', 0)->get(['title']);
      
        return response()->json($processes);
    }
}
