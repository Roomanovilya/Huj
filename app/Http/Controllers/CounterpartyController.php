<?php

namespace App\Http\Controllers;

use App\Models\Counterparty;
use Illuminate\Http\Request;

class CounterpartyController extends Controller
{
    // Выдать всех контрагентов
    public function index()
    {
        return response()->json(Counterparty::all(), 200);
    }

    // Создать нового контрагента
    public function store(Request $request)
    {
        // Проверяем, что нам прислали имя
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        // Сохраняем в базу данных
        $counterparty = Counterparty::create($validated);

        // Возвращаем ответ фронтенду
        return response()->json($counterparty, 201);
    }
}
