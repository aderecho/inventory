<?php

namespace App\Http\Controllers;

use App\Services\AcknowledgementService;
use App\Http\Requests\AcknowledgementUploadFileRequest;

use Illuminate\Http\Request;
use Inertia\Inertia;

class AcknowledgementController extends Controller
{
    public function __construct(
        protected AcknowledgementService $service
    ) {}

    public function index(Request $request)
    {
        $search = $request->input('search');

        $receipts = $this->service->getPaginatedReceipts($search);

        return Inertia::render('Acknowledgement/Index', [
            'receipts' => $receipts,
        ]);
    }

    public function show(int $id)
    {
        $data = $this->service->getReceiptWithGroupedPersons($id);

        return Inertia::render('Acknowledgement/Show', [
            'receipt'         => $data['receipt'],
            'groupedByPerson' => $data['groupedByPerson'],
        ]);
    }


    public function uploadFile(AcknowledgementUploadFileRequest $request)
    {
        try {
            $this->service->uploadFile(
                $request->acknowledgement_item_ids,
                $request->file('file'),
                auth()->user()->userProfiles->id
            );
        } catch (\Exception $e) {
            return back()->withErrors([
                'file' => $e->getMessage(),
            ]);
        }

        return back()->with(
            'success',
            'File uploaded successfully.'
        );
    }
}
