<?php

namespace App\Http\Controllers;

use App\Models\LoginHistory;
use App\Models\User;
use App\Rules\StrongPassword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UserProfileController extends Controller
{
    // show user profile
    public function userProfile()
    {
        $user = Auth::user();
        return view('pages.user-profile.show', compact('user'));
    }

    // change profile picture
    public function changeProfilePicture(Request $request)
    {
        if (! $request->hasFile('profile_picture')) {
            flash()->addError('Please select an image first!');
            return redirect()->route('user-profile.show');
        }

        $user = Auth::user();

        DB::beginTransaction();
        try {
            // reuse HasFiles::saveFiles to validate, save and remove existing files
            $saved = $user->saveFiles(
                $request->file('profile_picture'),
                ['jpg', 'jpeg'],
                1024 * 1024,
                null,
                true // delete existing
            );

            DB::commit();

            if ($saved->isNotEmpty()) {
                flash()->addSuccess('Profile picture updated successfully!');
            } else {
                flash()->addError('No file was uploaded. Ensure the file is a valid image and under 1 MB.');
            }

            return redirect()->route('user-profile.show');
        } catch (\Exception $e) {
            DB::rollBack();
            report($e);

            flash()->addError('Something went wrong while uploading the image.');
            return redirect()->back();
        }
    }

    // edit profile
    public function editUserProfile()
    {
        $user = Auth::user();
        return view('pages.user-profile.edit', compact('user'));
    }

    // update profile
    public function updateUserProfile(Request $request)
    {
        $user = User::findOrFail(Auth::user()->id);

        $request->validate(
            [
                'name' => 'required|max:255',
                'email' => 'required|unique:users,email,' . $user->id . ',id|max:255',
            ],
            [
                'name.required' => 'Name is required!',
                'email.required' => 'Email is required!',
                'email.unique' => 'This email has been taken!',
            ]
        );

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'updated_by' => Auth::user()->id
        ]);

        flash()->addSuccess('User profile updated successfully!');
        return redirect()->route('user-profile.show');
    }

    // change password
    public function changePassword(Request $request)
    {
        return view('pages.user-profile.change-password');
    }

    // update password
    public function updatePassword(Request $request)
    {
        $user = User::findOrFail(Auth::user()->id);
        // validate
        $request->validate(
            [
                'password' => ['required', new StrongPassword(), 'confirmed']
            ],
            [
                'password.required' => 'Password is required',
                'password.confirmed' => 'Password Confirmation does not match!',
            ]
        );

        $user->update([
            'password' => bcrypt($request->password),
            'updated_by' => Auth::user()->id
        ]);

        flash()->addSuccess('Password changed successfully!');
        return redirect()->route('user-profile.change-password');
    }

    // view my login history
    public function myLoginHistory(Request $request)
    {
        $logins = LoginHistory::where('user_id', Auth::id())
            ->orderBy('login_at', 'desc')
            ->paginate(20);

        return view('pages.user-profile.login-history', compact('logins'));
    }
}
