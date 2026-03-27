<?php

namespace App\Http\Controllers;

use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SettingsController extends Controller
{
    public function __construct(protected SettingsService $settings) {}
    /**
     * Display settings page
     */
    public function index()
    {
        $this->authorize('settings-view');

        return view('pages.settings');
    }

    /**
     * Get site configuration
     */
    public function getSiteConfig()
    {
        $this->authorize('settings-view');

        $config = [
            'app_name'        => $this->settings->get('app_name', config('app.name')),
            'app_logo'        => $this->settings->get('app_logo'),
            'timezone'        => $this->settings->get('timezone', config('app.timezone')),
            'app_description' => $this->settings->get('app_description'),
            'contact_email'   => $this->settings->get('contact_email'),
            'contact_phone'   => $this->settings->get('contact_phone'),
        ];

        return response()->json(['success' => true, 'data' => $config]);
    }

    /**
     * Update site configuration
     */
    public function updateSiteConfig(Request $request)
    {
        $this->authorize('settings-edit');

        $validator = Validator::make($request->all(), [
            'app_name' => 'nullable|string|max:255',
            'app_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'timezone' => 'nullable|string',
            'app_description' => 'nullable|string',
            'contact_email' => 'nullable|email',
            'contact_phone' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $changes = [];

        // Handle logo upload
        if ($request->hasFile('app_logo')) {
            $logo = $request->file('app_logo');

            // Create directory if not exists
            $uploadPath = public_path('uploads/site');
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            // Generate unique filename
            $filename = time() . '_' . uniqid() . '.' . $logo->getClientOriginalExtension();

            // Move file to public/uploads/site
            $logo->move($uploadPath, $filename);

            // Delete old logo if exists
            $oldLogo = $this->settings->get('app_logo');
            if ($oldLogo && file_exists(public_path($oldLogo))) {
                unlink(public_path($oldLogo));
            }

            // Store relative path
            $logoPath = 'uploads/site/' . $filename;
            $this->settings->set('app_logo', $logoPath, 'string', 'site', 'Application logo');
            $changes['app_logo'] = $filename;
        }

        // Update other settings
        if ($request->filled('app_name')) {
            $oldValue = $this->settings->get('app_name');
            $this->settings->set('app_name', $request->app_name, 'string', 'site', 'Application name');
            $changes['app_name'] = ['old' => $oldValue, 'new' => $request->app_name];
        }

        if ($request->filled('timezone')) {
            $oldValue = $this->settings->get('timezone');
            $this->settings->set('timezone', $request->timezone, 'string', 'site', 'Application timezone');
            $changes['timezone'] = ['old' => $oldValue, 'new' => $request->timezone];
        }

        if ($request->filled('app_description')) {
            $this->settings->set('app_description', $request->app_description, 'string', 'site', 'Application description');
            $changes['app_description'] = 'updated';
        }

        if ($request->filled('contact_email')) {
            $oldValue = $this->settings->get('contact_email');
            $this->settings->set('contact_email', $request->contact_email, 'string', 'site', 'Contact email');
            $changes['contact_email'] = ['old' => $oldValue, 'new' => $request->contact_email];
        }

        if ($request->filled('contact_phone')) {
            $oldValue = $this->settings->get('contact_phone');
            $this->settings->set('contact_phone', $request->contact_phone, 'string', 'site', 'Contact phone');
            $changes['contact_phone'] = ['old' => $oldValue, 'new' => $request->contact_phone];
        }

        return response()->json([
            'success' => true,
            'message' => 'Site configuration updated successfully'
        ]);
    }

    /**
     * Update a single setting
     */
    public function update(Request $request, $key)
    {
        $this->authorize('settings-edit');

        $validator = Validator::make($request->all(), [
            'value' => 'required',
            'type' => 'nullable|in:string,boolean,integer,json',
            'description' => 'nullable|string',
            'group' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $setting = $this->settings->set(
            $key,
            $request->value,
            $request->type ?? 'string',
            $request->group ?? 'general',
            $request->description
        );

        return response()->json([
            'success' => true,
            'data' => $setting,
            'message' => 'Setting updated successfully'
        ]);
    }

    /**
     * Bulk update settings
     */
    public function bulkUpdate(Request $request)
    {
        $this->authorize('settings-edit');

        $validator = Validator::make($request->all(), [
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'required',
            'settings.*.type' => 'nullable|in:string,boolean,integer,json',
            'settings.*.group' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        foreach ($request->settings as $settingData) {
            $this->settings->set(
                $settingData['key'],
                $settingData['value'],
                $settingData['type'] ?? 'string',
                $settingData['group'] ?? 'general',
                $settingData['description'] ?? null
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Settings updated successfully'
        ]);
    }
}
