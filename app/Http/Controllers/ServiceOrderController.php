<?php

namespace App\Http\Controllers;

use App\Models\ServiceOrder;
use App\Services\OrderWorkflow;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ServiceOrderController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate(['status' => 'nullable|in:received,quoted,approved,in_progress,completed,cancelled']);
        $query = ServiceOrder::query()->with('customer')->latest();
        if ($request->user()->role !== 'staff') {
            $query->where('user_id', $request->user()->id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return view('orders.index', ['orders' => $query->paginate(10)->withQueryString()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['equipment' => 'required|string|max:120', 'serial_number' => 'nullable|string|max:100', 'description' => 'required|string|max:4000']);
        $order = DB::transaction(function () use ($data, $request) {
            $order = new ServiceOrder($data);
            $order->user_id = $request->user()->id;
            $order->save();
            $order->events()->create(['user_id' => $request->user()->id, 'action' => 'created']);

            return $order;
        });

        return redirect()->route('orders.show', $order)->with('success', 'Ordem de serviço aberta.');
    }

    public function show(ServiceOrder $order): View
    {
        Gate::authorize('view', $order);
        $order->load('customer', 'events.actor');

        return view('orders.show', compact('order'));
    }

    public function transition(Request $request, ServiceOrder $order, OrderWorkflow $workflow): RedirectResponse
    {
        $data = $request->validate(['action' => 'required|in:quote,approve,start,complete,cancel',
            'quote_cents' => 'nullable|integer|min:1|max:100000000', 'diagnosis' => 'nullable|string|max:4000']);
        $workflow->transition($order->id, $request->user(), $data['action'], $data);

        return redirect()->route('orders.show', $order)->with('success', 'Situação atualizada.');
    }
}
