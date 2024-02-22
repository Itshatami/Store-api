<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public static function create($request, $amounts, $token)
    {

        try {
            DB::beginTransaction();

            $order = Order::create([
                "user_id" => $request->user_id,
                "total_amount" => $amounts["totalAmount"],
                "delivery_amount" => $amounts["deliveryAmount"],
                "paying_amount" => $amounts["payingAmount"]
            ]);

            foreach ($request->order_items as $orderItem) {
                $product = Product::findOrFail($orderItem['product_id']);
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'price' => $product->price,
                    'quantity' => $orderItem['quantity'],
                    'subtotal' => ($product->price * $orderItem['quantity'])
                ]);
            }

            Transaction::create([
                "user_id" => $request->user_id,
                "order_id" => $order->id,
                "amount" => $amounts['payingAmount'],
                "token" => $token,
                "request_from" => $request->request_from
            ]);

            DB::commit();
        } catch (\Throwable $th) {
            //throw $th;
            DB::rollBack();
            return response()->json($th->getMessage(), 200);
        }
    }
}
