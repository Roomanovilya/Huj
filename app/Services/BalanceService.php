<?php

namespace App\Services;

use App\Models\Accounts;    // Твоя модель счетов из app/Models/
use App\Models\CashFlow;    // Модель заявок

class BalanceService
{
    /**
     * Рассчитать плановый остаток по конкретному счету на определенную дату
     */
    public function getBalanceOnDate(int $accountId, string $date): int
    {
        // Используем твою модель Accounts
        $account = Accounts::findOrFail($accountId);

        $balance = $account->initial_balance;

        // Поступления пока считаем как 0, так как модели Income еще нет в проекте
        $totalIncomes = 0;

        // Вычисляем все плановые списания по этому счету до указанной даты
        $totalPayments = CashFlow::where('account_id', $accountId)
            ->where('type', 'payment')
            ->whereIn('status', ['approved', 'in_registry', 'paid', 'under_approval'])
            ->where('planned_date', '<=', $date)
            ->sum('amount');

        return $balance + $totalIncomes - $totalPayments;
    }
}
