<?php

namespace App\Http\Controllers;

use App\Models\Registry;
use Illuminate\Http\Request;

class RegistryController extends Controller
{
    // 1. Получить список всех реестров платежей
    public function index()
    {
        return response()->json(Registry::with('cashFlows')->get(), 200);
    }

    // 2. Создать новый реестр платежей
    public function store(Request $request)
    {
        $validated = $request->validate([
            'status' => 'required|in:draft,sent_to_bank,paid',
            'date' => 'required|date',
        ]);

        $registry = Registry::create($validated);

        return response()->json($registry, 201);
    }

    // 3. Показать конкретный реестр
    public function show($id)
    {
        $registry = Registry::with('cashFlows')->findOrFail($id);
        return response()->json($registry, 200);
    }

    // 4. Обновить реестр
    public function update(Request $request, $id)
    {
        $registry = Registry::findOrFail($id);

        $validated = $request->validate([
            'status' => 'nullable|in:draft,sent_to_bank,paid',
            'date' => 'nullable|date',
        ]);

        $registry->update($validated);

        return response()->json($registry, 200);
    }

    // 5. Удалить реестр
    public function destroy($id)
    {
        $registry = Registry::findOrFail($id);
        $registry->delete();

        return response()->json(null, 204);
    }

    // Автоматически собрать утвержденные заявки в этот реестр
    public function attachCashFlows($id)
    {
        $registry = Registry::findOrFail($id);

        // 1. Ищем платежи, у которых planned_date совпадает с date реестра и статус 'approved'
        $cashFlows = \App\Models\CashFlow::where('type', 'payment')
            ->where('planned_date', $registry->date)
            ->where('status', 'approved')
            ->get();

        $updatedCount = 0;

        foreach ($cashFlows as $cashFlow) {
            // Проверяем, не привязан ли уже этот платеж к ДАННОМУ реестру
            if (!$registry->cashFlows()->where('payment_id', $cashFlow->id)->exists()) {

                // 2. Делаем запись в таблицу связей registry_payment
                $registry->cashFlows()->attach($cashFlow->id);

                // 3. Меняем статус самого платежа на 'in_registry' согласно ТЗ
                $cashFlow->update(['status' => 'in_registry']);

                $updatedCount++;
            }
        }

        return response()->json([
            'message' => 'Реестр успешно заполнен заявками через таблицу связей!',
            'registry_id' => $registry->id,
            'registry_date' => $registry->date,
            'added_cash_flows_count' => $updatedCount
        ], 200);
    }
}
