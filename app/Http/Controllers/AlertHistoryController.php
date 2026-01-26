<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InventoryAlert;

class AlertHistoryController extends Controller
{
    public function index(Request $request)
    {
        $alerts = InventoryAlert::with('product')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('alerts.history', compact('alerts'));
    }
}
