<?php

namespace App\Services;

use App\Models\ExchangeRate;
use App\Models\Currency;
use Illuminate\Support\Facades\Http;
use Exception;
use Carbon\Carbon;

class CurrencyService
{
    /**
     * Запрос актуальных курсов валют (имитация Forex/ЦБ РФ для лабы)
     */
    public function fetchRatesFromExchange(): ?array
    {
        // Используем открытый API ЦБ РФ, который отдаёт курсы в формате JSON
        $response = Http::withoutVerifying()->get('https://www.cbr-xml-daily.ru/daily_json.js');

        if ($response->successful()) {
            $data = $response->json();
            $valute = $data['Valute'] ?? [];

            // Извлекаем нужные по ТЗ валюты: USD, EUR, AMD
            $usd = $valute['USD']['Value'] ?? null;
            $eur = $valute['EUR']['Value'] ?? null;

            // В API ЦБ курс драма обычно даётся за 100 единиц (разделим на 100 для курса к 1 драму)
            $amdRaw = $valute['AMD']['Value'] ?? null;
            $amdNominal = $valute['AMD']['Nominal'] ?? 1;
            $amd = $amdRaw ? ($amdRaw / $amdNominal) : null;

            return [
                'USD' => $usd,
                'EUR' => $eur,
                'AMD' => $amd,
            ];
        }

        return null;
    }

    /**
     * Скачать курсы и автоматически сохранить их в таблицу exchange_rates на сегодня
     */
    public function updateRatesInDatabase(): array
    {
        $rates = $this->fetchRatesFromExchange();

        if (!$rates) {
            throw new Exception("Не удалось получить курсы валют из внешнего API.");
        }

        $today = Carbon::today()->format('Y-m-d');

        // Карта соответствия кодов валют и их ID в твоей базе данных
        // Убедись, что в таблице currencies у тебя именно такие ID (1-RUB, 2-USD, 3-EUR, 4-AMD)
        $currencyMap = [
            'USD' => 2,
            'EUR' => 3,
            'AMD' => 4,
        ];

        foreach ($currencyMap as $code => $currencyId) {
            if (isset($rates[$code])) {
                // Используем updateOrCreate, чтобы не плодить дубликаты за один и тот же день
                ExchangeRate::updateOrCreate(
                    [
                        'currency_id' => $currencyId,
                        'rate_date' => $today,
                    ],
                    [
                        'rate_to_rub' => $rates[$code],
                    ]
                );
            }
        }

        return $rates;
    }

    /**
     * Конвертировать сумму в рубли по курсу на указанную дату из БД
     */
    public function convertToRub(int $currencyId, int $amount, string $date): int
    {
        // Если валюта уже рубли (ID = 1), то сумма не меняется
        if ($currencyId === 1) {
            return $amount;
        }

        // Ищем самый свежий курс в БД на указанную дату или ранее
        $rate = ExchangeRate::where('currency_id', $currencyId)
            ->where('rate_date', '<=', $date)
            ->orderBy('rate_date', 'desc')
            ->first();

        if (!$rate) {
            throw new Exception("Курс валюты для ID {$currencyId} на дату {$date} не найден в БД!");
        }

        // Умножаем копейки на курс и округляем по правилам арифметики
        return (int) round($amount * $rate->rate_to_rub);
    }
}
