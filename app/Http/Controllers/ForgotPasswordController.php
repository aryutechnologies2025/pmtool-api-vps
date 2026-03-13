<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use App\Models\PasswordResets;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;



class ForgotPasswordController extends Controller
{
    public function submitForgetPasswordForm(Request $request)
    {
        $usercheck = User::where('email_address', $request->email_address)->first();

        // If the email doesn't exist in the table, return an error response
        if (empty($usercheck)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email not found in our records.',
            ], 404); // You can return 404 if email not found
        }

        // Generate reset token
        $token = Str::random(64);

        Log::info('token generate', ['token' => $token]);

        $resertpassword = new PasswordResets();

        $resertpassword->email = $request->email_address;
        $resertpassword->token = $token;

        $resertpassword->save();

        // Optionally send the email for password reset
        Mail::send('emails.forgetPassword', ['token' => $token], function ($message) use ($request) {
            $message->to($request->email_address);
            $message->subject('Reset Password');
        });

        return response()->json([
            'status' => 'success',
            'message' => 'We have e-mailed your password reset link!',
        ], 200);
    }

    public function submitResetPasswordForm(Request $request)
    {
        $updatePassword = PasswordResets::where([
            'email' => $request->email_address,
            'token' => $request->token
        ])->first();
        
        if (!$updatePassword) {
            return response()->json(['error' => 'Invalid token.'], 400);
        }
        
        $user = User::where('email_address', $request->email_address)
            ->update(['password' => Hash::make($request->password)]);
        PasswordResets::where(['email' => $request->email_address])->delete();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Your password has been changed!',
        ], 200);
    }
}
