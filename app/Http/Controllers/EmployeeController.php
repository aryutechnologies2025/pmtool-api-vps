<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EmployeeModel;
use App\Models\User;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $details = EmployeeModel::where('is_deleted', 0)->where('status', 1)->get();
        return response()->json($details);
    }


    public function store(Request $request)
    {
       
      
        $details = new EmployeeModel();
        $details->first_name = $request->first_name;  
        $details->last_name = $request->last_name;
        $details->emailID = $request->emailID;
        $details->phone = $request->phone;
        $details->address = $request->address;
        $details->role = $request->role;
        $details->username = $request->username;
        $details->password = bcrypt($request->password);
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
        $details = EmployeeModel::find($id);
        return response()->json($details);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $details = EmployeeModel::find($id);
        $details->first_name = $request->first_name;  
        $details->last_name = $request->last_name;
        $details->emailID = $request->emailID;
        $details->phone = $request->phone;
        $details->address = $request->address;
        $details->role = $request->role;
        $details->username = $request->username;
        $details->password = $request->password;
        $details->status = $request->status ?? 1;
        $details->is_deleted = $request->is_deleted ?? 0;
        $details->created_by = $request->created_by ?? 'Aryu';
        $details->save();
        return response()->json($details);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $details = EmployeeModel::find($id);
        $details->is_deleted = 1;
       $details->status = 0;
        $details->save();
        return response()->json($details);
    }
}
