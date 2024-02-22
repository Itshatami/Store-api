<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    public function send(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => "required",
            'order_items' => "required",
            'order_items.*.product_id' => "required|integer",
            'order_items.*.quantity' => "required|integer",
            "request_from" => "required"
        ]);
        if ($validator->fails()) {
            return $this->eResponse($validator->messages(), 422);
        }
        $totalAmount = 0;
        $deliveryAmount = 0;
        foreach ($request->order_items as $order_item) {
            $product = Product::findOrFail($order_item['product_id']);
            if ($product->quantity < $order_item['quantity']) {
                return $this->eResponse('quantity is not enough', 400);
            }
            $totalAmount += $product->price * $order_item['quantity'];
            $deliveryAmount += $product->delivery_amount;
        }
        $payingAmount = $totalAmount + $deliveryAmount;

        $amounts = [
            'totalAmount' => $totalAmount,
            'deliveryAmount' => $deliveryAmount,
            'payingAmount' => $payingAmount
        ];

        $api = env("PAY_IR_API_KEY");
        $amount = $payingAmount;
        $mobile = "شماره موبایل";
        $factorNumber = "شماره فاکتور";
        $description = "توضیحات";
        $redirect = env('PAY_IR_CALLBACK_URL');
        $result = $this->sendRequest($api, $amount, $redirect, $mobile, $factorNumber, $description);
        $result = json_decode($result);

        if ($result->status) {
            OrderController::create($request, $amounts, $result->token);
            // $order = new OrderController();
            // $order->create($request, $amounts, $result->token);
            $go = "https://pay.ir/pg/$result->token";
            return $this->sResponse(['url' => $go]);
        } else {
            return $this->eResponse($result->errorMessage, 422);
        }
    }

    public function verify(Request $request)
    {
        $api = env("PAY_IR_API_KEY");
        $token = $request->token;
        $result = json_decode($this->verifyRequest($api, $token));
        return response()->json($result);
        if (isset($result->status)) {
            if ($result->status == 1) {
                echo "<h1>تراکنش با موفقیت انجام شد</h1>";
            } else {
                echo "<h1>تراکنش با خطا مواجه شد</h1>";
            }
        } else {
            if ($_GET['status'] == 0) {
                echo "<h1>تراکنش با خطا مواجه شد</h1>";
            }
        }
    }

    public function sendRequest($api, $amount, $redirect, $mobile = null, $factorNumber = null, $description = null)
    {
        return $this->curl_post('https://pay.ir/pg/send', [
            'api'          => $api,
            'amount'       => $amount,
            'redirect'     => $redirect,
            'mobile'       => $mobile,
            'factorNumber' => $factorNumber,
            'description'  => $description,
        ]);
    }

    public function curl_post($url, $params)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
        ]);
        $res = curl_exec($ch);
        curl_close($ch);

        return $res;
    }

    public function verifyRequest($api, $token)
    {
        return $this->curl_post('https://pay.ir/pg/verify', [
            'api'     => $api,
            'token' => $token,
        ]);
    }
}
