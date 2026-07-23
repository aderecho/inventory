<?php

namespace App\Http\Controllers\API;

use App\Models\InventoryItem;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class QrScanController extends Controller
{
    public function store(Request $request)
    {
        $request->validate(['code' => 'required|string']);

        $item = InventoryItem::where('property_number', $request->code)
            ->orWhere('serial_number', $request->code)
            ->with('supplier', 'itemClassification')
            ->first();

        return response()->json([
            'found' => (bool) $item,
            'item' => $item,
        ]);
    }
}