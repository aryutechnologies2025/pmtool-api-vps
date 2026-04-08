<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;


class ProfileController extends Controller
{
    public function showProfile()
    {

        // Log function call
        Log::info('showProfile function called');

        // Get the logged-in user
        $user = auth()->user();
        if (!$user) {
            Log::warning('User not authenticated');
            return response()->json(['message' => 'User not authenticated'], 401);
        }

        // Fetch the profile associated with the user's email
        $profile = $user->profile;
        if (!$profile) {
            Log::warning('Profile not found for user', ['email' => $user->email]);
            return response()->json(['message' => 'Profile not found'], 404);
        }

        // Return the profile data
        return response()->json($profile, 200);
    }



    public function store(Request $request)
    {

        $validated = $request->validate([
            'full_name' => 'required',
            'email' => 'nullable',
            'password' => 'nullable',
            'dob' => 'required|date',
            'permanent_address' => 'required',
            'present_address' => 'required',
            'city' => 'required',
            'post_code' => 'required',
            'country' => 'required',
            'profile_image' => 'required|image|mimes:jpg,jpeg,png,gif|max:2048'
        ]);

        $profile = new Profile($validated);


        if ($request->hasFile('profile_image')) {
            $profile->profile_image = $request->file('profile_image')->store('uploads', 'public');
        }

        $profile->save();

        return response()->json(['message' => 'Profile created successfully', 'profile' => $profile], 201);
    }

    public function update(Request $request, string $id)
    {
        $details = profile::find($id);
        $details->full_name = $request->full_name;
        $details->email = $request->email;
        $details->password = $request->password;
        $details->dob = $request->dob;
        $details->permanent_address = $request->permanent_address;
        $details->present_address = $request->present_address;
        $details->city = $request->city;
        $details->post_code = $request->post_code;
        $details->country = $request->country;
        $details->profile_image = $request->profile_image;


        if ($request->hasFile('profile_image')) {
            $details->profile_image = $request->file('profile_image')->store('uploads', 'public');
        }

        $details->save();

        return response()->json(['message' => 'Profile created successfully', 'profile' => $details], 201);
    }

    public function settingUpdate(Request $request)
    {
        Log::info('request :', $request->all());
        try {
            // Fetch existing settings
            $settings = Setting::first(); // Assuming you have a `settings` table
        
            if (!$settings) {
                $settings = new Setting(); // Create if not exists
            }
        
            // Assign values
            $settings->site_title = $request->site_title;
            $settings->site_description = $request->site_description;
            $settings->admin_email = $request->admin_email;
            $settings->cc_mail = $request->cc_mail;
            $settings->contact_no = $request->contact_no;
            $settings->project_target = $request->project_target;

            //invoice
            $settings->company_name = $request->company_name;
            $settings->name = $request->name;
            $settings->address = $request->address;
            $settings->email = $request->email;
            $settings->phone_number = $request->phone_number;

            if($request->hasFile('sign_image')){
                $randomNumber = rand(1000, 9999); 
                $invoiceLogoName = time() . '_' . $randomNumber . '_' . $request->file('sign_image')->getClientOriginalName();
                $settings->sign_image = $invoiceLogoName;
                $path = public_path('signatures');
                $request->file('sign_image')->move($path, $invoiceLogoName);
            }



            
            
            //bank information

            $settings->bank_name = $request->bank_name;
            $settings->account_number = $request->account_number;
            $settings->ifsc = $request->ifsc;
            $settings->branch_name = $request->branch_name;
            $settings->account_holder_name = $request->account_holder_name;
        
            // For fav_icon
            if ($request->hasFile('fav_icon')) {
                $randomNumber = rand(1000, 9999); 
                $favIconName = time() . '_' . $randomNumber . '_' . $request->file('fav_icon')->getClientOriginalName();
                $settings->fav_icon = $favIconName;
                $path = public_path('uploads/settings');
                $request->file('fav_icon')->move($path, $favIconName);
            }
        
            // For logo_image (Fix here: Assign to 'logo_image' instead of 'fav_icon')
            if ($request->hasFile('logo_image')) {
                $randomNumber = rand(1000, 9999); 
                $logoName = time() . '_' . $randomNumber . '_' . $request->file('logo_image')->getClientOriginalName();
                $settings->logo_image = $logoName; // Correct assignment here
                $path = public_path('uploads/settings');
                $request->file('logo_image')->move($path, $logoName);
            }
        
            $settings->save();
        
            return response()->json(['message' => 'Settings updated successfully!', 'data' => $settings], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error updating settings', 'error' => $e->getMessage()], 500);
        }
        
    }

    public function settinglist(Request $request)
    {
        $type = $request->query('type');
        if ($type === 'login') {
            return response()->json(['data' => Setting::select('site_title','site_description', 'logo_image', 'fav_icon')->first()]);
        } else {
            $settings = Setting::first();
        }

        if ($settings) {
            return response()->json(['data' => $settings]);
        } else {
            return response()->json(['message' => 'Settings not found'], 404);
        }
    }
    
}
