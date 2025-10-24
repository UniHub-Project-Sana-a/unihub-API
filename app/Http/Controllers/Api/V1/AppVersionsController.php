<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\AppVersion\StoreAppVersionRequest;
use App\Http\Requests\V1\AppVersion\UpdateAppVersionRequest;
use App\Models\AppVersion;
use Illuminate\Http\Request;

class AppVersionsController extends Controller {
    // CRUD لإدارة الإصدارات من لوحة التحكم
    public function index() {
        return response()->json(AppVersion::orderByDesc('release_date')->get());
    }
    public function store(StoreAppVersionRequest $request) {
        $version = AppVersion::create($request->validated());
        return response()->json($version, 201);
    }
    public function show(AppVersion $appVersion) {
        return response()->json($appVersion);
    }
    public function update(UpdateAppVersionRequest $request, AppVersion $appVersion) {
        $appVersion->update($request->validated());
        return response()->json($appVersion);
    }
    public function destroy(AppVersion $appVersion) {
        $appVersion->delete();
        return response()->json(['message' => 'App version deleted']);
    }

    /**
     * Endpoint عام للتطبيقات لفحص آخر إصدار.
     */
    public function latest(Request $request) {
        $request->validate([
            'platform' => ['required', 'string'],
            'package_name' => ['required', 'string'],
        ]);

        $latestVersion = AppVersion::where('platform', $request->platform)
            ->where('package_name', $request->package_name)
            ->orderByDesc('release_date')
            ->orderByDesc('version_id') // لضمان أحدث إدخال في نفس اليوم
            ->first();

        if (!$latestVersion) {
            return response()->json(['message' => 'No version found for this platform.'], 404);
        }

        return response()->json($latestVersion);
    }
}