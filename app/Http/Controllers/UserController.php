<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use DB;

class UserController extends Controller
{
    //
    public function index()
    {
        $users = User::orderBy('created_at', 'DESC')->paginate(10);

        return view('users.index', compact('users'));
    }

    public function create()
    {
        $role = Role::orderBy('name', 'ASC')->get();
        return view('users.create', compact('role'));
    }

    public function store(Request $r)
    {
        $this->validate($r, [
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
            'role' => 'required|string|exists:roles,name'
        ]);

        $user = User::firstOrCreate([
           'email' => $r->email,
        ], [
            'name' => $r->name,
            'password' => bcrypt($r->password),
            'status' => true
        ]);
    }

    public function edit($id)
    {
        $user = User::findOrFail($id);
        return view('users.edit', compact('user'));
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'email' => 'required|email|exists:users,email',
            'name' => 'required|string|max:100',
            'password' => 'nullable|min:6'
        ]);

        $user = User::findOrFail($id);
        $password = !empty($request->password) ? bcrypt($request->passwod): $user->password;
        $user->update([
            'name' => $request->name,
            'password' => $password
        ]);

        return redirect(route('users.index'))->with(['success' => 'User: <strong>' . $request->name . '</strong> Berhasil Diupdate']);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return redirect()->back()->with(['success' => 'User: <strong>' . $user->name . '</strong> Berhasil Dihapus']);
    }

    public function rolePermission(Request $request)
    {
        $role = $request->get('role');

        $permissions = null;
        $hasPermission = null;

        $roles = Role::all()->pluck('name');
        
        if(!empty($role)) {
            echo $permissions;
            $getRole = Role::findByName($role);

            $hasPermission = DB::table('role_has_permissions')
                ->select('permissions.name')
                ->join('permissions', 'role_has_permissions.permission_id', '=', 'permissions.id')
                ->where('role_id', $getRole->id)->get()->pluck('name')->all();

            $permissions = Permission::all()->pluck('name');

        }
        
        //dd($roles);
        return view('users.role_permission', compact('roles', 'permissions', 'hasPermission'));
    }

    public function addPermission(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|string|unique:permissions'
        ]);
            
        try {
            $permissions = Permission::firstOrCreate([
                'name' => $request->name
            ]);

            return redirect()->back()->with(['success' => 'Permission <strong>' . $request->name . '</strong> Berhasil Ditambahkan']);
        } catch(\Exception $e) {
            return redirect()->back()->with(['error' => $e->getMessage()]);
        }
        

        
    }

    public function setRolePermission(Request $request, $role)
    {
        $role = Role::findByName($role);

        
        try {
            $role->syncPermissions($request->permission);

            return redirect()->back()->with(['success' => 'Permission to Role Saved']);

        }catch(\Exception $e){
            return redirect()->back()->with(['error' => $e->getMessage()]);
        }
        
    }

    public function roles(Request $request, $id) 
    {
        $user = User::findOrFail($id);
        $roles = Role::all()->pluck('name');
        return view('users.roles', compact('user', 'roles'));
    }

    public function setRole(Request $request, $id) 
    {
        $this->validate($request, [
            'role' => 'required'
        ]);

        $user = User::findOrFail($id);

        $user->syncRoles($request->role);
        return redirect()->back()->with(['success' => 'Role Berhasil Diset']);
    }
}
