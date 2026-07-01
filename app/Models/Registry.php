<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Registry extends Model
{
    // Явно указываем имя таблицы из ТЗ
    protected $table = 'registries';

    protected $guarded = [];

    // Связь многие-ко-многим с таблицей cash_flows через пивот registry_payment
    public function cashFlows()
    {
        return $this->belongsToMany(CashFlow::class, 'registry_payment', 'registry_id', 'payment_id');
    }
}
