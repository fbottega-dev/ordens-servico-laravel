<?php

namespace App\Services;

use App\Models\ServiceOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class OrderWorkflow
{
    public function transition(int $id, User $actor, string $action, array $data = []): ServiceOrder
    {
        return DB::transaction(function () use ($id, $actor, $action, $data) {
            $order = ServiceOrder::query()->lockForUpdate()->findOrFail($id);
            Gate::forUser($actor)->authorize('view', $order);
            $staff = $actor->role === 'staff';
            $owner = $order->user_id === $actor->id;
            $next = match ($action) {
                'quote' => $staff && $order->status === 'received' ? 'quoted' : null,
                'approve' => $owner && $order->status === 'quoted' ? 'approved' : null,
                'start' => $staff && $order->status === 'approved' ? 'in_progress' : null,
                'complete' => $staff && $order->status === 'in_progress' ? 'completed' : null,
                'cancel' => $owner && in_array($order->status, ['received', 'quoted'], true) ? 'cancelled' : null,
                default => null,
            };
            if ($next === null) {
                throw ValidationException::withMessages(['action' => 'Transição não permitida para este usuário ou situação.']);
            }
            if ($action === 'quote') {
                validator($data, ['quote_cents' => 'required|integer|min:1|max:100000000', 'diagnosis' => 'required|string|max:4000'])->validate();
                $order->quote_cents = $data['quote_cents'];
                $order->diagnosis = $data['diagnosis'];
            }
            $order->status = $next;
            $order->save();
            $order->events()->create(['user_id' => $actor->id, 'action' => $action]);

            return $order;
        });
    }
}
