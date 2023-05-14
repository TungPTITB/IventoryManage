<?php

namespace App\Http\Controllers\Api;

use App\Model\Category;
use App\Model\Product;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class PosController extends Controller
{
    public function categoryProducts($id)
    {
        $category = Category::findOrfail($id);
        $products = $category->products;
        return response()->json($products);
    }

    public function orderStore(Request $request)
    {
        $this->validate($request, [
            'customer_id' => 'required',
            'payby' => 'required',
        ]);

        $data = [];
        $data['customer_id'] = $request->customer_id;
        $data['cart_quantity'] = $request->cart_quantity;
        $data['sub_total'] = $request->sub_total;
        $data['total'] = $request->total;
        $data['vat'] = $request->vat;
        $data['pay'] = $request->pay;
        $data['due'] = $request->due;
        $data['payby'] = $request->payby;
        $data['order_date'] = date('d/m/Y');
        $data['order_month'] = date('F');
        $data['order_year'] = date('Y');
        $order_id = DB::table('orders')->insertGetId($data);
        $carts = DB::table('carts')->get();
        foreach ($carts as $cart) {
            $orderData = [];
            $orderData['order_id'] = $order_id;
            $orderData['product_id'] = $cart->product_id;
            $orderData['product_quantity'] = $cart->product_quantity;
            $orderData['sub_total'] = $cart->sub_total;
            $orderData['product_price'] = $cart->product_price;
            DB::table('order_details')->insert($orderData);
            DB::table('products')->where('id', $cart->product_id)->update(['p_quantity' => DB::raw('p_quantity -' . $cart->product_quantity)]);
        }
        DB::table('carts')->delete();
        return response()->json(['success' => 'Successfully Order Complete!']);
    }

    function execPostRequest($url, $data)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data))
        );
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        //execute post
        $result = curl_exec($ch);
        //close connection
        curl_close($ch);
        return $result;
    }

    public function paymentMomo()
    {
        $endpoint = "https://test-payment.momo.vn/v2/gateway/api/create";


        $partnerCode = 'MOMOBKUN20180529';
        $accessKey = 'klm05TvNBzhg7h7j';
        $secretKey = 'at67qH6mk8w5Y1nAyMoYKMWACiEi2bsa';
        $orderInfo = "Thanh toán qua MoMo";
        $amount = "1000";
        $orderId = time();
        $redirectUrl = "127.0.0.1:8000";
        $ipnUrl = "https://webhook.site/b3088a6a-2d17-4f8d-a383-71389a6c600b";
        $extraData = "";


//        if (!empty($_POST)) {
            $partnerCode = $partnerCode;
            $accessKey = $accessKey;
            $serectkey = $secretKey;
            $orderId = $orderId; // Mã đơn hàng
            $orderInfo = $orderInfo;
            $amount = $amount;
//            $ipnUrl = $_POST["ipnUrl"];
            $redirectUrl = $redirectUrl;
            $extraData = $extraData;

            $requestId = time() . "";
            $requestType = "captureWallet";
//            $extraData = ($_POST["extraData"] ? $_POST["extraData"] : "");
            //before sign HMAC SHA256 signature
            $rawHash = "accessKey=" . $accessKey . "&amount=" . $amount . "&extraData=" . $extraData . "&ipnUrl=" . $ipnUrl . "&orderId=" . $orderId . "&orderInfo=" . $orderInfo . "&partnerCode=" . $partnerCode . "&redirectUrl=" . $redirectUrl . "&requestId=" . $requestId . "&requestType=" . $requestType;
            $signature = hash_hmac("sha256", $rawHash, $serectkey);
            $data = array('partnerCode' => $partnerCode,
                'partnerName' => "Test",
                "storeId" => "MomoTestStore",
                'requestId' => $requestId,
                'amount' => $amount,
                'orderId' => $orderId,
                'orderInfo' => $orderInfo,
                'redirectUrl' => $redirectUrl,
                'ipnUrl' => $ipnUrl,
                'lang' => 'vi',
                'extraData' => $extraData,
                'requestType' => $requestType,
                'signature' => $signature);
            $result = $this->execPostRequest($endpoint, json_encode($data));
            $jsonResult = json_decode($result, true);  // decode json
dd($jsonResult);
            //Just a example, please check more in there

            header('Location: ' . $jsonResult['payUrl']);
        }
//    }
}
