<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

use BlockSDK;

use App\Models\ContractDB;
use App\Models\CacheData;
use App\Models\Staking;


class StakingController extends Controller
{
	
	public function apr(Request $request,$contractAddress){

		if(envDB('BASE_MAINNET') == "ETH"){
			$annualBlock = 2102400;//이더는 15초 마다 1블록 생성
		}else if(envDB('BASE_MAINNET') == "BSC"){
			$annualBlock = 105122000;//스마트 체인은 3초 마다 1블록 생성
		}else if(envDB('BASE_MAINNET') == "KLAY"){
			$annualBlock = 31536000;//클레이튼은 1초 마다 1블록 생성
		}

		$staking = $this->stakingContract($contractAddress);

		$stakedToken 	= $staking->stakedToken();
		$rewardToken 	= $staking->rewardToken();
		$rewardPerBlock = $staking->rewardPerBlock();

        //페어 쌍 주소 가져오기
        $lpAddress		= $this->factoryContract(envDB('FACTORY_CONTRACT_ADDRESS'))->getPair($stakedToken,$rewardToken); // 0x00000...

        if ($lpAddress == "0x0000000000000000000000000000000000000000"){
            return response()->json([
               'error' => "I can't find liquidity.",
            ]);
        }

        // 두 토큰의 유동성 양
        $reserves		= $this->getReserves($lpAddress); //

        $reserves['token0'] = $this->getCacheTokenInfo($reserves['token0']);
		if(empty($reserves['token0']['name']) == true){
			return response()->json([
				'error' => [
					'message' => "Not Token"
				]
			]);
		}


		$reserves['token1'] = $this->getCacheTokenInfo($reserves['token1']);
		if(empty($reserves['token0']['name']) == true){
			return response()->json([
				'error' => [
					'message' => "Not Token"
				]
			]);
		}

		if($reserves['token0']['address'] == $stakedToken){
//			$rate = $reserves['reserve1'] / $reserves['reserve0'] * 100;
//			$rate2 = bcdiv($reserves['reserve1'], bcmul($reserves['reserve0'], "100", 18), 18);
			$rate = bcmul(bcdiv($reserves['reserve1'], $reserves['reserve0'], 18), "100", 18);
			for($i=0;$i < $reserves['token1']['decimals'];$i++){
//				$rewardPerBlock = $rewardPerBlock / 10;
                $rewardPerBlock = bcdiv($rewardPerBlock, "10", 18);
			}
		}else{
//			$rate = $reserves['reserve0'] / $reserves['reserve1'] * 100;
//            $rate2 = bcdiv($reserves['reserve0'], bcmul($reserves['reserve1'], "100", 18), 18);
            $rate = bcmul(bcdiv($reserves['reserve0'], $reserves['reserve1'],18), "100", 18);
			for($i=0;$i < $reserves['token0']['decimals'];$i++){
//				$rewardPerBlock = $rewardPerBlock / 10;
                $rewardPerBlock = bcdiv($rewardPerBlock, "10", 18);
			}
		}
//		$rewardToStakedAmount = $rewardPerBlock * $rate / 100;//보상 토큰수를 스테이킹한 토큰으로 환산하였을때 양
//        $rewardToStakedAmount = bcmul($rewardPerBlock, bcdiv($rate, "100", 18), 18);
        $rewardToStakedAmount = bcdiv(bcmul($rewardPerBlock, $rate, 18), "100", 18);

//		$annualReward = $rewardToStakedAmount * $annualBlock;//스테이킹 토큰 기준으로 연간 보상 총 개수
        $annualReward = bcmul($rewardToStakedAmount, $annualBlock, 18);

		$payload = $this->getTokenBalance($stakedToken,$contractAddress);
		$stakedAmount = $payload['strBalance'];
//		$stakedAmount = $payload['balance'];

        $apr = '0';
        if($stakedAmount > 0){
            $apr = bcdiv((string)$annualReward , (string)$stakedAmount, 18);
        }



		return response()->json([
			'data' => $apr,
            'reserves0' => $reserves['reserve0'],
            'reserves1' => $reserves['reserve1'],
            'rate' => $rate,
            'rewardPerBlock' => $rewardPerBlock,
            'amount' => $stakedAmount,
            'testRewardToStakedAmount' => bcdiv(bcmul("0.0000000001000000", $rate, 18), "100", 18),
            'testAnnualReward' => bcmul(bcdiv(bcmul("0.0000000001000000", $rate, 18), "100", 18), $annualBlock, 18),
            'testAPR' => bcdiv(bcmul(bcdiv(bcmul("0.0000000001000000", $rate, 18), "100", 18), $annualBlock, 18), 1, 18)
		]);

	}
	
	public function earned(Request $request,$contractAddress){
		if(empty($request->address) == true){
			return response()->json([
				'error' => [
					'message' => "address"
				]
			]);
		}
		
		$data = $this->stakingContract($contractAddress)->earned($request->address);
		
		if($data === false){
			return response()->json([
				'error' => [
					'message' => "not data"
				]
			]);
		}
		
		return response()->json([
			'data' => $data
		]);
	}
	
	public function userInfo(Request $request,$contractAddress){
		if(empty($request->address) == true){
			return response()->json([
				'error' => [
					'message' => "address"
				]
			]);
		}
		
		$data = $this->stakingContract($contractAddress)->userInfo($request->address);
		
		if($data === false){
			return response()->json([
				'error' => [
					'message' => ""
				]
			]);
		}
		
		return response()->json([
			'data' => $data
		]);
	}
	
	public function live(Request $request){

		$offset = empty($request->offset)?0:$request->offset;
        $limit = empty($request->limit)?10:$request->limit;

        $result = $this->netClient()->getBlockChain();

        if ($result['state']['success'] != true){
            return response()->json([
                'error' => [
                    'message' => "API BLOCK INFO ERROR"
                ]
            ]);
        }
        $last_block_height = $result['payload']['last_block_height'];


        $stakings = Staking::where('is_end',0)->orderBy('id','desc')->get();

        $data = [];
		foreach($stakings as $staking){
            if($last_block_height > $staking->end_block){
                $staking->is_end = 1;
                $staking->save();
                continue;
            }
			$stakedInfo = $this->getCacheTokenInfo($staking->staked_token);
			$rewardInfo = $this->getCacheTokenInfo($staking->reward_token);
			
			$result = $staking;
			$result['staked'] = $stakedInfo;
			$result['reward'] = $rewardInfo;
			array_push($data,$result);
		}
		
		//
		return response()->json([
			'data' => $data,
			'total' => Staking::where('is_end',0)->count()
		]);
	}

    /**
     *
     * @param Request $request
     */
	public function finished(Request $request){
	    //
        $result = $this->netClient()->getBlockChain();
        if ($result['state']['success'] != true){
            return response()->json([
                'error' => [
                    'message' => "API BLOCK INFO ERROR"
                ]
            ]);
        }
        $last_block_height = $result['payload']['last_block_height'];

        $offset = empty($request->offset)?0:$request->offset;
        $limit = empty($request->limit)?10:$request->limit;


        $stakings = Staking::all();
        $resData = [];
        foreach ($stakings as $key => $val){
            if($last_block_height < $val->end_block){
                continue;
            }
            $val->is_end = 1;
            $val->save();

            $stakedInfo = $this->getCacheTokenInfo($val->staked_token);
            $rewardInfo = $this->getCacheTokenInfo($val->reward_token);

            $result = $val;
            $result['staked'] = $stakedInfo;
            $result['reward'] = $rewardInfo;
            array_push($resData, $result);
        }


        return response()->json([
            'data' => $resData,
            'total' => Staking::where('is_end',1)->count()
        ]);
	}




    /**
     * Staked Only Data
     * @param Request $req
     * @return \Illuminate\Http\JsonResponse
     */
	public function only(Request $req, $fromAddress){

	    // 새로운 파라미터값을 받아서 내가 스테이킹한 것중 완료된 것만 리스트로 뽑히게 하나 파줘야됨
        if (empty($req->is_done) == false && $req->is_done == "done"){
            $stakings = Staking::where('is_end',1)->orderBy('id','desc')->get();
        }else {
            $stakings = Staking::where('is_end',0)->orderBy('id','desc')->get();
        }

//        $offset = empty($request->offset)?0:$request->offset;
//        $limit = empty($request->limit)?10:$request->limit;

//        $stakings = Staking::where('is_end',0)->orderBy('id','desc')->get();

        $resData = [];
        foreach ($stakings as $key => $val){
            $data = $this->netClient()->getContractRead([
                'contract_address' => $val->contract_address,
                'method' => 'userInfo',
                'return_type' => 'uint256',
                'parameter_type' => ['address'],
                'parameter_data' => [Str::lower($fromAddress)]
            ]);

            if ($data['state']['success'] != true || empty($data['payload']) == true){
                return response()->json([
                    'error' => [
                        'message' => "Not Data"
                    ]
                ]);
            }

            $offset = 2;
            $length = 64;
            $temp = $val;
            $temp['staked'] = $this->getCacheTokenInfo($val->staked_token);
            $temp['reward'] = $this->getCacheTokenInfo($val->reward_token);
            $temp['amount'] = $this->bchexdec(substr($data['payload']['hex'], $offset, $length)); $offset = $offset+$length;
            $temp['rewardDebt'] = $this->bchexdec(substr($data['payload']['hex'], $offset, $offset + $length));
            if ($temp['amount'] == 0){
                continue;
            }
            $resData[] = $temp;

        }

//        return $resData;
        return response()->json([
            'data' => $resData,
            'total'=> Staking::where('is_end',0)->count()
        ]);
    }

    public function netClient(){
        if(envDB('BASE_MAINNET') == 'ETH')
            return BlockSDK::createEthereum(envDB('BLOCKSDK_TOKEN'));
        if(envDB('BASE_MAINNET') == 'BSC')
            return BlockSDK::createBinanceSmart(envDB('BLOCKSDK_TOKEN'));
        if(envDB('BASE_MAINNET') == 'KLAY')
            return BlockSDK::createKlaytn(envDB('BLOCKSDK_TOKEN'));
    }

}