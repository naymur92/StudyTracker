@php
    $appLogo = \App\Models\Setting::get('app_logo');
    $appName = \App\Models\Setting::get('app_name', config('app.name', 'Laravel'));
@endphp

<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

    <!-- Sidebar - Brand -->
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="{{ route('dashboard') }}">
        @if ($appLogo)
            <div class="sidebar-brand-icon">
                <img src="{{ asset($appLogo) }}" alt="Logo" style="max-height: 50px; max-width: 50px; object-fit: contain;">
            </div>
        @else
            <div class="sidebar-brand-icon rotate-n-15">
                <i class="fas fa-laugh-wink"></i>
            </div>
        @endif
        <div class="sidebar-brand-text mx-3">{{ $appName }}</div>
    </a>

    <!-- Divider -->
    <hr class="sidebar-divider my-0">

    <li class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
        <a class="nav-link" href="{{ route('dashboard') }}">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>Dashboard</span></a>
    </li>


    <!-- Divider -->
    {{-- <hr class="sidebar-divider"> --}}
    <!-- Heading -->
    {{-- <div class="sidebar-heading mb-2">
        Product Management
    </div> --}}

    <!-- Divider -->
    <hr class="sidebar-divider">
    <!-- Heading -->
    <div class="sidebar-heading mb-2">
        Configuration
    </div>

    {{-- Nav Item - User & Role Management Menu --}}
    @canany(['user-list', 'permission-list', 'role-list'])
        @php
            $isActiveUsrRlMngMenu = request()->routeIs('users.*', 'permissions.*', 'roles.*');
        @endphp
        <li class="nav-item {{ $isActiveUsrRlMngMenu ? 'active' : '' }}">
            <a class="nav-link {{ $isActiveUsrRlMngMenu ? '' : 'collapsed' }}" href="#" data-toggle="collapse" data-target="#usrRlMngMenu"
                aria-expanded="{{ $isActiveUsrRlMngMenu ? 'true' : 'false' }}" aria-controls="usrRlMngMenu">
                <i class="fas fa-users-cog"></i>
                <span>Users &amp; Roles</span>
            </a>
            <div id="usrRlMngMenu" class="collapse {{ $isActiveUsrRlMngMenu ? 'show' : '' }}" aria-labelledby="headingTwo" data-parent="#accordionSidebar">
                <div class="bg-white py-2 collapse-inner rounded">
                    {{-- <h6 class="collapse-header">:</h6> --}}

                    @can('user-list')
                        <a class="collapse-item {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}">
                            <i class="fa-solid fa-users mr-2"></i>Users
                        </a>
                    @endcan

                    @can('permission-list')
                        <a class="collapse-item {{ request()->routeIs('permissions.*') ? 'active' : '' }}" href="{{ route('permissions.index') }}">
                            <i class="fas fa-shield-alt mr-2"></i>Permissions
                        </a>
                    @endcan

                    @can('role-list')
                        <a class="collapse-item {{ request()->routeIs('roles.*') ? 'active' : '' }}" href="{{ route('roles.index') }}">
                            <i class="fas fa-user-shield mr-2"></i>Roles
                        </a>
                    @endcan

                </div>
            </div>
        </li>
    @endcan

    @can('oauth-client-list')
        <li class="nav-item {{ request()->routeIs('oauth-clients.*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('oauth-clients.index') }}">
                <i class="fas fa-key"></i>
                <span>OAuth Clients</span></a>
        </li>
    @endcan

    {{-- Nav Item - Logs & Audit Menu --}}
    @canany(['activity-log-list', 'login-history-list', 'system-log-list'])
        <!-- Divider -->
        <hr class="sidebar-divider">
        <!-- Heading -->
        <div class="sidebar-heading mb-2">
            Activity & Audit
        </div>

        @php
            $isActiveLogsMenu = request()->routeIs('activity-logs.*', 'login-history.*', 'system-logs.*');
        @endphp
        <li class="nav-item {{ $isActiveLogsMenu ? 'active' : '' }}">
            <a class="nav-link {{ $isActiveLogsMenu ? '' : 'collapsed' }}" href="#" data-toggle="collapse" data-target="#logsMenu"
                aria-expanded="{{ $isActiveLogsMenu ? 'true' : 'false' }}" aria-controls="logsMenu">
                <i class="fas fa-history"></i>
                <span>Logs &amp; Audit</span>
            </a>
            <div id="logsMenu" class="collapse {{ $isActiveLogsMenu ? 'show' : '' }}" aria-labelledby="headingLogs" data-parent="#accordionSidebar">
                <div class="bg-white py-2 collapse-inner rounded">
                    @can('activity-log-list')
                        <a class="collapse-item {{ request()->routeIs('activity-logs.*') ? 'active' : '' }}" href="{{ route('activity-logs.index') }}">
                            <i class="fas fa-clipboard-list mr-2"></i>Activity Logs
                        </a>
                    @endcan

                    @can('login-history-list')
                        <a class="collapse-item {{ request()->routeIs('login-history.*') ? 'active' : '' }}" href="{{ route('login-history.index') }}">
                            <i class="fas fa-sign-in-alt mr-2"></i>Login History
                        </a>
                    @endcan

                    @can('system-log-list')
                        <a class="collapse-item {{ request()->routeIs('system-logs.*') ? 'active' : '' }}" href="{{ route('system-logs.index') }}">
                            <i class="fas fa-file-alt mr-2"></i>System Logs
                        </a>
                    @endcan
                </div>
            </div>
        </li>
    @endcanany


    <!-- Divider -->
    <hr class="sidebar-divider">
    <!-- Heading -->
    <div class="sidebar-heading mb-2">
        Study Tracker
    </div>

    @php
        $isActiveSTOverview = request()->routeIs('study-tracker.overview');
        $isActiveSTReports = request()->routeIs('study-tracker.reports');
        $isActiveSTUser = request()->routeIs('study-tracker.user-report');
        $isActiveSTMenu = $isActiveSTOverview || $isActiveSTReports || $isActiveSTUser;
    @endphp

    @php
        $isActiveSTOverview = request()->routeIs('study-tracker.overview');
        $isActiveSTReports = request()->routeIs('study-tracker.reports');
        $isActiveSTUser = request()->routeIs('study-tracker.user-report');
        $isActiveSTUsers = request()->routeIs('study-tracker.users-report');
        $isActiveSTTopics = request()->routeIs('study-tracker.topics-report');
        $isActiveSTCategories = request()->routeIs('study-tracker.categories-report');
        $isActiveSTTasks = request()->routeIs('study-tracker.tasks-report');
        $isActiveSTMenu = $isActiveSTOverview || $isActiveSTReports || $isActiveSTUser || $isActiveSTUsers || $isActiveSTTopics || $isActiveSTCategories || $isActiveSTTasks;
    @endphp

    <li class="nav-item {{ $isActiveSTMenu ? 'active' : '' }}">
        <a class="nav-link {{ $isActiveSTMenu ? '' : 'collapsed' }}" href="#" data-toggle="collapse" data-target="#stMenu"
            aria-expanded="{{ $isActiveSTMenu ? 'true' : 'false' }}" aria-controls="stMenu">
            <i class="fas fa-fw fa-brain"></i>
            <span>Study Tracker</span>
        </a>
        <div id="stMenu" class="collapse {{ $isActiveSTMenu ? 'show' : '' }}" aria-labelledby="headingST" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <a class="collapse-item {{ $isActiveSTOverview ? 'active' : '' }}" href="{{ route('study-tracker.overview') }}">
                    <i class="fas fa-chart-pie mr-2"></i>Overview
                </a>
                <h6 class="collapse-header px-3 mt-2 mb-1">Reports</h6>
                <a class="collapse-item {{ $isActiveSTReports ? 'active' : '' }}" href="{{ route('study-tracker.reports') }}">
                    <i class="fas fa-chart-line mr-2"></i>Trends &amp; Stats
                </a>
                <a class="collapse-item {{ $isActiveSTUsers ? 'active' : '' }}" href="{{ route('study-tracker.users-report') }}">
                    <i class="fas fa-users mr-2"></i>Users Report
                </a>
                <a class="collapse-item {{ $isActiveSTTopics ? 'active' : '' }}" href="{{ route('study-tracker.topics-report') }}">
                    <i class="fas fa-book mr-2"></i>Topics Report
                </a>
                <a class="collapse-item {{ $isActiveSTCategories ? 'active' : '' }}" href="{{ route('study-tracker.categories-report') }}">
                    <i class="fas fa-folder mr-2"></i>Categories Report
                </a>
                <a class="collapse-item {{ $isActiveSTTasks ? 'active' : '' }}" href="{{ route('study-tracker.tasks-report') }}">
                    <i class="fas fa-tasks mr-2"></i>Tasks Report
                </a>
            </div>
        </div>
    </li>

    <!-- Settings & Configuration -->
    @can('settings-view')
        <hr class="sidebar-divider">
        <div class="sidebar-heading mb-2">
            System
        </div>

        <li class="nav-item {{ request()->routeIs('settings') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('settings') }}">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </a>
        </li>
    @endcan


    <!-- Divider -->
    <hr class="sidebar-divider d-none d-md-block">

    <!-- Sidebar Toggler (Sidebar) -->
    <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
    </div>

</ul>
