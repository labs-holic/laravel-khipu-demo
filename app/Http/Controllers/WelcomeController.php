<?php namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Khipu;
use Khipu\Model\BanksResponse;
use DateTime;
use App\Order;
use App\KhipuResponse;

class WelcomeController extends Controller {

	/*
	|--------------------------------------------------------------------------
	| Welcome Controller
	|--------------------------------------------------------------------------
	|
	| This controller renders the "marketing page" for the application and
	| is configured to only allow guests. Like most of the other sample
	| controllers, you are free to modify or remove it as you desire.
	|
	*/

	/**
	 * Create a new controller instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		$this->middleware('guest');
	}

	/**
	 * Show the application welcome screen to the user.
	 *
	 * @return Response
	 */
	public function index()
	{
		return view('welcome');
	}

	/**
	 * Make a khipu request.
	 *
	 * @return Response
	 */
	public function pay()
	{
		$configuration = new Khipu\Configuration();
		$configuration->setSecret( config('services.khipu.secret') );
		$configuration->setReceiverId( config('services.khipu.debt_collector_id') );
		$configuration->setDebug(true);

		$client = new Khipu\ApiClient($configuration);
		$payments = new Khipu\Client\PaymentsApi($client);

		try {
		    $order = new Order();
			$order->amount = 1000;
			$order->description = 'Ejemplo de compra';
			$order->save();
		    $expires_date = new DateTime();
		    $expires_date->setDate(2016, 4, 4);
		    $response = $payments->paymentsPost($order->description
		        , 'CLP'
		        , $order->amount
		        , $order->id
		        , null
		        , 'Descripción de la compra'
		        , null
		        , env('KHIPU_URL_RETURN')
		        , env('KHIPU_URL_CANCEL')
		        , env('KHIPU_URL_PICTURE')
		        , env('KHIPU_URL_NOTIFY')
		        , '1.3'
		        , $expires_date
		    );
		} catch (\Exception $e) {
		    echo $e->getMessage();
		}
	}

	/**
	 * khipu required calback url.
	 *
	 * @return Response
	 */
	public function callback(Request $request)
	{
		dd($request);
	}

	/**
	 * Callback on notify on kiphu
	 *
	 */
	 public function notify(Request $request)
	 {
		$api_version = $request->api_version;  // Parámetro api_version
 		$notification_token = $request->notification_token; //Parámetro notification_token
		$r = new KhipuResponse();
		$r->response = json_encode($request->all());
		$r->save();

 		try {
 		    if ($api_version == '1.3') {
 		        $configuration = new Khipu\Configuration();
 		        $configuration->setSecret( config('services.khipu.secret') );
 		        $configuration->setReceiverId( config('services.khipu.debt_collector_id') );
 		        $configuration->setDebug(true);

 		        $client = new Khipu\ApiClient($configuration);
 		        $payments = new Khipu\Client\PaymentsApi($client);

 		        $response = $payments->paymentsGet($notification_token);
				$r = new KhipuResponse();
				$r->response = json_encode((array) $response);
				$r->save();
 		        if ($response->getReceiverId() == $configuration->getReceiverId()) {
 		            if ($response->getStatus() == 'done') {
 		                $order = Order::findOrFail($response->getTransactionId());
 		                if ($order != null ) {
 		                    $order->status = 5;
 							$order->save();
 		                }
 		            }
 		        } else {
 		            // receiver_id no coincide
 		        }
 		    } else {
 		        // Usar versión anterior de la API de notificación
 		    }
 		} catch (Khipu\ApiException $exception) {
 		    print_r($exception->getResponseObject());
 		}
 		//return view('welcome');
	 }

}
