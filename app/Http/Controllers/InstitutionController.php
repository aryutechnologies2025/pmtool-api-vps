<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InstitutionModel;
use Illuminate\Validation\Rule;
use App\Models\EntryProcessModel;


class InstitutionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $details = InstitutionModel::where('is_deleted', 0)->orderBy('created_at', 'desc')->get();
        return response()->json($details);
    }


    public function store(Request $request)
    {
        $request->validate([
            'name' => 'unique:institutions',
        ]);

        $details = new InstitutionModel();
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
        $details = InstitutionModel::find($id);
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
                Rule::unique('institutions')->ignore($id),
            ],
        ]);

        $details = InstitutionModel::find($id);
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
    //     $details = InstitutionModel::find($id);
    //     $details->is_deleted = 1;

    //     $details->save();
    //     return response()->json($details);
    // }

    public function destroy(string $id)
    {
        $details = InstitutionModel::find($id);


         // Check if project_id exists in PaymentStatusModel
         $paymentExists = EntryProcessModel::where('institute', $details->id)->exists();

         if ($paymentExists) {
             return response()->json([
                 'status' => 'error',
                 'message' => 'Cannot delete this institution because a project exists.'
             ], 400);
         }

        if ($details) {
            $details->delete(); // Permanently delete the record
            return response()->json(['message' => 'Record deleted successfully']);
        }

        return response()->json(['error' => 'Record not found'], 404);
    }
}
