<?php

namespace App\Http\Controllers;

use App\Services\AcknowledgementService;
use App\Models\AcknowledgementReceipt;
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

    public function uploadFile(Request $request)
    {
        $request->validate([
            'file'                        => 'required|file|mimes:pdf,jpg,jpeg,png|max:5048',
            'acknowledgement_item_ids'    => 'required|array|min:1',
            'acknowledgement_item_ids.*'  => 'exists:acknowledgement_items,id',
        ]);

        $this->service->uploadFile(
            $request->acknowledgement_item_ids,
            $request->file('file'),
            auth()->user()->userProfiles->id
        );

        return back()->with('success', 'File uploaded successfully.');
    }
}
