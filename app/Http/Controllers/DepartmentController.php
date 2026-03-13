<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DepartmentModel;
use Illuminate\Validation\Rule;
use App\Models\EntryProcessModel;

class DepartmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $details = DepartmentModel::where('is_deleted', 0)->orderBy('created_at', 'desc')->get();
        return response()->json($details);
    }



    public function store(Request $request)
    {
        $request->validate([
            'name' => 'unique:department_types',
        ]);

        $details = new DepartmentModel();
        $details->name = $request->name;
        $details->status = 'Active';
        $details->is_deleted = $request->is_deleted ?? 0;
        $details->created_by = $request->created_by ?? 0;
        $details->save();

        return response()->json($details);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $details = DepartmentModel::find($id);
        return response()->json($details);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'name' => [
                'required',
                Rule::unique('department_types')->ignore($id),
            ],
        ]);

        $request->validate([
            'name' => [
                'required',
                Rule::unique('department_types')->ignore($id),
            ],
        ]);

        $details = DepartmentModel::find($id);
        $details->name = $request->name;
        $details->status = 'Active';
        $details->is_deleted = $request->is_deleted ?? 0;
        $details->save();
        return response()->json($details);
    }

    /**
     * Remove the specified resource from storage.
     */
    // public function destroy(string $id)
    // {
    //     $details = DepartmentModel::find($id);
    //     $details->is_deleted = 1;

    //     $details->save();
    //     return response()->json($details);
    // }

    public function destroy(string $id)
    {
        $details = DepartmentModel::find($id);

         // Check if project_id exists in PaymentStatusModel
         $paymentExists = EntryProcessModel::where('department', $details->id)->exists();

         if ($paymentExists) {
             return response()->json([
                 'status' => 'error',
                 'message' => 'Cannot delete this department because a project exists.'
             ], 400);
         }

        if ($details) {
            $details->delete(); // Permanently delete the record
            return response()->json(['message' => 'Record deleted successfully']);
        }

        return response()->json(['error' => 'Record not found'], 404);
    }
}
