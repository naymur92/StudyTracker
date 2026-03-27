<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->authorize('permission-list');

        setUnsetUniqueId();

        $permissions = Permission::select(['id', 'name', 'created_at'])->get();
        return view('pages.permissions.index', compact('permissions'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->authorize('permission-create');

        $validator = Validator::make(
            $request->all(),
            [
                'name.*' => 'required|unique:permissions,name'
            ],
            [
                'name.*.required' => 'Please enter Permission Name!',
                'name.*.unique' => 'Permission Name has been taken!'
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

            $names = array_unique($request->name);
            // return response()->json($names);
            foreach ($names as $item) {
                Permission::create(['name' => trim($item)]);
            }

            flash()->addSuccess('Permissions Added');
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            // report($e);

            setUnsetUniqueId();

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Permission $permission)
    {
        $this->authorize('permission-delete');

        if ($permission->id <= 8) {
            flash()->addError('This permission cannot be deleted!');
            return back();
        }

        $permission->delete();

        flash()->addSuccess('Permission Deleted');
        return back();
    }
}
