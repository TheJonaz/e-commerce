<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Support\Facades\Auth;

class AccountController extends Controller
{
    public function show()
    {
        $customer = Auth::guard('customer')->user();

        return view('shop.account.show', [
            'customer' => $customer,
            'recentOrders' => $customer->orders()
                ->orderByDesc('placed_at')
                ->limit(5)
                ->get(),
        ]);
    }

    public function orders()
    {
        $customer = Auth::guard('customer')->user();

        return view('shop.account.orders', [
            'orders' => $customer->orders()->orderByDesc('placed_at')->paginate(20),
        ]);
    }

    public function order(string $orderNumber)
    {
        $customer = Auth::guard('customer')->user();

        $order = $customer->orders()
            ->with('items')
            ->where('order_number', $orderNumber)
            ->firstOrFail();

        return view('shop.account.order', ['order' => $order]);
    }
}
