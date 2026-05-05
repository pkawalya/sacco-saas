<?php

use App\Http\Requests\StoreOrderRequest;
use App\Models\Central\Order;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('filament.admin.auth.login');
})->name('home');

Route::post('/order', function (StoreOrderRequest $request) {
    Order::create([
        'order_number' => Order::generateOrderNumber(),
        ...$request->validated(),
    ]);

    return back()->with('success', 'Order received! We\'ll contact you within 24 hours to set up your SACCO.');
})->name('order.store');
