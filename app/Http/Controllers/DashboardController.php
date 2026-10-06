<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\DataDeletionRequest;
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

        $pendingDataRequests = null;
        if (auth()->user()->can('data-request-list')) {
            $pending = DataDeletionRequest::status(DataDeletionRequest::STATUS_PENDING);
            $pendingDataRequests = [
                'count' => (clone $pending)->count(),
                'oldest' => $pending->with('user')->oldest('id')->limit(5)->get(),
            ];
        }

        return view('dashboard', compact('stats', 'pendingDataRequests'));
    }
}
