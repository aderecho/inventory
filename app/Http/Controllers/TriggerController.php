<?php

namespace App\Http\Controllers;

use App\Services\TriggerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class TriggerController extends Controller
{
    public function __construct(
        protected TriggerService $triggerService
    ) {}

    public function store(Request $request): JsonResponse
    {
        try {
            $requestData = $request->all();

            $trigger = $this->triggerService->sync();

            return response()->json([
                'success' => true,
                'message' => 'Employee synchronization completed successfully.',
                'data' => $trigger,
                'request_data' => $requestData,
            ], 201);

        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Employee synchronization failed.',
                'status' => 0,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}