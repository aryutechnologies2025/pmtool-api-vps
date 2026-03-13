<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EmployeeModel;
use Illuminate\Support\Facades\Auth;


class LoginUserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $employees = EmployeeModel::select('emailID', 'password')->get();
        return response()->json($employees);
    }


    public function loginEmployee(Request $request)
    {
        if (Auth::attempt(['emailID' => $request->emailID, 'password' => $request->password])) {
            Auth::user();
            $getName = auth()->user()->first_name;
            return response()->json([
                'message' => [('Login successful'), ($getName)],

            ], 200);
        } else {
            return response()->json([
                'message' => 'Login failed, invalid credentials'
            ], 401);
        }
    }
    public function logout()
    {
        
        if (Auth::check()) {    
            Auth::logout();
            return response()->json(['message' => 'Dashboard opened successfully'], 200);
        } else {
            return response()->json(['message' => 'No user is in logged in'], 400);
        }
    }

    public function loginCount(){
        $count =EmployeeModel ::where('status', 0)->count();
        $fullCount = EmployeeModel::count();
      
        return response()->json ([$count , $fullCount]);
    }

    
}
 


