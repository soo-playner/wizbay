<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use BlockSDK;

use App\Models\ContractDB;
use Illuminate\Support\Str;

class FarmController extends Controller
{
	
	public function tokenApr(){
		if(envDB('BASE_MAINNET') == "ETH"){
			$annualBlock = 2102400;//이더는 15초 마다 1블록 생성
		}else if(envDB('BASE_MAINNET') == "BSC"){
			$annualBlock = 10512000;//스마트 체인은 3초 마다 1블록 생성
		}else if(envDB('BASE_MAINNET') == "KLAY"){
			$annualBlock = 31536000;//클레이튼은 1초 마다 1블록 생성
		}
		
		$farm = $this->farmContract(envDB('STAKING_CONTRACT_ADDRESS'));
		
		$poolInfo = $farm->poolInfo(0);
		if($poolInfo === false){
			return response()->json([
				'error' => [
					'message' => "Not Data"
				]
			]);
		}
		
		$tokenInfo = $this->getCacheTokenInfo($poolInfo['lpAddress']);
		if(empty($tokenInfo['name']) == true){
			return response()->json([
				'error' => [
					'message' => "Not Token"
				]
			]);
		}
		
		$totalAllocPoint = $farm->totalAllocPoint();
		$dexPerBlock = $farm->dexPerBlock();
//	    print_r($totalAllocPoint);
//		exit;

		$rate = $poolInfo['allocPoint'] / $totalAllocPoint * 100;
		
		$rewardPerBlock = $dexPerBlock * $rate / 100;
		for($i=0;$i < $tokenInfo['decimals'];$i++){
			$rewardPerBlock = $rewardPerBlock / 10;
		}
		
		$payload = $this->getTokenBalance($poolInfo['lpAddress'],envDB('STAKING_CONTRACT_ADDRESS'));
		$stakedAmount = $payload['balance'];    // 0이 들어옴


        $annualReward = $rewardPerBlock * $annualBlock;

//		$apr =  $annualReward / $stakedAmount;
		$apr = $stakedAmount != 0 ? $annualReward / $stakedAmount : 0;
		return response()->json([
			'data' => $apr
		]);		
	}
	
	public function farmApr(Request $request,$lpAddress){
		if($lpAddress === 0){

			return $this->tokenApr();
		}

		$reserves = $this->getReserves($lpAddress);


		if(empty($reserves) == true){
			return response()->json([
				'error' => [
					'message' => "Not Data"
				]
			]);
		}
		
		$contractDB = ContractDB::find('pairHour');
		$totalSwapAmountIn0 = "0";
		$totalSwapAmountIn1 = "0";
		for($i=0;$i<7;$i++){    // 7일 기준
			$time = time() - (86400*$i);
			$y = date('Y',$time);
			$m = date('n',$time);
			$d = date('d',$time);


            $result = $this->contractDB()->selected([
                'table_id' => $contractDB->table_id,
                'where' => [
                    [
                        'columnName' => "lpAddress",
                        'value' => $lpAddress
                    ],
                    [
                        'columnName' => "y",
                        'value' => "" . $y
                    ],
                    [
                        'columnName' => "m",
                        'value' => "" . $m
                    ],
                    [
                        'columnName' => "d",
                        'value' => "" . (int)$d
                    ]
                ],
                'offset' => 0,
                'limit' => 100,
            ]);


            if(empty($result['payload']['data']) == true || count($result['payload']['data']) == 0){
				continue;
			}


			foreach($result['payload']['data'] as $data){

				if (isset($data['swapAmountIn0'])) $totalSwapAmountIn0 = bcadd($totalSwapAmountIn0, $this->bchexdec($data['swapAmountIn0']));
				if (isset($data['swapAmountIn1'])) $totalSwapAmountIn1 = bcadd($totalSwapAmountIn1, $this->bchexdec($data['swapAmountIn1']));
			}

		}

//		$rate0 = $totalSwapAmountIn0 / $reserves['reserve0'] * 100;
//		$rate1 = $totalSwapAmountIn1 / $reserves['reserve1'] * 100;

//        dump($totalSwapAmountIn0);
//        dump($totalSwapAmountIn1);
//        dump($reserves['reserve0']);
//        dump($reserves['reserve1']);


        $rate0 = bcmul(bcdiv($totalSwapAmountIn0, $reserves['reserve0'],8), "100", 8);
        $rate1 = bcmul(bcdiv($totalSwapAmountIn1, $reserves['reserve1'],8), "100", 8);
//        dump(bcmul(bcdiv($totalSwapAmountIn0, $reserves['reserve0'],8), "100", 8));
//        dump(bcmul(bcdiv($totalSwapAmountIn1, $reserves['reserve1'],8), "100", 8));
//        $rate0 = 0.17 * $rate0 / 100;
//		$rate1 = 0.17 * $rate1 / 100;

		$rate0 = bcmul("0.17", bcdiv($rate0, "100", 8), 8);
		$rate1 = bcmul("0.17", bcdiv($rate1, "100", 8), 8);
//        dump($rate0);
//        dd($rate1);
//        exit;
        //apy = (일간 이자율+APR/100)^365
        $apr = ($rate0+$rate1) / 7 * 365;
        $apy = bcsub(bcpow(bcadd(bcdiv($apr, '365', 16), 1, 16), '365', 16), 1, 16);
        // 0.6082726543
        // 0.006082726543 = /100
        // APY = [1 + (APR / 주기수)]^(주기수) - 1
        // 1.001661947143
        // 0.8332808714
		return response()->json([
			'data' => ($rate0+$rate1) / 7 * 365,
            'apr' => $apr,
            'apy' => $apy,
		]);
	}
	
	public function farmInfo(Request $request,$pullID){
		$data = $this->farmContract(envDB('STAKING_CONTRACT_ADDRESS'))->info($pullID);
		
		if($data === false){
			return response()->json([
				'error' => [
					'message' => "Not Data"
				]
			]);
		}
	
		return response()->json([
			'data' => $data
		]);
	}	
	
	public function farmEarned(Request $request,$pullID){
		$data = $this->farmContract(envDB('STAKING_CONTRACT_ADDRESS'))->earned($pullID,$request->address);
			
		if($data === false){
			return response()->json([
				'error' => [
					'message' => "Not Data"
				]
			]);
		}
		
		return response()->json([
			'data' => $data
		]);
	}
	
	public function farmUserInfo(Request $request,$pullID){
		$data = $this->farmContract(envDB('STAKING_CONTRACT_ADDRESS'))->userInfo($pullID,$request->address);
		
		if(empty($data) == true){
			return response()->json([
				'error' => [
					'message' => "Not Data"
				]
			]);
		}
		
		return response()->json([
			'data' => $data
		]);
	}

    /**
     * Staked Only Data
     * @param Request $req
     * @return \Illuminate\Http\JsonResponse
     */
    public function only(Request $req, $fromAddress){

        // 새로운 파라미터값을 받아서 내가 스테이킹한 것중 완료된 것만 리스트로 뽑히게 하나 파줘야됨

        /*
         *
         *
         * $tokenHour = $this->contractDatabase('farms');
            if (isset($request->method) && $request->method == "done"){
                $result = $tokenHour->where('allocPoint', 0 )->orderBy('pid','desc')->offset(0)->limit(200)->get();
            }
         *
         */
        $contractDB = ContractDB::find('farmGenerator');
        $farms = ContractDB::find('farms');

//        dump($contractDB);
//        dd($farms);
        if(empty($req->fromAddress) == true){
            return response()->json([
                'error' => [
                    'message' => "Not Address"
                ]
            ]);
        }

        $pid = $this->contractDB()->selected([
            'table_id' => $contractDB->table_id,
            'where' => [
                [
                    'columnName' => "fromAddress",
                    'value' => Str::lower($fromAddress)
                ]
            ],
            'offset' => 0,
            'limit' => 200,
        ]);

        if(empty($pid['payload']['data']) == true){
            return response()->json([
                'error' => [
                    'message' => "Not Data"
                ]
            ]);
        }

        $res = []; // 팜 목록
        foreach ($pid['payload']['data'] as $key => $val){
            $p_id = sprintf('%064s', substr($val['pid'], 2)); // 0x제외 -> 64글자 맞춰주기

            // 조건 추가 allocPoint 가 0 이면 완료된 것
            $result = $this->contractDB()->selected([
                'table_id' => $farms->table_id,
                'where' => [
                    [
                        'columnName' => "pid",
                        'value' => $p_id
                    ]
                ],
                'offset' => 0,
                'limit' => 200,
            ]);

            if(empty($result['payload']['data']) == true){

                return response()->json([
                    'error' => [
                        'message' => "Not Data"
                    ]
                ]);
            }

            $tokenAddress0 = $this->getCacheToken0($result['payload']['data'][0]['lpAddress']);
            $tokenAddress1 = $this->getCacheToken1($result['payload']['data'][0]['lpAddress']);

            $lpInfo = $this->getCacheTokenInfo($result['payload']['data'][0]['lpAddress']);

            $tokenInfo0 = $this->getCacheTokenInfo($tokenAddress0);
            $tokenInfo1 = $this->getCacheTokenInfo($tokenAddress1);

            $reserves = $this->getCacheReserves($result['payload']['data'][0]['lpAddress']);
            $farm = $result['payload']['data'][0];
            $farm['address'] = $result['payload']['data'][0]['lpAddress'];
            $farm['lpInfo'] = $lpInfo;
            $farm['tokenInfo0'] = $tokenInfo0;
            $farm['tokenInfo1'] = $tokenInfo1;
            $farm['reserves'] = $reserves;

            if (isset($req->is_done) && $req->is_done == "done"){
                if($farm['allocPoint'] == "0000000000000000000000000000000000000000000000000000000000000000"){
                    array_push($res,$farm);
                }
            }else {
                array_push($res,$farm);
            }

        }

        return response()->json([
            'data' => $res,
            'total'=> count($res)
        ]);
    }
		
}