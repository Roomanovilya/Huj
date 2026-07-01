<?php

namespace App\Observers;

use App\Models\CashFlow;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class CashFlowObserver
{
    // Срабатывает ПОСЛЕ создания новой заявки
    public function created(CashFlow $cashFlow): void
    {
        AuditLog::create([
            'user_id' => Auth::id(),
            'entity_type' => 'cash_flow',
            'entity_id' => $cashFlow->id,
            'action' => 'create',
            'old_values' => json_encode([]),
            'new_values' => json_encode($cashFlow->getAttributes(), JSON_UNESCAPED_UNICODE),
        ]);
    }

    // Срабатывает ПОСЛЕ изменения (например, смены статуса)
    public function updated(CashFlow $cashFlow): void
    {
        $changedFields = $cashFlow->getChanges();

        $oldValues = [];
        foreach ($changedFields as $key => $value) {
            $oldValues[$key] = $cashFlow->getOriginal($key);
        }

        AuditLog::create([
            'user_id' => Auth::id(),
            'entity_type' => 'cash_flow',
            'entity_id' => $cashFlow->id,
            'action' => 'update',
            'old_values' => json_encode($oldValues, JSON_UNESCAPED_UNICODE),
            'new_values' => json_encode($changedFields, JSON_UNESCAPED_UNICODE),
        ]);
    }

    /**
     * Срабатывает ПЕРЕД сохранением изменений в БД
     */
    public function updating(\App\Models\CashFlow $cashFlow): void
    {
        // Проверяем, изменились ли критически важные для бюджета поля
        if ($cashFlow->isDirty(['amount', 'planned_date', 'account_id', 'item_id'])) {

            // Если заявка уже имела статус 'approved', 'in_registry' или 'paid'
            if (in_array($cashFlow->getOriginal('status'), ['approved', 'in_registry', 'paid'])) {

                // Сбрасываем статус на "На согласовании"
                $cashFlow->status = 'under_approval';
            }
        }
    }
}
