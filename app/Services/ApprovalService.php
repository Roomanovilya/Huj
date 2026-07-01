<?php

namespace App\Services;

use App\Models\Approval;
use App\Models\CashFlow;

class ApprovalService
{
    /**
     * Вынести решение по заявке
     */
    public function makeDecision(int $paymentId, ?int $userId, string $decision, ?string $comment = null)
    {
        // 1. Ищем заявку на платеж
        $payment = CashFlow::findOrFail($paymentId);

        // 2. Создаем запись в таблице approvals
        $approval = Approval::create([
            'payment_id' => $paymentId,
            'user_id' => $userId, // ID пользователя, принявшего решение
            'decision' => $decision, // 'approved' или 'rejected'
            'comment' => $comment,
        ]);

        // 3. Меняем статус у самого платежа в cash_flows
        $payment->update([
            'status' => $decision // Сменит статус на 'approved' или 'rejected'
        ]);

        return $approval;
    }
}
