<?php

namespace App\Http\Controllers;

use App\Models\Accounts;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function index()
    {
        return response()->json(Accounts::all(), 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'account_number' => 'required|string|unique:accounts,account_number',
            'initial_balance' => 'required|integer|min:0', // Блокирует отрицательный баланс при создании!
            'currency_id' => 'required|integer',
        ]);

        $account = Accounts::create($validated);
        return response()->json($account, 201);
    }

    public function update(Request $request, $id)
    {
        $account = Accounts::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            // Исключаем текущий id из проверки уникальности, чтобы номер счета можно было сохранить при изменении других полей
            'account_number' => 'sometimes|required|string|unique:accounts,account_number,' . $id,
            'initial_balance' => 'sometimes|required|integer|min:0', // Блокирует отрицательный баланс при изменении!
            'currency_id' => 'sometimes|required|integer',
        ]);

        $account->update($validated);
        return response()->json($account, 200);
    }
}
