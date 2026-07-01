<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\RecurringTemplate;
use App\Models\CashFlow;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB; // Добавили фасад БД

class GenerateRecurringPayments extends Command
{
    protected $signature = 'payments:generate-recurring';
    protected $description = 'Генерация заявок из шаблонов повторяющихся платежей с учетом выходных (перенос на пятницу ДО)';

    public function handle()
    {
        $today = Carbon::today();

        $searchUntil = $today->copy();
        if ($today->isFriday()) {
            $searchUntil->addDays(2);
        }

        $templates = RecurringTemplate::where('start_date', '<=', $searchUntil)
            ->where(function ($query) use ($today) {
                $query->whereNull('end_date')->orWhere('end_date', '>=', $today);
            })->get();

        $generatedCount = 0;

        foreach ($templates as $template) {
            while (true) {
                $nextScheduleDate = $this->calculateNextScheduleDate($template);

                if (!$nextScheduleDate || $nextScheduleDate->greaterThan($searchUntil)) {
                    break;
                }

                $plannedDate = $nextScheduleDate->copy();
                if ($plannedDate->isSaturday()) {
                    $plannedDate->subDay();
                } elseif ($plannedDate->isSunday()) {
                    $plannedDate->subDays(2);
                }

                $alreadyExists = CashFlow::where('recurring_template_id', $template->id)
                    ->where('original_planned_date', $nextScheduleDate->format('Y-m-d'))
                    ->exists();

                if ($alreadyExists) {
                    $template->update(['last_generated_date' => $nextScheduleDate->format('Y-m-d')]);
                    continue;
                }

                if ($plannedDate->equalTo($today)) {
                    // Используем транзакцию для безопасности данных
                    DB::transaction(function () use ($template, $plannedDate, $nextScheduleDate, &$generatedCount) {
                        CashFlow::create([
                            'type' => 'payment',
                            'amount' => $template->amount,
                            'planned_date' => $plannedDate->format('Y-m-d'),
                            'original_planned_date' => $nextScheduleDate->format('Y-m-d'),
                            'account_id' => $template->account_id,
                            'counterparty_id' => $template->counterparty_id,
                            'item_id' => $template->item_id,
                            'description' => $template->description . ' (Автоматический платеж)',
                            'priority' => $template->priority,
                            'status' => 'draft',
                            'is_recurring' => true,
                            'recurring_template_id' => $template->id,
                        ]);

                        $template->update(['last_generated_date' => $nextScheduleDate->format('Y-m-d')]);
                        $generatedCount++;
                    });
                } else {
                    break;
                }
            }
        }

        $this->info("Успешно сгенерировано заявок: {$generatedCount}");
    }

    private function calculateNextScheduleDate($template): ?Carbon
    {
        $lastGenerated = $template->last_generated_date ? Carbon::parse($template->last_generated_date) : null;
        $startDate = Carbon::parse($template->start_date);

        if (!$lastGenerated) {
            return $startDate;
        }

        switch ($template->frequency) {
            case 'daily':
                return $lastGenerated->copy()->addDay();
            case 'weekly':
                return $lastGenerated->copy()->addWeek();
            case 'monthly':
                return $lastGenerated->copy()->addMonth();
            default:
                return null;
        }
    }
}
