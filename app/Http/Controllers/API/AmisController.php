<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\AmisEnrolledStudentsRequest;
use App\Http\Resources\AmisEnrolledStudentCollection;
use App\Services\Api\AmisService;

class AmisController extends Controller
{
    public function __construct(private readonly AmisService $amisService)
    {
    }

    public function index(AmisEnrolledStudentsRequest $request): AmisEnrolledStudentCollection
    {
        $students = $this->amisService->paginateEnrolledStudents(
            page: $request->integer('page', 1),
            perPage: $request->integer('per_page', 15),
        );

        return new AmisEnrolledStudentCollection($students->withQueryString());
    }
}
