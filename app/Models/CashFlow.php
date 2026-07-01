<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashFlow extends Model
{
    protected $guarded = [];

    public function account()
    {
        return $this->belongsTo(Accounts::class, 'account_id');
    }

    // Связь с контрагентом 
    public function counterparty()
    {
        return $this->belongsTo(Counterparty::class, 'counterparty_id');
    }

    // Связь со статьей 
    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    // Допиши этот метод внутрь класса CashFlow
    public function registry()
    {
        return $this->belongsTo(Registry::class, 'registry_id');
    }

    /**
     * Получить текстовый статус с учетом требований заказчика о переносе
     */
    public function getFormattedStatusAttribute(): string
    {
        // Массив красивых русских названий для базовых статусов
        $statuses = [
            'draft' => 'Черновик',
            'under_approval' => 'На согласовании',
            'approved' => 'Согласована',
            'in_registry' => 'В реестре',
            'paid' => 'Оплачена',
            'rejected' => 'Отклонена',
        ];

        $currentStatus = $statuses[$this->status] ?? $this->status;

        // Если заявка была согласована и её перенесли (есть исходная дата)
        if ($this->status === 'approved' && !empty($this->original_planned_date)) {
            return "Согласовано, перенесено с " . \Carbon\Carbon::parse($this->original_planned_date)->format('d.m.Y');
        }

        return $currentStatus;
    }
}
