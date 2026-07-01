<?php

use App\Http\Controllers\CounterpartyController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\CashFlowController;
use App\Http\Controllers\ExchangeRateController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RegistryController;

Route::apiResource('counterparties', CounterpartyController::class);
Route::apiResource('items', ItemController::class);
Route::apiResource('accounts', AccountController::class);
Route::apiResource('cash_flows', CashFlowController::class);
Route::get('rates', [ExchangeRateController::class, 'index']);
Route::post('rates/update', [ExchangeRateController::class, 'updateRates']);
Route::post('registries/{id}/attach', [RegistryController::class, 'attachCashFlows']);
Route::apiResource('registries', RegistryController::class);
