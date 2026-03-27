<?php

namespace App\Http\Controllers;

use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $this->authorize('role-list');

        setUnsetUniqueId();

        $query = Role::orderBy('name', 'asc');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $roles = $query->paginate(20)->withQueryString();
        $permissions = Permission::select('id', 'name')->get();

        return view('pages.roles.index', compact('roles', 'permissions'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->authorize('role-create');

        $validator = Validator::make(
            $request->all(),
            [
                'name'          => 'required|unique:roles,name',
                'permissions'   => 'required|array',
            ],
            [
                'name.required'         => 'Please enter Role Name!',
                'name.unique'           => 'Role Name has been taken!',
                'permissions.required'  => 'Please select at least one permission!',
            ]
        );

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            // session check
            if (!setUnsetUniqueId('get')) {
                throw new \Exception('Unauthorized operation! Please try again!');
            }

            DB::beginTransaction();

            $role = Role::create(['name' => $request->input('name')]);
            $role->syncPermissions($request->input('permissions'));

            DB::commit();

            flash()->addSuccess('Role Added');
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollBack();
            // report($e);

            setUnsetUniqueId();

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Role  $role
     * @return \Illuminate\Http\Response
     */
    public function show(Role $role)
    {
        $this->authorize('role-list');

        setUnsetUniqueId();

        $permissions = Permission::select('id', 'name')->get();

        return view('pages.roles.show', compact('role', 'permissions'));
    }

    /**
     * Get role data as JSON for editing
     *
     * @param  \App\Models\Role  $role
     * @return \Illuminate\Http\Response
     */
    public function getJson(Role $role)
    {
        $this->authorize('role-list');

        return response()->json([
            'success' => true,
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
            ],
            'permissions' => $role->permissions->pluck('name')->toArray(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Role  $role
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Role $role)
    {
        $this->authorize('role-edit');

        $validator = Validator::make(
            $request->all(),
            [
                'name'          => 'required|unique:roles,name,' . $role->id,
                'permissions'   => 'required|array',
            ],
            [
                'name.required'         => 'Please enter Role Name!',
                'name.unique'           => 'Role Name has been taken!',
                'permissions.required'  => 'Please select at least one permission!',
            ]
        );

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            // session check
            if (!setUnsetUniqueId('get')) {
                throw new \Exception('Unauthorized operation! Please try again!');
            }

            DB::beginTransaction();

            $role->name = $request->input('name');
            $role->save();
            $role->syncPermissions($request->input('permissions'));

            DB::commit();

            flash()->addSuccess('Role Updated');
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollBack();
            // report($e);

            setUnsetUniqueId();

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Role  $role
     * @return \Illuminate\Http\Response
     */
    public function destroy(Role $role)
    {
        $this->authorize('role-delete');

        if ($role->name != 'Super Admin') {
            $role->delete();
            flash()->addSuccess('Role Deleted');
        }
        return redirect(route('roles.index'));
    }
}
