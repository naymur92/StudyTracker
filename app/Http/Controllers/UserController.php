<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Rules\StrongPassword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('user-list');

        $users = User::get();
        return view('pages.users.index', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('user-create');

        $roles = Role::pluck('name', 'name')->all();
        $userTypes = User::getTypeOptions();
        $isActiveOptions = [
            1 => 'Active',
            0 => 'Inactive',
        ];

        setUnsetUniqueId();
        return view('pages.users.create', compact('roles', 'userTypes', 'isActiveOptions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('user-create');

        $request->validate(
            [
                'name'      => 'required|max:255',
                'email'     => 'required|unique:users,email|max:255',
                'user_type' => 'required',
                'is_active' => 'required',
                'roles'     => 'nullable|array',
                'password'  => ['required', new StrongPassword(), 'confirmed'],
            ],
            [
                'name.required'         => 'Name is required!',
                'email.required'        => 'Email is required!',
                'email.unique'          => 'This email has been taken!',
                'user_type.required'    => 'User type is required!',
                'is_active.required'    => 'Status is required!',
                // 'roles.required'        => 'At least one role is required!',
                'password.required'     => 'Password is required!',
                'password.confirmed'    => 'Password Confirmation does not match!',
            ]
        );

        try {
            // session check
            if (!setUnsetUniqueId('get')) {
                throw new \Exception('Unauthorized operation! Please try again!');
            }

            DB::beginTransaction();

            $user = User::create([
                'name'      => $request->name,
                'email'     => $request->email,
                'type'      => $request->user_type,
                'is_active' => $request->is_active,
                'password'  => bcrypt($request->password),
                'created_by' => Auth::user()->id,
            ]);

            // assign roles
            if (!empty($request->input('roles'))) {
                $user->assignRole($request->input('roles'));
            }

            DB::commit();

            flash()->addSuccess('Authentication User Created');
            return redirect()->route('users.index');
        } catch (\Exception $e) {
            DB::rollBack();
            // report($e);

            setUnsetUniqueId();

            flash()->addError($e->getMessage());
            return redirect()->back();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        $this->authorize('user-list');

        return view('pages.users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        $this->authorize('user-edit');

        setUnsetUniqueId();

        $roles = Role::pluck('name', 'name')->all();
        $userRoles = $user->roles->pluck('name')->all();
        $userTypes = User::getTypeOptions();
        $isActiveOptions = [
            1 => 'Active',
            0 => 'Inactive',
        ];

        return view('pages.users.edit', compact('user', 'roles', 'userRoles', 'userTypes', 'isActiveOptions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $this->authorize('user-edit');

        $request->validate(
            [
                'name'      => 'required|max:255',
                'email'     => 'required|unique:users,email,' . $user->id . ',id|max:255',
                'user_type' => 'required',
                'is_active' => 'required',
                'roles'     => 'nullable|array',
            ],
            [
                'name.required'         => 'Name is required!',
                'email.required'        => 'Email is required!',
                'email.unique'          => 'This email has been taken!',
                'user_type.required'    => 'User type is required!',
                'is_active.required'    => 'Status is required!',
                // 'roles.required'        => 'At least one role is required!',
            ]
        );

        try {
            // session check
            if (!setUnsetUniqueId('get')) {
                throw new \Exception('Unauthorized operation! Please try again!');
            }

            DB::beginTransaction();

            $user->update([
                'name'          => $request->name,
                'email'         => $request->email,
                'type'          => $request->user_type,
                'is_active'     => $request->is_active,
                'updated_by'    => Auth::user()->id,
            ]);

            // sync roles
            DB::table('model_has_roles')->where('model_id', $user->id)->delete();
            if (!empty($request->input('roles'))) {
                $user->assignRole($request->input('roles'));
            }

            DB::commit();

            flash()->addSuccess("Authentication User updated successfully!");
            return redirect()->route('users.index');
        } catch (\Exception $e) {
            DB::rollBack();
            // report($e);

            setUnsetUniqueId();

            flash()->addError($e->getMessage());
            return redirect()->back();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    // public function destroy(User $user)
    // {
    //     //
    // }

    // change status
    public function changeStatus(Request $request, User $user)
    {
        $this->authorize('user-edit');

        $user->update(['is_active' => $request->is_active, 'updated_by' => Auth::user()->id]);

        flash()->addSuccess("Status changed successfully!");
        return redirect()->back();
    }


    // change password
    public function changePassword(User $user, Request $request)
    {
        $this->authorize('user-edit');

        if ($user->id === Auth::user()->id || $user->id == 1) {
            flash()->addError('Unauthorized operation! You can not change this user password.');
            return redirect()->back();
        }
        return view('pages.users.change-password', compact('user'));
    }

    // update password
    public function updatePassword(User $user, Request $request)
    {
        $this->authorize('user-edit');

        if ($user->id === Auth::user()->id || $user->id == 1) {
            flash()->addError('Unauthorized operation! You can not change this user password.');
            return redirect()->back();
        }

        // validate
        $request->validate(
            [
                'password' => ['required', new StrongPassword(), 'confirmed']
            ],
            [
                'password.required'     => 'Password is required',
                'password.confirmed'    => 'Password Confirmation does not match!',
            ]
        );

        $user->update([
            'password'      => bcrypt($request->password),
            'updated_by'    => Auth::user()->id
        ]);

        flash()->addSuccess('Password changed successfully!');
        return redirect()->route('users.show', $user->id);
    }
}
