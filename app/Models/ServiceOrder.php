<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceOrder extends Model
{
    protected $fillable = ['equipment', 'serial_number', 'description'];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(OrderEvent::class);
    }

    public function statusLabel(): string
    {
        return ['received' => 'Recebida', 'quoted' => 'Aguardando aprovação', 'approved' => 'Aprovada',
            'in_progress' => 'Em serviço', 'completed' => 'Concluída', 'cancelled' => 'Cancelada'][$this->status];
    }
}
