<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\QRRefreshOption\StoreQRRefreshOptionRequest;
use App\Http\Requests\V1\QRRefreshOption\UpdateQRRefreshOptionRequest;
use App\Models\QrRefreshOption;
use Illuminate\Http\Request;

class QRRefreshOptionsController extends Controller {
    public function index() {
        return response()->json(QrRefreshOption::all());
    }
    public function store(StoreQRRefreshOptionRequest $request) {
        $option = QrRefreshOption::create($request->validated());
        return response()->json($option, 201);
    }
    public function show(QrRefreshOption $qrRefreshOption) {
        return response()->json($qrRefreshOption);
    }
    public function update(UpdateQRRefreshOptionRequest $request, QrRefreshOption $qrRefreshOption) {
        $qrRefreshOption->update($request->validated());
        return response()->json($qrRefreshOption);
    }
    public function destroy(QrRefreshOption $qrRefreshOption) {
        $qrRefreshOption->delete();
        return response()->json(['message' => 'QR refresh option deleted']);
    }
}