<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HierarchyModel;

class HierarchyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $details = HierarchyModel::where('is_deleted', 0)->get();
        return response()->json($details);
    }

    

    public function store(Request $request)
    {
      
        $details = new HierarchyModel();
        $details->institution = $request->institution;  
        $details->status = $request->status ?? 1;
        $details->is_deleted = $request->is_deleted ?? 0;
        $details->created_by = $request->created_by ?? 'Aryu';

        $details->save();
        
        return response()->json($details);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $details = HierarchyModel::find($id);
        return response()->json($details);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $details = HierarchyModel::find($id);
        $details->institution = $request->institution;  
        $details->status = $request->status ?? 0;
        $details->is_deleted = $request->is_deleted ?? 0;
        $details->save();
        return response()->json($details);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $details = HierarchyModel::find($id);
        $details->is_deleted = 1;
       
        $details->save();
        return response()->json($details);
    }
}
