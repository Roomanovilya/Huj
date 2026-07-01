<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    public function index()
    {
        return response()->json(Item::all(), 200);
    }

    public function store(Request $request)
    {
        // Валидируем данные: имя обязательно, тип может быть только 'income' или 'payment'
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:income,payment',
        ]);

        $item = Item::create($validated);

        return response()->json($item, 201);
    }
}
