<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use BlockSDK;

use App\Models\ContractDB;

class TopController extends Controller
{
	public function topFarms(Request $request){
		$contractDB = ContractDB::find('farms');
//        dump($contractDB->table_id);
//		$params = $request->all(); // select(정렬), text(검색)


//         $result = $this->contractDB()->selected([
//			'table_id' => $contractDB->table_id,
////			'where' => [
////                [
////                    'columnName' => "allocPoint",
////                    'value' => $request->process()
////                ]
////            ],
////            'order' => [
////				'columnName' => "allocPoint",
////				'sort' => "desc"
////			],
//			'offset' => 0,
//			'limit' => 200,
//		]);
        $farms = $this->contractDatabase('farms');
        if (isset($request->method) && $request->method == "done"){
            $result = $farms->where('allocPoint', "0000000000000000000000000000000000000000000000000000000000000000")->orderBy('pid','desc')->offset(0)->limit(200)->get();
//            dump(1);
        }else if (isset($request->method) && $request->method == 'admin') {
            $offset = empty($request->offset) ? 0 : $request->offset;
            $limit = empty($request->limit) ? 10 : $request->limit;
            $result = $farms->orderBy('allocPoint','desc')->offset($offset)->limit($limit)->get();
//            dump(2);
        }else {
//            if ($this->get_client_ip() == "61.74.109.18"){
//                $limit = empty($request->limit) ? 100 : $request->limit;
//                $result = $farms->orderBy('allocPoint','desc')->offset(0)->limit($limit)->get();
//            }else {
//                $result = $farms->orderBy('allocPoint','desc')->offset(0)->limit(200)->get();
//            }
            $result = $farms->orderBy('allocPoint','desc')->offset(0)->limit(200)->get();
//            dump(3);
        }
//        return response()->json([
//            'data' => $farms
//        ]);
//        dd($result);
        if($result['total'] == 0){
            return response()->json([
                'error' => [
                    'message' => "Not Data"
                ]
            ]);
        }

        $data = [];
//		foreach($result['payload']['data'] as $farm){
        foreach($result['data'] as $farm){
            if ($request->method != "done"){
                if($farm['allocPoint'] == "0x0"){
                    continue;
                }
            }

			$tokenAddress0 = $this->getCacheToken0($farm['lpAddress']);
			$tokenAddress1 = $this->getCacheToken1($farm['lpAddress']);


			$lpInfo = $this->getCacheTokenInfo($farm['lpAddress']);

			$tokenInfo0 = $this->getCacheTokenInfo($tokenAddress0);
			$tokenInfo1 = $this->getCacheTokenInfo($tokenAddress1);

			$reserves = $this->getCacheReserves($farm['lpAddress']);

			$farm['address'] = $farm['lpAddress'];
			$farm['lpInfo'] = $lpInfo;
			$farm['tokenInfo0'] = $tokenInfo0;
			$farm['tokenInfo1'] = $tokenInfo1;
			$farm['reserves'] = $reserves;
            if(isset($request->method) && $request->method == 'admin'){
                $addressInfo = $this->netClient()->getAddressInfo([
                    'address' => $farm['lpAddress']
                ]);
                if($addressInfo['state']['success'] == true){
                    $farm['addressInfo'] = $addressInfo['payload'];
                }

            }



			array_push($data,$farm);
		}
//        print_r($data);
//        exit;

		return response()->json([
			'data' => $data,
//			'total'=> $result['payload']['total'],
			'total'=> $result['total'],
		]);
	}

//    public function netClient(){
//        if(envDB('BASE_MAINNET') == 'ETH')
//            return BlockSDK::createEthereum(envDB('BLOCKSDK_TOKEN'));
//        if(envDB('BASE_MAINNET') == 'BSC')
//            return BlockSDK::createBinanceSmart(envDB('BLOCKSDK_TOKEN'));
//        if(envDB('BASE_MAINNET') == 'KLAY')
//            return BlockSDK::createKlaytn(envDB('BLOCKSDK_TOKEN'));
//    }

	public function topTokens(Request $request){
		$tokens = $this->contractDatabase('tokens');
		if(empty($tokens) == true){
			return response()->json([
				'error' => [
					'message' => "존재하지 않는 테이블"
				]
			]);
		}

		$tokenHour = $this->contractDatabase('tokenHour');
		if(empty($tokenHour) == true){
			return response()->json([
				'error' => [
					'message' => "존재하지 않는 테이블"
				]
			]);
		}

		$pairHour = $this->contractDatabase('pairHour');
		if(empty($pairHour) == true){
			return response()->json([
				'error' => [
					'message' => "존재하지 않는 테이블"
				]
			]);
		}

        $result = $tokens->orderBy('totalSwapCount','desc')->offset(0)->limit(30)->get();
        $tokens = $result['data'];


		if(empty($tokens) == true){
			return response()->json([
				'error' => [
					'message' => "Not Data"
				]
			]);
		}

		$i = 1;
		$data = [];
		foreach($tokens as $token){
			$tokenHour = $this->contractDatabase('tokenHour');
			$result = $tokenHour->where('tokenAddress',$token['tokenAddress'])->orderBy('timestamp','desc')->offset(0)->limit(24)->get();
			$statistics = $result['data'];

			$swapAmountIn24H = "0";
			$swapAmountOut24H = "0";

			if(empty($statistics) == false && count($statistics) > 0){
				foreach($statistics as $hourData){
				    if(empty($hourData['h'])){
				        print_r($hourData);
				        exit;
                    }
					$time = strtotime("{$hourData['y']}-{$hourData['m']}-{$hourData['d']} {$hourData['h']}:00:00");
					if($time < (time() - 86400)){//해당 데이터가 일주일 이상된 데이터인 경우에는 처리하지 않음.
						//continue;
					}

					if(empty($hourData['swapAmountIn']) == true){
						$hourData['swapAmountIn'] = "0";
					}

					if(empty($hourData['swapAmountOut']) == true){
						$hourData['swapAmountOut'] = "0";
					}
					$swapAmountIn24H = bcadd($swapAmountIn24H,$this->bchexdec($hourData['swapAmountIn']));
					$swapAmountOut24H = bcadd($swapAmountOut24H,$this->bchexdec($hourData['swapAmountOut']));
				}

			}

			$tokenInfo = $this->getCacheTokenInfo($token['tokenAddress']);
			$tokenInfo['#'] = $i;
			$tokenInfo['swapAmount24H0'] = $this->bcdechex(bcadd($swapAmountIn24H,$swapAmountOut24H));

			$tokenInfo['totalMintAmount'] = empty($token['totalMintAmount'])?"0":$token['totalMintAmount'];
			$tokenInfo['totalBurnAmount'] = empty($token['totalBurnAmount'])?"0":$token['totalBurnAmount'];
			$tokenInfo['totalAmountIn'] = empty($token['totalAmountIn'])?"0":$token['totalAmountIn'];
			$tokenInfo['totalAmountOut'] = empty($token['totalAmountOut'])?"0":$token['totalAmountOut'];

			if($this->checkNetToken($token['tokenAddress']) == false){

				$netAddresses = $this->netTokenAddresses();
				foreach($netAddresses as $address){
					$result = $this->factoryContract(envDB('FACTORY_CONTRACT_ADDRESS'))->getPair($token['tokenAddress'],$address);
					if($result !== false && $result != '0x0000000000000000000000000000000000000000'){
						$reserves = $this->getReserves($result);
						$tokenInfo['lpAddress'] = $result;
						$tokenInfo['reserves'] = $reserves;
						$tokenInfo['reserves']['token0'] = $this->getCacheTokenInfo($tokenInfo['reserves']['token0']);
						$tokenInfo['reserves']['token1'] = $this->getCacheTokenInfo($tokenInfo['reserves']['token1']);
						$tokenInfo['pairSymbol'] = $this->checkNetToken($address);
						$tokenInfo['perAddress'] = $address;
						break;
					}
				}
			}

			if(empty($tokenInfo['lpAddress']) == false){
				$pairHour = $this->contractDatabase('pairHour');
				$result = $pairHour->where('lpAddress',$tokenInfo['lpAddress'])->orderBy('timestamp','desc')->offset(0)->limit(24)->get();

				foreach($result['data'] as $pairHourData){
					$time = strtotime("{$pairHourData['y']}-{$pairHourData['m']}-{$pairHourData['d']} {$pairHourData['h']}:00:00");
					if($time < (time() - 86400)){
						//continue;
					}

					$tokenInfo['prevPairHourData'] = $pairHourData;
				}
			}

			$i++;
			array_push($data,$tokenInfo);
		}


		return response()->json([
			'data' => $data
		]);
	}

	public function topPools(Request $request){
		$pairs = $this->contractDatabase('pairs');
		if(empty($pairs) == true){
			return response()->json([
				'error' => [
					'message' => "존재하지 않는 테이블"
				]
			]);
		}

		$pairHour = $this->contractDatabase('pairHour');
		if(empty($pairHour) == true){
			return response()->json([
				'error' => [
					'message' => "존재하지 않는 테이블"
				]
			]);
		}
//        if ($this->get_client_ip() == "61.74.109.18"){
//            if(empty($request->tokenAddress) == true){
//                $result = $pairs->orderBy('totalSwapCount','desc')->offset(0)->limit(30)->get();
//                $pools = $result['data'];
//                return $pools;
//            }else {
//                return 0;
//            }
//        }

//        if ($this->get_client_ip() == "61.74.109.18"){
//            return $request->all();
//        }

        $limit = empty($request->limit) ? 30 : $request->limit;
		if(empty($request->tokenAddress) == true){
            if ($this->get_client_ip() == "61.74.109.18"){
                $result = $pairs->orderBy('totalSwapCount','desc')->offset(0)->limit($limit)->get();
            }else {
                $result = $pairs->orderBy('totalSwapCount','desc')->offset(0)->limit(30)->get();
            }
//			$result = $pairs->orderBy('totalSwapCount','desc')->offset(0)->limit(30)->get();
			$pools = $result['data'];

			if(empty($pools) == true){
				return response()->json([
					'error' => [
						'message' => "Not Data"
					]
				]);
			}
		}else{
			$pairs = $this->contractDatabase('pairs');
            if ($this->get_client_ip() == "61.74.109.18"){
                $result = $pairs->where('tokenAddress0',$request->tokenAddress)->orderBy('totalSwapCount','desc')->offset(0)->limit($limit)->get();
            }else {
                $result = $pairs->where('tokenAddress0',$request->tokenAddress)->orderBy('totalSwapCount','desc')->offset(0)->limit(30)->get();
            }
//			$result = $pairs->where('tokenAddress0',$request->tokenAddress)->orderBy('totalSwapCount','desc')->offset(0)->limit(30)->get();
			$tokenAddressPools0 = empty($result['data'])?[]:$result['data'];


			$pairs = $this->contractDatabase('pairs');
            if ($this->get_client_ip() == "61.74.109.18"){
                $result = $pairs->where('tokenAddress1',$request->tokenAddress)->orderBy('totalSwapCount','desc')->offset(0)->limit($limit)->get();
            }else {
                $result = $pairs->where('tokenAddress1',$request->tokenAddress)->orderBy('totalSwapCount','desc')->offset(0)->limit(30)->get();
            }
//			$result = $pairs->where('tokenAddress1',$request->tokenAddress)->orderBy('totalSwapCount','desc')->offset(0)->limit(30)->get();
			$tokenAddressPools1 = empty($result['data'])?[]:$result['data'];



			foreach($tokenAddressPools0 as $key => $pool){
				$tokenAddressPools0[$key]['totalSwapCount'] = empty($tokenAddressPools0[$key]['totalSwapCount'])?0:$this->bchexdec($tokenAddressPools0[$key]['totalSwapCount']);
			}

			foreach($tokenAddressPools1 as $key => $pool){
				$tokenAddressPools1[$key]['totalSwapCount'] = empty($tokenAddressPools1[$key]['totalSwapCount'])?0:$this->bchexdec($tokenAddressPools1[$key]['totalSwapCount']);
			}

			$pools = array_merge($tokenAddressPools0,$tokenAddressPools1);
			for($i=0;$i<count($pools);$i++){
				$tmpCount = $pools[count($pools)-1];

				for($y=0;$y<count($pools);$y++){
					if($tmpCount['totalSwapCount'] > $pools[$y]['totalSwapCount']){
						$frontArr = array_slice($pools,0,$y);
						$backArr = array_slice($pools,$y,count($pools) - $y - 1);

						$pools = array_merge($frontArr,[$tmpCount],$backArr);
						break;
					}
				}
			}
		}

		$i = 1;
		$data = [];
		foreach($pools as $pool){
			$reserves = $this->getReserves($pool['lpAddress']);
			$token0 = $this->getCacheTokenInfo($reserves['token0']);
			$token1 = $this->getCacheTokenInfo($reserves['token1']);

			$pairHour = $this->contractDatabase('pairHour');
			$result = $pairHour->where('lpAddress',$pool['lpAddress'])->orderBy('timestamp','desc')->offset(0)->limit(168)->get();//1시간 x 168 = 7일
			$statistics = $result['data'];

			$swapAmountIn24H0 = "0";
			$swapAmountIn24H1 = "0";
			$swapAmountOut24H0 = "0";
			$swapAmountOut24H1 = "0";

			$swapAmountIn7D0 = "0";
			$swapAmountIn7D1 = "0";
			$swapAmountOut7D0 = "0";
			$swapAmountOut7D1 = "0";

			if(empty($statistics) == false && count($statistics) > 0){
				foreach($statistics as $hourData){
					$time = strtotime("{$hourData['y']}-{$hourData['m']}-{$hourData['d']} {$hourData['h']}:00:00");
					if($time < (time() - 604800)){//해당 데이터가 일주일 이상된 데이터인 경우에는 처리하지 않음.
						//continue;
					}

					if($time >= (time() - 86400)){//24시간 이내의 데이터인 경우
						$swapAmountIn24H0 = empty($hourData['swapAmountIn0'])?bcadd($swapAmountIn24H0,"0"):bcadd($swapAmountIn24H0,$this->bchexdec($hourData['swapAmountIn0']));
						$swapAmountIn24H1 = empty($hourData['swapAmountIn1'])?bcadd($swapAmountIn24H1,"0"):bcadd($swapAmountIn24H1,$this->bchexdec($hourData['swapAmountIn1']));
						$swapAmountOut24H0 = empty($hourData['swapAmountOut0'])?bcadd($swapAmountOut24H0,"0"):bcadd($swapAmountOut24H0,$this->bchexdec($hourData['swapAmountOut0']));
						$swapAmountOut24H1 = empty($hourData['swapAmountOut1'])?bcadd($swapAmountOut24H1,"0"):bcadd($swapAmountOut24H1,$this->bchexdec($hourData['swapAmountOut1']));
					}

					$swapAmountIn7D0 = empty($hourData['swapAmountIn0'])?bcadd($swapAmountIn7D0,"0"):bcadd($swapAmountIn7D0,$this->bchexdec($hourData['swapAmountIn0']));
					$swapAmountIn7D1 = empty($hourData['swapAmountIn1'])?bcadd($swapAmountIn7D1,"0"):bcadd($swapAmountIn7D1,$this->bchexdec($hourData['swapAmountIn1']));
					$swapAmountOut7D0 = empty($hourData['swapAmountOut0'])?bcadd($swapAmountOut7D0,"0"): bcadd($swapAmountOut7D0,$this->bchexdec($hourData['swapAmountOut0']));
					$swapAmountOut7D1 = empty($hourData['swapAmountOut1'])?bcadd($swapAmountOut7D1,"0"):bcadd($swapAmountOut7D1,$this->bchexdec($hourData['swapAmountOut1']));
				}

			}

			array_push($data,[
				'#' => $i,
				'lpAddress' => $pool['lpAddress'],
				'token0' => $token0,
				'token1' => $token1,
				'reserves' => $reserves,
				'swapAmount24H0' => $this->bcdechex(bcadd($swapAmountIn24H0,$swapAmountOut24H0)),
				'swapAmount24H1' =>$this->bcdechex(bcadd($swapAmountIn24H1,$swapAmountOut24H1)),
				'swapAmount7D0' => $this->bcdechex(bcadd($swapAmountIn7D0,$swapAmountOut7D0)),
				'swapAmount7D1' => $this->bcdechex(bcadd($swapAmountIn7D1,$swapAmountOut7D1)),
			]);
			$i++;
		}

		return response()->json([
			'data' => $data
		]);
	}

    public function top5Pools(Request $req){
        return $this->get_client_ip();
    }

}
