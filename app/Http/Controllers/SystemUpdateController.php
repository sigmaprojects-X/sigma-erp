<?php

namespace App\Http\Controllers;

use App\Services\SystemUpdateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SystemUpdateController extends Controller
{
    public function __construct(
        protected SystemUpdateService $updateService
    ) {}

    /**
     * Tampilkan halaman pusat pembaruan & informasi sistem.
     */
    public function index(): View
    {
        $info = $this->updateService->getSystemInfo();
        $changelogs = $this->updateService->getChangelog();

        return view('settings.update', compact('info', 'changelogs'));
    }

    /**
     * Endpoint API untuk mengecek pembaruan via AJAX.
     */
    public function check(): JsonResponse
    {
        $info = $this->updateService->getSystemInfo();

        return response()->json([
            'success' => true,
            'current_version' => $info['current_version'],
            'latest_version' => $info['latest_version'],
            'update_available' => $info['update_available'],
        ]);
    }

    /**
     * Jalankan proses update 1-klik.
     */
    public function applyUpdate(Request $request)
    {
        $result = $this->updateService->performUpdate();

        if ($request->wantsJson()) {
            return response()->json($result);
        }

        if ($result['success']) {
            return redirect()->route('settings.update.index')->with('success', $result['message']);
        }

        return redirect()->route('settings.update.index')->with('error', $result['message']);
    }
}
