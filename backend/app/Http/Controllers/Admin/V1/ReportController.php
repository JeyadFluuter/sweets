<?php

namespace App\Http\Controllers\Admin\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\V1\Report\SummaryRequest;
use App\Http\Responses\ApiResponse;
use App\Services\Finance\ReportService;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    public function __construct(private readonly ReportService $reportService) {}

    public function dashboard(): JsonResponse
    {
        $this->authorize('reports.view');

        return ApiResponse::success($this->reportService->dashboard());
    }

    public function summary(SummaryRequest $request): JsonResponse
    {
        $data = $request->validated();

        return ApiResponse::success($this->reportService->summary($data['from'], $data['to']));
    }
}
