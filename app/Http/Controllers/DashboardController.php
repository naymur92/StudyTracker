<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\LoginHistory;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'users'    => User::count(),
            'roles'    => Role::count(),
            'activity' => ActivityLog::count(),
            'logins'   => LoginHistory::where('is_successful', 1)->whereDate('login_at', today())->count(),
        ];

        return view('dashboard', compact('stats'));
    }
}
