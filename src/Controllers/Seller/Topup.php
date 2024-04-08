<?php

namespace Svakode\Svaflazz\Controllers\Seller;

use App\Models\GameVoucher;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Service;
use App\Models\UserMutation;
use App\Models\WASender;
use App\Models\WebSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Svakode\Svaflazz\SvaflazzClient;

class Topup extends Base
{
    /**
     * CheckBalance constructor.
     * @param string $buyerSkuCode
     * @param string $customerNo
     * @param string $refId
     * @param string $msg
     * @param SvaflazzClient $client
     */
    public function __construct(SvaflazzClient $client)
    {
        parent::__construct($client);

    }

    public function order(Request $request)
    {
        $command           = $request->commands;
        $signature         = $this->sign($request->ref_id);
        $callbackSignature = $request->sign;
        $testing           = $request->testing;

        // if ($signature !== (string) $callbackSignature) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Invalid signature',
        //     ]);
        // }

        // if($request->ip() === "52.74.250.133")
        // {
        
            Log::info('Digiflazz Payload (temporary) => {id}', ['id' => json_encode($request->all())]);

            if($command == "topup")
            {
                /**
                 * Check current order by ref ID
                 * if exists dont create new order
                 * Proses order dan panggil digiflazz seller callback
                 */

                /**
                 * Proses Order ke web
                */

                $filterData = $this->priceControl($request->pulsa_code, "Guest");


                $service = Service::where('name', $request->pulsa_code)->with(['category'])->first();
                
                $filterData['price'] =  1 * $filterData['price'];

                $finalPrice = $this->paymentControl('Saldo', $filterData['price']);

                
                // $pesan = "Halo Kak, \n\n" .
                //     "Berikut adalah rincian pesanan anda :\n" .
                //     "*- Kategori* : " . $service->category->name . "\n" .
                //     "*- Produk* : " . $service->name . "\n" .
                //     "*- Harga* : Rp " . number_format(intval($finalPrice[0]['price']), 0, '.', ',') . "\n" .
                //     "*- Metode* : Digiflazz Saldo\n\n" .
                //     "Silakan melakukan transfer/pembayaran ke nomor yang telah tertera di halaman invoice website JagoTopup tanpa dikurangi atau bahkan dibulatkan.\n\n" .
                //     "*Perhatian*\n" .
                //     "Mohon untuk tidak melakukan transfer/pembayaran jika pesanan sudah melebihi batas waktu 1x24 jam!.\n\n" .
                //     "Untuk rincian lebih lanjut, silakan lihat pada link yang tertera dibawah ini.\n" .
                //     route('invoice.show', [$invoice]) . "\n\n" .
                //     "Terima kasih.";

                // // $phone = str_replace("08", "628", $request->phone);
                // $phone = preg_replace("/^08/mi", "628", $request->phone);

                // $wa = new WASender();
                // $wa->number = $phone;
                // $wa->message = $pesan;
                // $wa->status = 'waiting';
                // $wa->save();


                /**
                 * Check order first
                 */
                $existOrder = Order::where('pay_reference', $request->ref_id)->first();
                if(!empty($existOrder))
                {

                    /**
                     * Sisa saldo
                    */
                    $user = \App\Models\User::find(21);
                    if($existOrder->status == "waiting" || $existOrder->status == "pending" || $existOrder->status == "processing"  )
                    {
                        $statusDigiflazz = "0";
                        $statusName = "PROCESS";
                        $rc = "39";

                    }else if($existOrder->status == "success" )
                    {
                        $statusDigiflazz = "1";
                        $statusName = "SUCCESS";
                        $rc = "00";
                        $body = [
                            "data" => [
                                'ref_id'     => $request->ref_id,
                                'status'     => $statusDigiflazz,
                                "code"      => $request->pulsa_code,
                                "hp"        => $request->hp,
                                "price"     => (string)$existOrder->price,
                                "message"   => $statusName,
                                "balance"   => (string)$user->balance,
                                "tr_id"     => $existOrder->invoice,
                                "rc"        => $rc,
                                "sn"        => $existOrder->invoice
                            ]
                        ];
                        
                        Log::info('Digiflazz Callback Payload (temporary) => {id}', ['id' => json_encode($body)]);
                         
                          
                        $responseCallback = $this->client->setUrl('/seller/callback')
                         ->setBody($body);
    
                        Log::info('Digiflazz Callback URL (temporary) => {id}', ['id' => config('svaflazz.base_url') . '/seller/callback']);
                         
    
                        $responseCallback = $responseCallback->run();
    
                        Log::info('Digiflazz Callback Response (temporary) => {id}', ['id' => $responseCallback->getBody()->getContents()]);
    

                    }else if($existOrder->status == "canceled" || $existOrder->status == "error" )
                    {
                        $statusDigiflazz = "2";
                        $statusName = "FAILED";
                        $rc = "07";

                        $body = [
                            "data" => [
                                'ref_id'     => $request->ref_id,
                                'status'     => $statusDigiflazz,
                                "code"      => $request->pulsa_code,
                                "hp"        => $request->hp,
                                "price"     => (string)$existOrder->price,
                                "message"   => $statusName,
                                "balance"   => (string)$user->balance,
                                "tr_id"     => $existOrder->invoice,
                                "rc"        => $rc,
                                "sn"        => $existOrder->invoice
                            ]
                        ];
                        
                        Log::info('Digiflazz Callback Payload (temporary) => {id}', ['id' => json_encode($body)]);
                         
                          
                        $responseCallback = $this->client->setUrl('/seller/callback')
                         ->setBody($body);
    
                        Log::info('Digiflazz Callback URL (temporary) => {id}', ['id' => config('svaflazz.base_url') . '/seller/callback']);
                         
    
                        $responseCallback = $responseCallback->run();
    
                        Log::info('Digiflazz Callback Response (temporary) => {id}', ['id' => $responseCallback->getBody()->getContents()]);
    
                    }



                    return response()->json([
                        "data" => [
                            'ref_id'     => $request->ref_id,
                            'status'     => $statusDigiflazz,
                            "code"      => $request->pulsa_code,
                            "hp"        => $request->hp,
                            "price"     => (string)$existOrder->price,
                            "message"   => $statusName,
                            "balance"   => (string)$user->balance,
                            "tr_id"     => $existOrder->invoice,
                            "rc"        => $rc,
                            "sn"        => $existOrder->invoice
                        ]
                    ]);
                }else{
                    $invoice = "JGT" . time() . \Illuminate\Support\Str::random(3); //jika erorr ubah lagi jadi BUY

                    //jika final price kurang dari 0
                    // if ($finalPrice < 0) return back()->with('error', 'Terjadi kesalahan sistem');
                    //melakukan pengecekan saldo
                    // if (Auth::user()->balance < $finalPrice) return back()->with('error', 'Saldo anda tidak mencukupi untuk pembelian ini.');

                    // if (Auth::user()->pin != $request->pin) return back()->with('error', 'Pin transaksi anda tidak valid');


                    /**
                     * Sisa saldo
                    */
                    $user = \App\Models\User::find(21);

                    $updatedBalance = $user->balance - $finalPrice;
                    $user->balance = $updatedBalance;
                    $user->save();

                    $userMutation = new UserMutation();
                    $userMutation->user_id = 21;
                    $userMutation->effect = "min";
                    $userMutation->log = "Pembelian product <b>#" . $invoice . "</b> dengan harga Rp " . number_format($finalPrice) . ", sisa saldo : Rp " . number_format($updatedBalance);
                    $userMutation->save();

                    //input data kedalam database
                    $order = new Order();
                    $order->invoice = $invoice;
                    $order->user_id = 21;
                    $order->email = $request->hp;
                    $order->service_id = $service->id;
                    $order->phone = isset($request->hp) ? $request->hp : NULL;
                    $order->data = $request->hp;
                    $order->status = 'waiting';
                    $order->pay_reference = $request->ref_id;
                    $order->pay_number = isset($requestPayment['payment_no']) ? $requestPayment['payment_no'] : '';
                    $order->price = intval($finalPrice);
                    if ($service->category->tipe_product == "joki") {
                        $order->keterangan = "Email & Password : " . $request->data_no . "|" . $request->data_server . "\n" .
                            "Jumlah : " . ($request->jumlah ? $request->jumlah : 0) . "\n" .
                            "Req Hero : " . $request->reqHero . "\n" .
                            "Catatan : " . $request->catatan . "\n" .
                            "Nickname : " . $request->nickname . "\n" .
                            "Login Via : " . $request->login;
                    }
                    if ($request->voucher) {
                        $order->use_voucher = 1;
                        $order->voucher_code = strtolower($request->voucher);
                    }
                    $order->profit = $filterData['profit'];
                    $order->discount = 0;
                    $order->harga_modal = 0;
                    $order->profit_sebelum_discount = 0;
                    $order->payment_method_id = 1;
                    $order->ip_trx = $request->server('REMOTE_ADDR');
                    $order->is_api = true;
                    $order->qty = 1;
                    $order->kode_server = NULL;
                    $order->nama_server = NULL;
                    $order->paid_amount = intval($finalPrice);
                    $order->save();

                    if($order->service->provider === "otomatis")
                    {
                        /**
                         * Add game voucher to this order
                         */
                        $availableVoucher = GameVoucher::where('service_id', $order->service_id)
                        ->where('status', '=', 'new')
                        ->orderBy('created_at', 'ASC')
                        ->take($order->qty)
                        ->get();
                        
                        
                        if(count($availableVoucher) > 0 && count($availableVoucher) >= $order->qty)
                        {
                            foreach($availableVoucher as $voucher)
                            {
                                $voucher->update(['order_id' => $order->id, 'status' => 'used']);
                            }
                            $order->update(['status' => 'success']);
                            
                            $web = \App\Models\WebSetting::where('id', 1)->first();
                            $pesan = "Halo kak, \n\n".
                            "Pesanan anda dengan nomor invoice #".$order->invoice." telah kami proses, silakan lakukan pengecekan terhadap target/data pesanan yang telah anda masukkan. \n".
                            "*- Kategori* : ".$order->service->category->name."\n".
                            "*- Produk* : ".$order->service->name."\n".
                            "*- Target* : ".$order->data."\n".
                            "*- SN/Kode Voucher* : ".(count($order->vouchers()->get()) > 0 ? implode(',', $order->vouchers()->get()->pluck('serial_number', 'code')->toArray()) : '')."\n\n".
                            "Terima kasih telah berbelanja di ".ENV("APP_NAME")."\n\n".
                            "_Jika terdapat kendala/anda rasa notifikasi ini salah, silakan hubungi Admin_\n".
                            "Whatsapp : https://wa.me/".$web->admin_number;

                            // $phone = str_replace("08", "628", $request->phone);
                            $phone = preg_replace("/^08/mi", "628", $request->hp);

                            $wa = new WASender();
                            $wa->number = $phone;
                            $wa->message = $pesan;
                            $wa->status = 'waiting';
                            $wa->save();


                            $statusDigiflazz = "1";
                            $statusName = "SUCCESS";
                            $rc = "00";

                            $body = [
                                "data" => [
                                    'ref_id'     => $request->ref_id,
                                    'status'     => $statusDigiflazz,
                                    "code"      => $request->pulsa_code,
                                    "hp"        => $request->hp,
                                    "price"     => (string)$finalPrice,
                                    "message"   => $statusName,
                                    "balance"   => (string)$user->balance,
                                    "tr_id"     => $invoice,
                                    "rc"        => $rc,
                                    "sn"        => $invoice
                                ]
                            ];

                            
                            Log::info('Digiflazz Callback Payload (temporary) => {id}', ['id' => json_encode($body)]);
                            
                            
                            $responseCallback = $this->client->setUrl('/seller/callback')
                            ->setBody($body);

                            Log::info('Digiflazz Callback URL (temporary) => {id}', ['id' => config('svaflazz.base_url') . '/seller/callback']);
                            

                            $responseCallback = $responseCallback->run();

                            Log::info('Digiflazz Callback Response (temporary) => {id}', ['id' => $responseCallback->getBody()->getContents()]);
                            

                            return response()->json([
                                "data" => [
                                    'ref_id'     => $request->ref_id,
                                    'status'     => $statusDigiflazz,
                                    "code"      => $request->pulsa_code,
                                    "hp"        => $request->hp,
                                    "price"     => (string)$finalPrice,
                                    "message"   => $statusName,
                                    "balance"   => (string)$user->balance,
                                    "tr_id"     => $invoice,
                                    "rc"        => $rc,
                                    "sn"        => $invoice
                                ]
                            ]);

                        }else{
                            $order->update(['status' => 'canceled']);

                            $statusDigiflazz = "2";
                            $statusName = "PRODUCT IS TEMPORARILY OUT OF SERVICE";
                            $rc = "106";

                            $body = [
                                "data" => [
                                    'ref_id'     => $request->ref_id,
                                    'status'     => $statusDigiflazz,
                                    "code"      => $request->pulsa_code,
                                    "hp"        => $request->hp,
                                    "price"     => (string)$finalPrice,
                                    "message"   => $statusName,
                                    "balance"   => (string)$user->balance,
                                    "tr_id"     => $invoice,
                                    "rc"        => $rc,
                                    "sn"        => $invoice
                                ]
                            ];

                            Log::info('Digiflazz Callback Payload (temporary) => {id}', ['id' => json_encode($body)]);
                            
                            
                            $responseCallback = $this->client->setUrl('/seller/callback')
                            ->setBody($body);

                            Log::info('Digiflazz Callback URL (temporary) => {id}', ['id' => config('svaflazz.base_url') . '/seller/callback']);
                                

                            $responseCallback = $responseCallback->run();

                            Log::info('Digiflazz Callback Response (temporary) => {id}', ['id' => $responseCallback->getBody()->getContents()]);


                        }
                    }else{
                        $statusDigiflazz = "0";
                        $statusName = "PROCESS";
                        $rc = "39";

                        return response()->json([
                            "data" => [
                                'ref_id'     => $request->ref_id,
                                'status'     => $statusDigiflazz,
                                "code"      => $request->pulsa_code,
                                "hp"        => $request->hp,
                                "price"     => (string)$finalPrice,
                                "message"   => $statusName,
                                "balance"   => (string)$user->balance,
                                "tr_id"     => $invoice,
                                "rc"        => $rc,
                                "sn"        => $invoice
                            ]
                        ]);

                    }


                    if($order->status == "waiting" || $order->status == "pending" || $order->status == "processing"  )
                    {
                        $statusDigiflazz = "0";
                        $statusName = "PROCESS";
                        $rc = "39";

                    }else if($order->status == "success" )
                    {
                        $statusDigiflazz = "1";
                        $statusName = "SUCCESS";
                        $rc = "00";


                    }else if($order->status == "canceled" || $order->status == "error" )
                    {
                        $statusDigiflazz = "2";
                        $statusName = "FAILED";
                        $rc = "07";
                    }


                    // $body = [
                    //     "data" => [
                    //         'ref_id'     => $request->ref_id,
                    //         'status'     => $statusDigiflazz,
                    //         "code"      => $request->pulsa_code,
                    //         "hp"        => $request->hp,
                    //         "price"     => (string)$finalPrice,
                    //         "message"   => $statusName,
                    //         "balance"   => (string)$user->balance,
                    //         "tr_id"     => $invoice,
                    //         "rc"        => $rc,
                    //         "sn"        => $invoice
                    //     ]
                    // ];

                    // Log::info('Digiflazz Callback Payload (temporary) => {id}', ['id' => json_encode($body)]);
                     
                      
                    // $responseCallback = $this->client->setUrl('/seller/callback')
                    //  ->setBody($body);

                    // Log::info('Digiflazz Callback URL (temporary) => {id}', ['id' => config('svaflazz.base_url') . '/seller/callback']);
                     

                    // $responseCallback = $responseCallback->run();

                    // Log::info('Digiflazz Callback Response (temporary) => {id}', ['id' => $responseCallback->getBody()->getContents()]);
                    

                    return response()->json([
                        "data" => [
                            'ref_id'     => $request->ref_id,
                            'status'     => $statusDigiflazz,
                            "code"      => $request->pulsa_code,
                            "hp"        => $request->hp,
                            "price"     => (string)$finalPrice,
                            "message"   => $statusName,
                            "balance"   => (string)$user->balance,
                            "tr_id"     => $invoice,
                            "rc"        => $rc,
                            "sn"        => $invoice
                        ]
                    ]);
                }
            }

            if($command == "checkstatus")
            {
                /**
                 * Check order to DB Function
                 */

                 $existOrder = Order::where('pay_reference', $request->ref_id)->first();
                 if(!empty($existOrder))
                 {
 
                     /**
                      * Sisa saldo
                     */
                     $user = \App\Models\User::find(21);
                     if($existOrder->status == "waiting" || $existOrder->status == "pending" || $existOrder->status == "processing"  )
                     {
                         $statusDigiflazz = "0";
                         $statusName = "PROCESS";
                         $rc = "39";
 
                     }else if($existOrder->status == "success" )
                     {
                         $statusDigiflazz = "1";
                         $statusName = "SUCCESS";
                         $rc = "00";
 
                         $body = [
                            "data" => [
                                'ref_id'     => $request->ref_id,
                                'status'     => $statusDigiflazz,
                                "code"      => $request->pulsa_code,
                                "hp"        => $request->hp,
                                "price"     => (string)$existOrder->price,
                                "message"   => $statusName,
                                "balance"   => (string)$user->balance,
                                "tr_id"     => $existOrder->invoice,
                                "rc"        => $rc,
                                "sn"        => $existOrder->invoice
                            ]
                        ];
   
                       Log::info('Digiflazz Callback Payload (temporary) => {id}', ['id' => json_encode($body)]);
                        
                         
                       $responseCallback = $this->client->setUrl('/seller/callback')
                        ->setBody($body);
   
                       Log::info('Digiflazz Callback URL (temporary) => {id}', ['id' => config('svaflazz.base_url') . '/seller/callback']);
                        
   
                       $responseCallback = $responseCallback->run();
   
                       Log::info('Digiflazz Callback Response (temporary) => {id}', ['id' => $responseCallback->getBody()->getContents()]);
                       
                     }else if($existOrder->status == "canceled" || $existOrder->status == "error" )
                     {
                         $statusDigiflazz = "2";
                         $statusName = "FAILED";
                         $rc = "07";


                         $body = [
                            "data" => [
                                'ref_id'     => $request->ref_id,
                                'status'     => $statusDigiflazz,
                                "code"      => $request->pulsa_code,
                                "hp"        => $request->hp,
                                "price"     => (string)$existOrder->price,
                                "message"   => $statusName,
                                "balance"   => (string)$user->balance,
                                "tr_id"     => $existOrder->invoice,
                                "rc"        => $rc,
                                "sn"        => $existOrder->invoice
                            ]
                        ];
   
                       Log::info('Digiflazz Callback Payload (temporary) => {id}', ['id' => json_encode($body)]);
                        
                         
                       $responseCallback = $this->client->setUrl('/seller/callback')
                        ->setBody($body);
   
                       Log::info('Digiflazz Callback URL (temporary) => {id}', ['id' => config('svaflazz.base_url') . '/seller/callback']);
                        
   
                       $responseCallback = $responseCallback->run();
   
                       Log::info('Digiflazz Callback Response (temporary) => {id}', ['id' => $responseCallback->getBody()->getContents()]);
                       

                     }
 
 


                     return response()->json([
                         "data" => [
                             'ref_id'     => $request->ref_id,
                             'status'     => $statusDigiflazz,
                             "code"      => $request->pulsa_code,
                             "hp"        => $request->hp,
                             "price"     => (string)$existOrder->price,
                             "message"   => $statusName,
                             "balance"   => (string)$user->balance,
                             "tr_id"     => $existOrder->invoice,
                             "rc"        => $rc,
                             "sn"        => $existOrder->invoice
                         ]
                     ]);
                }
            }
        // }else{
        //     return response()->json([
        //         "data" => [
        //             'message' => 'Akses tidak diizinkan'
        //         ]
        //     ]);

        // }
    }

    public function priceControl($id, $role)
    {
        $discount = 0;
        $service = Service::where('name', $id)->first(); //melakukan pencarian layanan berdasarkan id

        if (!$service) { //jika layanan tidak ditemukan maka akan mengembalikan json dibawah
            return array('status' => false, 'message' => 'Layanan tidak ditemukan atau sedang tidak tersedia.');
        }

        $normal_price = $service->price;

        //mengambil data website
        $web = WebSetting::where('id', 1)->select('margin_guest', 'margin_reseller', 'margin_h2h')->first();

        //jika harga pengambilan margin menggunakan persen
        if ($service->is_percent) {
            //Filterisasi harga berdasarkan role
            if ($role == "Guest" || $role == "Member") {
                $service->price = $service->price + ($service->price * $web->margin_guest / 100);
            } else if ($role == "Reseller") {
                $service->price = $service->price + ($service->price * $web->margin_reseller / 100);
            } else if ($role == "Special" || $role == "Admin") {
                $service->price = $service->price + ($service->price * $web->margin_h2h / 100);
            }
        } else {
            if ($role == "Guest" || $role == "Member") {
                $service->price += $service->guest_price;
            } else if ($role == "Reseller") {
                $service->price += $service->reseller_price;
            } else if ($role == "Special" || $role == "Admin") {
                $service->price += $service->special_price;
            }
        }

        $real_price = $service->price;

        //diskon ditetapkan
        if ($service->is_discount) {
            // /*Filterisasi harga berdasarkan role*/
            if ($role == "Guest" || $role == "Member") {
                $discount = ($real_price / 100) * $service->guest_discount;
            } else if ($role == "Reseller") {
                $discount = ($real_price / 100) * $service->reseller_discount;
            } else if ($role == "Special" || $role == "Admin") {
                $discount = ($real_price / 100) * $service->special_discount;
            }
        } else {
            if ($role == "Guest" || $role == "Member") {
                $discount = $service->guest_discount;
            } else if ($role == "Reseller") {
                $discount = $service->reseller_discount;
            } else if ($role == "Special" || $role == "Admin") {
                $discount = $service->special_discount;
            }
        }

        return array(
            'profit' => ($real_price - $normal_price) - $discount,
            'price' => $real_price - $discount,
            'discount' => $discount,
            'harga_modal' => $normal_price,
            'profit_sebelum_discount' => ($real_price - $normal_price),
            'provider_id' => $service->provider_id,
            'provider' => $service->provider,
            'category_id' => $service->category_id,
            'note' => $service->note,
            'min' => $service->min,
            'max' => $service->max
        ); //mengembalikan hasil pencarian layanan & harga yang telah difilter
    }

    public function paymentControl($method = null, $price = 0)
    {
        if ($method != null) {
            $paymentMethod = PaymentMethod::where('name', $method)->first();
            // dd($method);
            if (!$paymentMethod) return array('status' => false, 'message' => 'Metode tidak valid');

            if ($paymentMethod->is_percent) {
                $total = intval($price + $paymentMethod->fixed_rate + (($price + $paymentMethod->fixed_rate) * $paymentMethod->percent_rate / 100));

                if (($paymentMethod->provider_payment_code == "OVO" || $paymentMethod->provider_payment_code == "SHOPEEPAY") && $paymentMethod->provider == "tripay") {
                    $fee = $price * $paymentMethod->percent_rate / 100;
                    return $fee < 1000 ? $price + 1000 : $total;
                } else {
                    return $total;
                }
            } else {
                return $price + $paymentMethod->fixed_rate + ($price * $paymentMethod->percent_rate / 100);
            }
        } else {
            $paymentMethod = PaymentMethod::where('status', 'active')->get();

            $payment = [];

            foreach ($paymentMethod as $method) {
                $priceFee = 0;
                if ($method->is_percent) {
                    $priceFee = intval($price + $method->fixed_rate + (($price + $method->fixed_rate) * $method->percent_rate / 100));

                    if (($method->provider_payment_code == "OVO" || $method->provider_payment_code == "SHOPEEPAY") && $method->provider == "tripay") {
                        $fee = $price * $method->percent_rate / 100;
                        if ($fee < 1000) {
                            $priceFee = $price + 1000;
                        } else {
                            $priceFee = $priceFee;
                        }
                    }
                } else {
                    $priceFee = $price + $method->fixed_rate + ($price * $method->percent_rate / 100);
                }

                $payment[] = array(
                    'metode' => \Illuminate\Support\Str::slug($method->name, '-'),
                    'price' => "Rp. " . number_format($priceFee, 0, ',', '.')
                );
            }

            return $payment;
        }
    }
}
