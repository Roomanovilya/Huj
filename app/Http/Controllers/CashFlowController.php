<?php

namespace App\Http\Controllers;

use App\Models\CashFlow;
use Illuminate\Http\Request;

class CashFlowController extends Controller
{
    public function index()
    {
        $cashFlows = CashFlow::with(['account', 'counterparty', 'item'])->get();

        return response()->json($cashFlows, 200);
    }

    public function store(Request $request)
    {
        // 1. ВАЛИДАЦИЯ по ТЗ
        $validated = $request->validate([
            'type' => 'required|in:income,payment', // Поступление или Списание
            'amount' => 'required|integer|min:1', // Сумма в копейках, строго положительная
            'planned_date' => 'required|date', // Планируемая дата
            'account_id' => 'required|integer|exists:accounts,id', // Проверяем, что такой счет существует в БД
            'item_id' => 'required|integer|exists:items,id', // Проверяем статью
            'counterparty_id' => 'nullable|integer|exists:counterparties,id', // Контрагент может быть NULL
            'description' => 'nullable|string', // Назначение платежа

            // Поля ниже заполняются только для платежей (type = payment)
            'priority' => 'nullable|required_if:type,payment|in:low,medium,high',
            'status' => 'nullable|in:draft,under_approval,approved,in_registry,paid,rejected',
        ]);

        // 2. БИЗНЕС-ЛОГИКА: автовыставление статуса по умолчанию
        // Если создается ПЛАТЕЖ и статус не передан с фронтенда — ставим по умолчанию 'draft' (Черновик)
        if ($validated['type'] === 'payment' && empty($validated['status'])) {
            $validated['status'] = 'draft';
        }

        // Если это ПОСТУПЛЕНИЕ (income) — приоритет и статус по ТЗ должны быть NULL
        if ($validated['type'] === 'income') {
            $validated['priority'] = null;
            $validated['status'] = null;
        }

        // 3. СОХРАНЕНИЕ
        $cashFlow = CashFlow::create($validated);

        // 4. ОТВЕТ
        return response()->json($cashFlow, 201);
    }

    public function update(Request $request, $id)
    {
        // 1. Ищем заявку в базе по её ID
        $cashFlow = CashFlow::findOrFail($id);

        // 2. Валидируем пришедшие изменения
        $validated = $request->validate([
            'type' => 'nullable|in:income,payment',
            'amount' => 'nullable|integer|min:1',
            'planned_date' => 'nullable|date',
            'account_id' => 'nullable|integer|exists:accounts,id',
            'item_id' => 'nullable|integer|exists:items,id',
            'counterparty_id' => 'nullable|integer|exists:counterparties,id',
            'description' => 'nullable|string',
            'priority' => 'nullable|in:low,medium,high',
            'status' => 'nullable|in:draft,under_approval,approved,in_registry,paid,rejected',
        ]);

        // 3. БИЗНЕС-ЛОГИКА по ТЗ: автоматический сброс статуса при редактировании
        // Если это расход (payment) и его отредактировали — принудительно ставим статус 'under_approval'
        if ($cashFlow->type === 'payment' || (isset($validated['type']) && $validated['type'] === 'payment')) {
            $validated['status'] = 'under_approval';
        }

        // 4. Обновляем запись в базе данных PostgreSQL
        $cashFlow->update($validated);

        // 5. Возвращаем обновленную заявку обратно
        return response()->json($cashFlow, 200);
    }

    public function show($id)
    {
        // Ищем заявку по ID. Если не нашли — Laravel сам вернет ошибку 404 Not Found
        $cashFlow = CashFlow::with(['account', 'counterparty', 'item'])->findOrFail($id);

        return response()->json($cashFlow, 200);
    }

    // 2. Удалить заявку из системы
    public function destroy($id)
    {
        // Ищем заявку в базе
        $cashFlow = CashFlow::findOrFail($id);

        // Удаляем её из PostgreSQL
        $cashFlow->delete();

        // Возвращаем пустой ответ со статусом 204 No Content (Успешно удалено)
        return response()->json(null, 204);
    }
}
