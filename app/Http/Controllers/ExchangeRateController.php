<?php

namespace App\Http\Controllers;

use App\Models\ExchangeRate;
use App\Models\Currency;
use App\Services\CurrencyService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ExchangeRateController extends Controller
{
    protected $currencyService;

    public function __construct(CurrencyService $currencyService)
    {
        $this->currencyService = $currencyService;
    }

    // Показать всю историю сохраненных курсов
    public function index()
    {
        return response()->json(ExchangeRate::with('currency')->get(), 200);
    }

    // Запустить обновление курсов валют (включая AMD)
    public function updateRates()
    {
        $rates = $this->currencyService->fetchRatesFromExchange();

        if (!$rates) {
            return response()->json(['message' => 'Не удалось получить актуальные курсы'], 500);
        }

        foreach ($rates as $code => $value) {
            if ($value) {
                // Задаем параметры для каждой валюты по ТЗ
                $name = '';
                $symbol = '';

                if ($code === 'USD') {
                    $name = 'Доллар США';
                    $symbol = '$';
                }
                if ($code === 'EUR') {
                    $name = 'Евро';
                    $symbol = '€';
                }
                if ($code === 'AMD') {
                    $name = 'Армянский драм';
                    $symbol = '֏';
                }

                // 1. Проверяем / создаем валюту в таблице currencies
                $currency = Currency::firstOrCreate(
                    ['code' => $code],
                    ['name' => $name, 'symbol' => $symbol]
                );

                // 2. Записываем курс в таблицу exchange_rates на сегодняшнюю дату
                ExchangeRate::updateOrCreate(
                    [
                        'currency_id' => $currency->id,
                        'rate_date' => Carbon::today()->toDateString()
                    ],
                    [
                        'rate_to_rub' => round($value, 6) // Округляем до 6 знаков по ТЗ decimal(18,6)
                    ]
                );
            }
        }

        return response()->json([
            'message' => 'Курсы валют (USD, EUR, AMD) успешно синхронизированы с биржи!',
            'date' => Carbon::today()->toDateString(),
            'rates_in_rub' => [
                '1 USD' => round($rates['USD'], 2) . ' ₽',
                '1 EUR' => round($rates['EUR'], 2) . ' ₽',
                '1 AMD' => round($rates['AMD'], 4) . ' ₽'
            ]
        ], 200);
    }
}
