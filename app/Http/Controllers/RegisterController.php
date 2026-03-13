<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RegisterModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\User;

use function Laravel\Prompts\password;

class RegisterController extends Controller
{
    public function login(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'User logged out successfully.',
        ], 200);
    }

    public function checklogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ], [
            'email.required' => 'Please a enter email.',
            'password.required' => 'Please enter a password.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
        // Check the user credentials
        // $user = RegisterModel::where('email', $request->email)->first();
        try {
            // Attempt to authenticate the user
            if (Auth::attempt(['email_address' => $request->email, 'password' => $request->password])) {

                if (!DB::connection()->getPdo()) {
                    DB::reconnect(); // optional, usually not needed unless you're explicitly closing connections
                }

                $user = Auth::user();
                // Fetch additional user details from another database connection
                $userhrms = DB::connection('mysql_medics_hrms')
                    ->table('employee_details')
                    ->select('id', 'employee_name', 'employee_type', 'position', 'department', 'date_of_joining', 'phone_number', 'email_address', 'profile_image')
                    ->where('status', '1')
                    ->where('email_address', $request->email)
                    ->first();

                if (!$userhrms) {
                    return response([
                        'message' => 'User not found in employee details.',
                    ], 404);
                }

                $userposition = $userhrms->position;
                $userEmpType = $userhrms->employee_type;

                // Fetch permissions based on the user's position

                $roleId = [];
                $roleName = [];
                if ($userposition !== 'Admin') {
                    $positions = explode(',', $userposition);

                    $roles = DB::connection('mysql_medics_hrms')
                        ->table('roles')
                        ->whereIn('id', $positions)
                        ->get();

                    // Loop through the roles and get the role ID and name
                    if ($roles->isNotEmpty()) {
                        // Loop to gather all the roles for the specified positions
                        foreach ($roles as $role) {
                            // Save role data
                            $roleId[] = $role->id; // Add to array of IDs
                            $roleName[] = $role->name; // Add to array of role names
                        }
                    } else {
                        $roleId = '';
                        $roleName = '';
                    }
                } else {
                    $roleId = ['Admin']; // For Admin, assign an empty string or desired default value
                    $roleName = ['Admin'];
                }


                $userpermission = DB::connection('mysql_medics_hrms')
                    ->table('role_permissions')
                    ->whereIn('role_id', $roleId)
                    ->where('status', 1)
                    ->pluck('permission')
                    ->toArray();

                // Generate a random token
                // $token = hash('sha256', Str::random(60));

                // session(['user_token' => $token]);

                // Issue a token for the authenticated user
                $token = $user->createToken('YourAppName')->plainTextToken;

                return response([
                    'data' => $user,
                    'permission' => $userpermission,
                    'token' => $token,
                    'rolename' => $roleId,
                    'employee_type' => $userEmpType,
                    'roleName' => $roleName,
                    'message' => 'User login successfully.'
                ], 200);
            } else {
                // Authentication failed, return an error
                return response([
                    'data' => 'error',
                    'message' => 'Invalid credentials.'
                ], 400);
            }
        } catch (\Exception $e) {
            // Log the exception and return a generic error message
            Log::error('Login error: ' . $e->getMessage()); // This will log the error in storage/logs/laravel.log

            // Return a generic error response
            return response([
                'message' => $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // public function logout(Request $request)
    // {
    //     // Revoke the user's token
    //     $request->user()->tokens->each(function ($token) {
    //         $token->delete();
    //     });


    //     return response()->json(['message' => 'User logged out successfully.'])->cookie($cookie);
    // }
    //  public function logoutlogin(Request $request)
    // {
    //     try {
    //         // Get the current user before logout
    //         $user = $request->user();

    //         // Revoke all tokens (if using Sanctum)
    //         if (method_exists($user, 'tokens')) {
    //             $user->tokens->each(function ($token) {
    //                 $token->delete();
    //             });
    //         }

    //         // Logout the user
    //         Auth::logout();

    //         // Invalidate the session
    //         $request->session()->invalidate();
    //         $request->session()->regenerateToken();

    //         // Close all database connections
    //         $this->closeDatabaseConnections();

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Successfully logged out'
    //         ]);

    //     } catch (\Exception $e) {
    //         Log::error('Logout error: ' . $e->getMessage());

    //         // Ensure connections are closed even if error occurs
    //         $this->closeDatabaseConnections();

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Logout failed',
    //             'error' =>  $e->getMessage()
    //         ], 500);
    //     }
    // }

    // /**
    //  * Close all active database connections
    //  */
    // protected function closeDatabaseConnections()
    // {
    //     try {
    //         // Close primary connection
    //         DB::disconnect();

    //         // Close HRMS connection if exists
    //         if (DB::connection('mysql_medics_hrms')) {
    //             DB::connection('mysql_medics_hrms')->disconnect();
    //         }

    //         // Close any other connections you might have
    //         // DB::connection('another_connection')->disconnect();

    //     } catch (\Exception $e) {
    //         Log::error('Database disconnect error: ' . $e->getMessage());
    //     }
    // }


    //     use Illuminate\Http\Request;
    // use Illuminate\Support\Facades\Auth;
    // use Illuminate\Support\Facades\DB;
    // use Illuminate\Support\Facades\Log;

   public function logoutlogin(Request $request)
{
    try {
        $user = $request->user();

        // if (!$user) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Unauthenticated.'
        //     ], 400);
        // }

        // Revoke all tokens (for Passport/Sanctum)
        if (method_exists($user, 'tokens')) {
            $user->tokens()->delete(); // Sanctum style
            // OR $user->tokens->each->delete(); // Passport style
        }

        $this->closeDatabaseConnections();

        return response()->json([
            'success' => true,
            'message' => 'Successfully logged out.'
        ]);
    } catch (\Exception $e) {
        Log::error('Logout error: ' . $e->getMessage());
        $this->closeDatabaseConnections();
        return response()->json([
            'success' => false,
            'message' => 'Logout failed.',
            'error' => $e->getMessage()
        ], 500);
    }
}

    protected function closeDatabaseConnections()
    {
        try {
            // Disconnect default connection
            DB::disconnect();

            // Disconnect custom HRMS connection if it exists
            if (DB::getConfig('mysql_medics_hrms')) {
                DB::connection('mysql_medics_hrms')->disconnect();
            }

            // Add more connections if needed
            // DB::connection('another_connection')->disconnect();
        } catch (\Exception $e) {
            Log::error('Database disconnect error: ' . $e->getMessage());
        }
    }


    public function autoLogin(Request $request)
    {
        $role = $request->query('role');
        $id = $request->query('id');

        $userhrms = DB::connection('mysql_medics_hrms')
            ->table('employee_details')
            ->select('id', 'employee_name', 'employee_type', 'position', 'department', 'date_of_joining', 'phone_number', 'email_address', 'profile_image')
            ->where('status', '1')
            ->where('id', $id)
            ->first();

        if (!$userhrms) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        $userposition = $userhrms->position;
        $userEmpType = $userhrms->employee_type;

        // Fetch permissions based on the user's position

        $roleId = [];
        $roleName = [];
        if ($userposition !== 'Admin') {
            $positions = explode(',', $userposition);

            $roles = DB::connection('mysql_medics_hrms')
                ->table('roles')
                ->whereIn('id', $positions)
                ->get();

            // Loop through the roles and get the role ID and name
            if ($roles->isNotEmpty()) {
                // Loop to gather all the roles for the specified positions
                foreach ($roles as $role) {
                    // Save role data
                    $roleId[] = $role->id; // Add to array of IDs
                    $roleName[] = $role->name; // Add to array of role names
                }
            } else {
                $roleId = '';
                $roleName = '';
            }
        } else {
            $roleId = ['Admin']; // For Admin, assign an empty string or desired default value
            $roleName = ['Admin'];
        }


        $userpermission = DB::connection('mysql_medics_hrms')
            ->table('role_permissions')
            ->whereIn('role_id', $roleId)
            ->where('status', 1)
            ->pluck('permission')
            ->toArray();


        // You can return the HRMS user directly since you’re not using Laravel’s auth
        return response()->json([
            'user' => $userhrms,
            'role' => $role,
            'permission' => $userpermission,
            'rolename' => $roleId,
            'employee_type' => $userEmpType,
            'roleName' => $roleName,
        ]);
    }
}
