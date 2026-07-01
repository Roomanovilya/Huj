<?php

namespace App\Services;

use App\Models\Registry;
use App\Models\CashFlow;
use Illuminate\Support\Facades\DB;

class RegistryService
{
    /**
     * Сформировать реестр платежей на определенную дату
     */
    public function createRegistryForDate(string $date)
    {
        return DB::transaction(function () use ($date) {
            // 1. Ищем все одобренные платежи (status = 'approved') на эту дату
            $payments = CashFlow::where('type', 'payment')
                ->where('status', 'approved')
                ->where('planned_date', $date)
                ->get();

            if ($payments->isEmpty()) {
                return null; // Нет одобренных платежей на эту дату
            }

            // 2. Создаем сам реестр в статусе 'draft' (Черновик)
            $registry = Registry::create([
                'date' => $date,
                'status' => 'draft'
            ]);

            // 3. Привязываем платежи к реестру и меняем им статус
            foreach ($payments as $payment) {
                // Привязка в registry_payment
                $registry->cashFlows()->attach($payment->id);

                // Переводим платеж в статус 'in_registry' (в реестре)
                $payment->update(['status' => 'in_registry']);
            }

            return $registry;
        });
    }
}
