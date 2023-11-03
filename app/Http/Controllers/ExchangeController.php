<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ExchangeController extends Controller
{
    public function __construct()
    {
		$this->baseURL = "https://api.probit.kr/api/exchange/v1";
    }
//
//	public function api_request($method,$path,$data = []){
//		$url = $this->baseURL . $path;
//
//		if($method == "GET" && count($data) > 0){
//			$url = $url . "?";
//			foreach($data as $key => $value){
//				if($value === true){
//					$url = $url . $key . "=true&";
//				}else if($value === false){
//					$url = $url . $key . "=false&";
//				}else{
//					$url = $url . $key . "=" . $value . "&";
//				}
//			}
//		}
//
//		$ch = curl_init($url);
//
//		if($method == "POST" || $method == "DELETE"){
//			$json = json_encode($data);
//			curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
//		}
//
//		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
//		curl_setopt($ch, CURLOPT_HEADER, 0);
//		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
//		$result = json_decode(curl_exec($ch),true);
//		curl_close($ch);
//
//
//		return $result['data'];
//	}
//
//	public function getPrices(Request $request){
//
//		$result = $this->api_request("GET","/ticker");
//
//		$data = [];
//		foreach($result as $ticker){
//			if($ticker['market_id'] != 'USDT-KRW'){
//				continue;
//			}
//
//			$usdtKRW = $ticker['last'];
//			break;
//		}
//
//		$data['USDT'] = $usdtKRW;
//
//		foreach($result as $ticker){
//			if(substr($ticker['market_id'],-5) != '-USDT'){
//				continue;
//			}
//
//			$len = strlen($ticker['market_id']);
//			$data[substr($ticker['market_id'],0,$len-5)] = empty($ticker['last'])?0:$ticker['last'] * $usdtKRW;
//		}
//
//		return response()->json($data);
//	}


	public function getPrices(Request $req){
        $data = [];
        $res = $this->bitsumAPI();
        if($res['status'] === "0000") {
            foreach ($res['data'] as $key => $val){
                if(isset($val['closing_price'])){
                    $data[$key] = $val['closing_price'];
                }
            }
            $data["USDT"] = $this->probitAPI();
        }
        return $data;
    }

    public function bitsumAPI(){
        $url = "https://api.bithumb.com/public/ticker/ALL_KRW";
        $result = $this->api_req($url, "GET");

        return json_decode($result, true);
    }

    public function probitAPI(){
        $url = "https://api.probit.kr/api/exchange/v1/ticker?market_ids=USDT-KRW";
        $result = $this->api_req($url, "GET", );

        $res = json_decode($result, true);
        return $res["data"][0]['last'];
    }

    public function netinfo(Request $req){
        $url = 'https://api.bithumb.com/public/ticker/BNB_KRW';
        $result = $this->api_req($url, "GET", );

        $res = json_decode($result, true);
        return response()->json([
            'data' => $res['data']
        ]);
    }
    public function api_req($url, $method, $data=[]){
        $ch = curl_init($url);

        if($method == "POST" || $method == "DELETE"){
            $json = json_encode($data);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        }

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

//        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','x-api-token: '. $this->api_token));
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
}
