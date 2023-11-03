<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Symfony\Component\Config\Definition\Exception\Exception;


class InfoController extends Controller
{
	
	public function poolTxs(Request $request,$lpAddress){
		$contractDatabase = $this->contractDatabase('allTxs');
		if(empty($contractDatabase) == true){
			return response()->json([
				'error' => [
					'message' => "존재하지 않는 테이블"
				]
			]);
		}
		
		
		$result = $contractDatabase->where('lpAddress',$lpAddress)->orderBy('timestamp','desc')->offset(0)->limit(30)->get();
		$txs = $result['data'];

		$pair = $this->getReserves($lpAddress);
		$data = [];
		foreach($txs as $tx){
			
			$token0 = $this->getCacheTokenInfo($pair['token0']);
			$token1 = $this->getCacheTokenInfo($pair['token1']);
			
			array_push($data,[
				'tx_hash' => $tx['txHash'],
				'type' => $tx['type'],
				'token0' => $token0,
				'token1' => $token1,
				'tokenAmount0' => $tx['tokenAmount0'],
				'tokenAmount1' => $tx['tokenAmount1'],
				'timestamp' 	=> $tx['timestamp'],
			]);
		}
		
		return response()->json([
			'data' => $data
		]);
	}
	
	public function tokenPrices(Request $request,$tokenAddress){
		$netAddresses = $this->netTokenAddresses();
		foreach($netAddresses as $address){
			$lpAddress = $this->factoryContract(envDB('FACTORY_CONTRACT_ADDRESS'))->getPair($tokenAddress,$address);
			if($lpAddress == false || $lpAddress == '0x0000000000000000000000000000000000000000'){
				continue;
			}
			
			$symbol = $this->checkNetToken($address);
			$reserves = $this->getReserves($lpAddress);
			
			if($this->checkNetToken($reserves['token0']) !== false){
				$rate = bcdiv($reserves['reserve0'],$reserves['reserve1'],8);
			}else{
				$rate = bcdiv($reserves['reserve1'],$reserves['reserve0'],8);
			}
			
			break;
		}
		
		$pairHour = $this->contractDatabase('pairHour');
		if(empty($pairHour) == true){
			return response()->json([
				'error' => [
					'message' => "존재하지 않는 테이블"
				]
			]);
		}
		
		$result = $pairHour->where('lpAddress',$lpAddress)->orderBy('timestamp','desc')->offset(0)->limit(168)->get();//1시간 x 168 = 7일
		$statistics = $result['data'];
		
		if(empty($statistics) == true){
			return response()->json([
				'error' => [
					'message' => "Not Data"
				]
			]);
		}
		
		$rate24Hs = [];
		foreach($statistics as $hourData){
			$hourData['y'] = $this->bchexdec($hourData['y']);
			$hourData['m'] = $this->bchexdec($hourData['m']);
			$hourData['d'] = $this->bchexdec($hourData['d']);
			
			$time = strtotime("{$hourData['y']}-{$hourData['m']}-{$hourData['d']}");
			$date = date("Y-m-d",$time);
			
			if($time < (time() - 86400)){//해당 데이터가 일주일 이상된 데이터인 경우에는 처리하지 않음.
				//continue;
			}else if(empty($hourData['reserve0']) == true || empty($hourData['reserve1']) == true){
				continue;
			}
			
			if($this->checkNetToken($reserves['token0']) !== false){
				$rate24Hs[$date] = bcdiv($this->bchexdec($hourData['reserve0']),$this->bchexdec($hourData['reserve1']),8);
			}else{
				$rate24Hs[$date] = bcdiv($this->bchexdec($hourData['reserve1']),$this->bchexdec($hourData['reserve0']),8);
			}
		}
		
		$price24Hs = [];
		for($i=0;$i<60;$i++){
			$date = date("Y-m-d",time() - (86400 * $i));
			
			if($i == 0){
				$price24Hs[$symbol] = [];
				$price24Hs[$symbol][$date] = $rate;
			}else{
				$nextDate = date("Y-m-d",time() - (86400 * ($i-1)));
				
				$nextAmount = $price24Hs[$symbol][$nextDate];
				if(empty($rate24Hs[$date]) == true){
					$price24Hs[$symbol][$date] = $nextAmount;
				}else{
					$price24Hs[$symbol][$date] = $rate24Hs[$date];
				}
				
			}
		}
		
		return response()->json([
			'data' => $price24Hs
		]);
	}
	
	public function tokenLiquidity24H(Request $request,$tokenAddress){
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
		
		$result = $tokenHour->where('tokenAddress',$tokenAddress)->orderBy('timestamp','desc')->offset(0)->limit(168)->get();//1시간 x 168 = 7일
		$statistics = $result['data'];
		
		if(empty($statistics) == true){
			return response()->json([
				'error' => [
					'message' => "Not Data"
				]
			]);
		}

		
		$amount24Hs = [];
		foreach($statistics as $hourData){
			$hourData['y'] = $this->bchexdec($hourData['y']);
			$hourData['m'] = $this->bchexdec($hourData['m']);
			$hourData['d'] = $this->bchexdec($hourData['d']);
			
			$time = strtotime("{$hourData['y']}-{$hourData['m']}-{$hourData['d']}");
			$date = date("Y-m-d",$time);
			
			if($time < (time() - 86400)){//해당 데이터가 일주일 이상된 데이터인 경우에는 처리하지 않음.
				//continue;
			}
			
			if(empty($amount24Hs[$date]) == true){
				$amount24Hs[$date] = [];
				$amount24Hs[$date]['swapAmountIn'] = "0";
				$amount24Hs[$date]['swapAmountOut'] = "0";
				$amount24Hs[$date]['totalMintAmount'] = "0";
				$amount24Hs[$date]['totalBurnAmount'] = "0";
			}
			
			if(empty($hourData['swapAmountIn']) == true){
				$hourData['swapAmountIn'] = "0";
			}
			if(empty($hourData['swapAmountOut']) == true){
				$hourData['swapAmountOut'] = "0";
			}
			if(empty($hourData['totalMintAmount']) == true){
				$hourData['totalMintAmount'] = "0";
			}
			if(empty($hourData['totalBurnAmount']) == true){
				$hourData['totalBurnAmount'] = "0";
			}
			
			$amount24Hs[$date]['swapAmountIn'] = bcadd($amount24Hs[$date]['swapAmountIn'],$this->bchexdec($hourData['swapAmountIn']));
			$amount24Hs[$date]['swapAmountOut'] = bcadd($amount24Hs[$date]['swapAmountOut'],$this->bchexdec($hourData['swapAmountOut']));
			$amount24Hs[$date]['totalMintAmount'] = bcadd($amount24Hs[$date]['totalMintAmount'],$this->bchexdec($hourData['totalMintAmount']));
			$amount24Hs[$date]['totalBurnAmount'] = bcadd($amount24Hs[$date]['totalBurnAmount'],$this->bchexdec($hourData['totalBurnAmount']));
		}
		
		$symbol = $this->checkNetToken($tokenAddress);
		if($symbol === false){
			$netAddresses = $this->netTokenAddresses();
			foreach($netAddresses as $address){
				$result = $this->factoryContract(envDB('FACTORY_CONTRACT_ADDRESS'))->getPair($tokenAddress,$address);
				if($result == false || $result == '0x0000000000000000000000000000000000000000'){
					continue;
				}
				
				$reserves = $this->getReserves($result);
				if($this->checkNetToken($reserves['token0']) !== false){
					$rate = bcdiv($reserves['reserve0'],$reserves['reserve1']);
				}else{
					$rate = bcdiv($reserves['reserve1'],$reserves['reserve0'],8);
				}

				$symbol = $this->checkNetToken($address);
				break;
			}
		}else{
			$rate = "1";
		}
		
		$result = $tokens->where('tokenAddress',$tokenAddress)->offset(0)->limit(1)->get();
		if(empty($result['data']) == true || count($result['data']) == 0){
			return response()->json([
				'error' => [
					'message' => "Not Data"
				]
			]);
		}
		
		// (totalMintAmount + totalAmountIn) - (totalBurnAmount + totalAmountOut)
		$token =  $result['data'][0];
		
		$tokenLiquidity = "0";
		if(empty($token['totalMintAmount']) == false){
			$tokenLiquidity = bcadd($tokenLiquidity,$this->bchexdec($token['totalMintAmount']));
		}
		if(empty($token['totalAmountIn']) == false){
			$tokenLiquidity = bcadd($tokenLiquidity,$this->bchexdec($token['totalAmountIn']));
		}
		if(empty($token['totalBurnAmount']) == false){
			$tokenLiquidity = bcsub($tokenLiquidity,$this->bchexdec($token['totalBurnAmount']));
		}
		if(empty($token['totalAmountOut']) == false){
			$tokenLiquidity = bcsub($tokenLiquidity,$this->bchexdec($token['totalAmountOut']));
		}
		
		$liquidity24Hs = [];
		for($i=0;$i<60;$i++){
			$date = date("Y-m-d",time() - (86400 * $i));
			
			if($i == 0){
				$liquidity24Hs[$symbol] = [];
				$liquidity24Hs[$symbol][$date] = $tokenLiquidity;
			}else{
				$nextDate = date("Y-m-d",time() - (86400 * ($i-1)));
				
				$prevAmount = $liquidity24Hs[$symbol][$nextDate];
				if(empty($amount24Hs[$date]) == true){
					$liquidity24Hs[$symbol][$date] = $prevAmount;
				}else{
					//날짜 역순 계산이기 때문에 발행 , 소각 등의 계산을 반대로 진행
					$nextAmount = bcadd($prevAmount,$amount24Hs[$date]['swapAmountOut']);
					$nextAmount = bcadd($nextAmount,$amount24Hs[$date]['totalBurnAmount']);
					$nextAmount = bcsub($nextAmount,$amount24Hs[$date]['swapAmountIn']);
					$nextAmount = bcsub($nextAmount,$amount24Hs[$date]['totalMintAmount']);
					if(substr($nextAmount,0,1) == "-"){
						$nextAmount = $prevAmount;
					}
					$liquidity24Hs[$symbol][$date] = $nextAmount;
				}
				
			}
		}
		
		foreach($liquidity24Hs[$symbol] as $date => $data){
			$liquidity24Hs[$symbol][$date] = bcmul($data,$rate); 
		}
		
		return response()->json([
			'data' => $liquidity24Hs
		]);
	}
	
	public function tokenVolume24H(Request $request,$tokenAddress){
		$tokenHour = $this->contractDatabase('tokenHour');
		if(empty($tokenHour) == true){
			return response()->json([
				'error' => [
					'message' => "존재하지 않는 테이블"
				]
			]);
		}
		
		$result = $tokenHour->where('tokenAddress',$tokenAddress)->orderBy('timestamp','desc')->offset(0)->limit(168)->get();//1시간 x 168 = 7일
		$statistics = $result['data'];
		
		if(empty($statistics) == true){
			return response()->json([
				'error' => [
					'message' => "Not Data"
				]
			]);
		}

		
		$swap24Hs = [];
		foreach($statistics as $hourData){
			$hourData['y'] = $this->bchexdec($hourData['y']);
			$hourData['m'] = $this->bchexdec($hourData['m']);
			$hourData['d'] = $this->bchexdec($hourData['d']);
			
			$time = strtotime("{$hourData['y']}-{$hourData['m']}-{$hourData['d']}");
			$date = date("Y-m-d",$time);
			
			if($time < (time() - 86400)){//해당 데이터가 일주일 이상된 데이터인 경우에는 처리하지 않음.
				//continue;
			}
			
			if(empty($swap24Hs[$date]) == true){
				$swap24Hs[$date] = "0";
			}
			
			if(empty($hourData['swapAmountIn']) == true){
				$hourData['swapAmountIn'] = "0";
			}
			if(empty($hourData['swapAmountOut']) == true){
				$hourData['swapAmountOut'] = "0";
			}

			
			$swap24Hs[$date]= bcadd($swap24Hs[$date],$this->bchexdec($hourData['swapAmountIn']));
			$swap24Hs[$date]= bcadd($swap24Hs[$date],$this->bchexdec($hourData['swapAmountOut']));
		}
		
		$symbol = $this->checkNetToken($tokenAddress);
		if($symbol === false){
			$netAddresses = $this->netTokenAddresses();
			foreach($netAddresses as $address){
				$result = $this->factoryContract(envDB('FACTORY_CONTRACT_ADDRESS'))->getPair($tokenAddress,$address);
				if($result == false || $result == '0x0000000000000000000000000000000000000000'){
					continue;
				}
				
				$reserves = $this->getReserves($result);
				if($this->checkNetToken($reserves['token0']) !== false){
					$rate = bcdiv($reserves['reserve0'],$reserves['reserve1']);
				}else{
					$rate = bcdiv($reserves['reserve1'],$reserves['reserve0'],8);
				}

				$symbol = $this->checkNetToken($address);
				break;
			}
		}else{
			$rate = "1";
		}
		
		
		$volume24Hs = [];
		for($i=0;$i<60;$i++){
			$date = date("Y-m-d",time() - (86400 * $i));
			

			if(empty($swap24Hs[$date]) == true){
				$volume24Hs[$symbol][$date] = "0";
			}else{
				$volume24Hs[$symbol][$date] =  bcmul($swap24Hs[$date],$rate);
			}
		
		}
		

		return response()->json([
			'data' => $volume24Hs
		]);
	}
	
	public function poolLiquidity24H(Request $request,$lpAddress){
		$pairHour = $this->contractDatabase('pairHour');
		if(empty($pairHour) == true){
			return response()->json([
				'error' => [
					'message' => "존재하지 않는 테이블"
				]
			]);
		}
		
		$result = $pairHour->where('lpAddress',$lpAddress)->orderBy('timestamp','desc')->offset(0)->limit(168)->get();//1시간 x 168 = 7일
		$statistics = $result['data'];
		
		if(empty($statistics) == true){
			return response()->json([
				'error' => [
					'message' => "Not Data"
				]
			]);
		}

		$tokenAddress0 = $this->getToken0($lpAddress);
		$tokenAddress1 = $this->getToken1($lpAddress);

		$tokenSymbol0 = $this->checkNetToken($tokenAddress0);
		$tokenSymbol1 = $this->checkNetToken($tokenAddress1);
		if($tokenSymbol0 == false && $tokenSymbol1 == false){
			$netAddresses = $this->netTokenAddresses();
			foreach($netAddresses as $address){
				$result = $this->factoryContract(envDB('FACTORY_CONTRACT_ADDRESS'))->getPair($tokenAddress0,$address);
				if($result !== false && $result != '0x0000000000000000000000000000000000000000'){
					$pricePairAddress = $result;
					$select = "token0";
					$pricePairSymbol = $this->checkNetToken($address);
					break;
				}
				
				$result = $this->factoryContract(envDB('FACTORY_CONTRACT_ADDRESS'))->getPair($tokenAddress1,$address);
				if($result !== false && $result != '0x0000000000000000000000000000000000000000'){
					$pricePairAddress = $result;
					$select = "token1";
					$pricePairSymbol = $this->checkNetToken($address);
					break;
				}
			}
			
			if(empty($pricePairAddress) == false){
				$reserves = $this->getReserves($pricePairAddress);
				if($this->checkNetToken($reserves['token0']) !== false){
					$pricePairRate = $reserves['reserve1'] / $reserves['reserve0'];
				}else{
					$pricePairRate = $reserves['reserve0'] / $reserves['reserve1'];
				}
			}
		}else{
			$reserves = $this->getReserves($lpAddress);
		}


        $liquidity24Hs = [];
		foreach($statistics as $hourData){
			$hourData['y'] = $this->bchexdec($hourData['y']);
			$hourData['m'] = $this->bchexdec($hourData['m']);
			$hourData['d'] = $this->bchexdec($hourData['d']);
			
			$time = strtotime("{$hourData['y']}-{$hourData['m']}-{$hourData['d']}");
			$date = date("Y-m-d",$time);
			
			if($time < (time() - 86400)){//해당 데이터가 일주일 이상된 데이터인 경우에는 처리하지 않음.
				//continue;
			}
			
			if(empty($hourData['reserve0']) == true || empty($hourData['reserve1'])){
				continue;
			}
			
			if($tokenSymbol0 != false){
				if(empty($liquidity24Hs[$tokenSymbol0][$date]) == true){
					$liquidity24Hs[$tokenSymbol0][$date] = "0";
				}
			
				$liquidity24Hs[$tokenSymbol0][$date] = bcadd($liquidity24Hs[$tokenSymbol0][$date],$this->bchexdec($hourData['reserve0']));
			}else if($tokenSymbol1 != false){
				if(empty($liquidity24Hs[$tokenSymbol1][$date]) == true){
					$liquidity24Hs[$tokenSymbol1][$date] = "0";
				}
				
				$liquidity24Hs[$tokenSymbol1][$date] = bcadd($liquidity24Hs[$tokenSymbol1][$date],$this->bchexdec($hourData['reserve1']));
			}else{
				if(empty($pricePairRate) == true){
					continue;
				}else if(empty($liquidity24Hs[$pricePairSymbol]) == true){
                    $liquidity24Hs[$pricePairSymbol] = [];
                }
				if (empty($liquidity24Hs[$pricePairSymbol][$date]) == true){
                    $liquidity24Hs[$pricePairSymbol][$date] = "0";
                }
				if($select == "token0"){
					$mul = bcmul($this->bchexdec($hourData['reserve0']),$pricePairRate);
				}else{
					$mul = bcmul($this->bchexdec($hourData['reserve1']),$pricePairRate);
				}

				$liquidity24Hs[$pricePairSymbol][$date] = bcadd($liquidity24Hs[$pricePairSymbol][$date],$mul);
			}
		}
		
		if($tokenSymbol0 != false){
			$symbol = $tokenSymbol0;
		}else if($tokenSymbol1 != false){
			$symbol = $tokenSymbol1;
		}else if(empty($pricePairSymbol) == false){
			$symbol = $pricePairSymbol;
		}
		
		$todayDate = date("Y-m-d");
		if(empty($liquidity24Hs[$symbol][$todayDate]) == true){
			if($tokenSymbol0 != false){
				$liquidity24Hs[$symbol][$todayDate] = $reserves['reserve0'];
			}else if($tokenSymbol1 != false){
				$liquidity24Hs[$symbol][$todayDate] = $reserves['reserve1'];
			}else{
				if($this->checkNetToken($reserves['token0']) !== false){
					$liquidity24Hs[$symbol][$todayDate] = $reserves['reserve0'];
				}else if($this->checkNetToken($reserves['token1']) !== false){
					$liquidity24Hs[$symbol][$todayDate] = $reserves['reserve1'];
				}
			}
			
		}
			
			
		for($i=0;$i<60;$i++){
			$date = date("Y-m-d",time() - (86400 * $i));
			if(empty($liquidity24Hs[$symbol][$date]) == true){
			    if(empty($prevDate) == false && $liquidity24Hs[$symbol][$prevDate] != "0"){
					$liquidity24Hs[$symbol][$date] = $liquidity24Hs[$symbol][$prevDate];
				}else{
					$liquidity24Hs[$symbol][$date] = "0";
				}
			}
			
			$prevDate = $date;
        }

        foreach ($liquidity24Hs as $key => $val){
		    krsort($liquidity24Hs[$key]);
        }

		return response()->json([
			'data' => $liquidity24Hs
		]);
	}
	
	public function poolVolume24H(Request $request,$lpAddress){
		$pairHour = $this->contractDatabase('pairHour');
		if(empty($pairHour) == true){
			return response()->json([
				'error' => [
					'message' => "존재하지 않는 테이블"
				]
			]);
		}
		
		$result = $pairHour->where('lpAddress',$lpAddress)->orderBy('timestamp','desc')->offset(0)->limit(168)->get();//1시간 x 168 = 7일
		$statistics = $result['data'];
		
		if(empty($statistics) == true){
			return response()->json([
				'error' => [
					'message' => "Not Data"
				]
			]);
		}
		
		$volume24Hs = [];
		foreach($statistics as $hourData){
			$hourData['y'] = $this->bchexdec($hourData['y']);
			$hourData['m'] = $this->bchexdec($hourData['m']);
			$hourData['d'] = $this->bchexdec($hourData['d']);
			
			$time = strtotime("{$hourData['y']}-{$hourData['m']}-{$hourData['d']}");
			$date = date("Y-m-d",$time);
			
			if($time < (time() - 86400)){//해당 데이터가 일주일 이상된 데이터인 경우에는 처리하지 않음.
				//continue;
			}
			
			if(empty($hourData['swapAmountIn0']) == true){
				$hourData['swapAmountIn0'] = "0";
			}
			if(empty($hourData['swapAmountIn1']) == true){
				$hourData['swapAmountIn1'] = "0";
			}
			if(empty($hourData['swapAmountOut0']) == true){
				$hourData['swapAmountOut0'] = "0";
			}
			if(empty($hourData['swapAmountOut1']) == true){
				$hourData['swapAmountOut1'] = "0";
			}
			
			$swapAmount0 = bcadd($this->bchexdec($hourData['swapAmountIn0']),$this->bchexdec($hourData['swapAmountOut0']));
			$swapAmount1 = bcadd($this->bchexdec($hourData['swapAmountIn1']),$this->bchexdec($hourData['swapAmountOut1']));
			
			$volume24Hs[$date] = [
				'swapAmount0' => $swapAmount0,
				'swapAmount1' => $swapAmount1,
			];
		}

		$tokenAddress0 = $this->getToken0($lpAddress);
		$tokenAddress1 = $this->getToken1($lpAddress);
		$tokenSymbol0 = $this->checkNetToken($tokenAddress0);
		$tokenSymbol1 = $this->checkNetToken($tokenAddress1);
		if($tokenSymbol0 == false && $tokenSymbol1 == false){
			$netAddresses = $this->netTokenAddresses();
			foreach($netAddresses as $address){
				$result = $this->factoryContract(envDB('FACTORY_CONTRACT_ADDRESS'))->getPair($tokenAddress0,$address);
				if($result !== false && $result != '0x0000000000000000000000000000000000000000'){
					$pricePairAddress = $result;
					$select = "swapAmount0";
					$symbol = $this->checkNetToken($address);
					break;
				}
				
				$result = $this->factoryContract(envDB('FACTORY_CONTRACT_ADDRESS'))->getPair($tokenAddress1,$address);
				if($result !== false && $result != '0x0000000000000000000000000000000000000000'){
					$pricePairAddress = $result;
					$select = "swapAmount1";
					$symbol = $this->checkNetToken($address);
					break;
				}
			}
			
			if(empty($pricePairAddress) == false){
				$reserves = $this->getReserves($pricePairAddress);
				if($this->checkNetToken($reserves['token0']) !== false){
					$rate = $reserves['reserve1'] / $reserves['reserve0'];
				}else{
					$rate = $reserves['reserve0'] / $reserves['reserve1'];
				}
			}else{
				$rate = 0;
			}
		}else{
			if($tokenSymbol0 == false){
				$select = "swapAmount1";
				$symbol = $tokenSymbol1;
			}else{
				$select = "swapAmount0";
				$symbol = $tokenSymbol0;
			}
			
			$rate = 1;
		}
		
		$data = [];
		for($i=0;$i<60;$i++){
			$date = date("Y-m-d",time() - (86400 * $i));
			if(empty($volume24Hs[$date]) == true){
				$data[$date] = "0"; 
			}else{
				$data[$date] = bcmul($volume24Hs[$date][$select],$rate); 
			}
		}
		
		return response()->json([
			'data' => [
				$symbol => $data
			]
		]);
	}
	
	
	public function liquidity24H(Request $request){
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
		
		$todayAmounts = [];
		$addresses = $this->netTokenAddresses();
//		print_r($addresses);
//		exit;
		foreach($addresses as $address){
			$tokens = $this->contractDatabase('tokens');
			$result = $tokens->where('tokenAddress',$address)->offset(0)->limit(1)->get();

			if(empty($result['data']) == true || count($result['data']) == 0){
				continue;
			}
			
			// (totalMintAmount + totalAmountIn) - (totalBurnAmount + totalAmountOut)
			$data =  $result['data'][0];
			
			$amount = "0";
			if(empty($data['totalMintAmount']) == false){
				$amount = bcadd($amount,$this->bchexdec($data['totalMintAmount']));
			
			
			}
			if(empty($data['totalAmountIn']) == false){
				$amount = bcadd($amount,$this->bchexdec($data['totalAmountIn']));
			}
			if(empty($data['totalBurnAmount']) == false){
				$amount = bcsub($amount,$this->bchexdec($data['totalBurnAmount']));
			}
			if(empty($data['totalAmountOut']) == false){
				$amount = bcsub($amount,$this->bchexdec($data['totalAmountOut']));
			}
			
			$todayAmounts[$address] = $amount;
		}
//		print_r($todayAmounts);
//		exit;
//		0xbb4cdb9cbd36b01bd1cbaebf2de08d9173bc095c 가 - 나옴

		$addressesAmount24Hs = [];
		foreach($todayAmounts as $address => $amount){
			$tokenHour = $this->contractDatabase('tokenHour');
			$result = $tokenHour->where('tokenAddress',$address)->orderBy('timestamp','desc')->offset(0)->limit(720)->get();
			$statistics = $result['data'];
			$tokenSymbol = $this->checkNetToken($address);
			
			$amount24Hs = [];
			if(empty($statistics) == false && count($statistics) > 0){
				foreach($statistics as $hourData){
					$hourData['y'] = $this->bchexdec($hourData['y']);
					$hourData['m'] = $this->bchexdec($hourData['m']);
					$hourData['d'] = $this->bchexdec($hourData['d']);

					$date = "{$hourData['y']}-{$hourData['m']}-{$hourData['d']}";
					$time = strtotime($date);
					$date = date("Y-m-d",$time);
					if($time < (time() - 2592000)){//해당 데이터가 30일 이상된 데이터인 경우에는 처리하지 않음.
						//continue;
					}
					
					if(empty($amount24Hs[$date]) == true){
						$amount24Hs[$date] = [];
						$amount24Hs[$date]['swapAmountIn'] = "0";
						$amount24Hs[$date]['swapAmountOut'] = "0";
						$amount24Hs[$date]['totalMintAmount'] = "0";
						$amount24Hs[$date]['totalBurnAmount'] = "0";
					}
					
					if(empty($hourData['swapAmountIn']) == true){
						$hourData['swapAmountIn'] = "0";
					}
					if(empty($hourData['swapAmountOut']) == true){
						$hourData['swapAmountOut'] = "0";
					}
					if(empty($hourData['totalMintAmount']) == true){
						$hourData['totalMintAmount'] = "0";
					}
					if(empty($hourData['totalBurnAmount']) == true){
						$hourData['totalBurnAmount'] = "0";
					}
					
					$amount24Hs[$date]['swapAmountIn'] = bcadd($amount24Hs[$date]['swapAmountIn'],$this->bchexdec($hourData['swapAmountIn']));
					$amount24Hs[$date]['swapAmountOut'] = bcadd($amount24Hs[$date]['swapAmountOut'],$this->bchexdec($hourData['swapAmountOut']));
					$amount24Hs[$date]['totalMintAmount'] = bcadd($amount24Hs[$date]['totalMintAmount'],$this->bchexdec($hourData['totalMintAmount']));
					$amount24Hs[$date]['totalBurnAmount'] = bcadd($amount24Hs[$date]['totalBurnAmount'],$this->bchexdec($hourData['totalBurnAmount']));
				}
				
			}
			
			$addressesAmount24Hs[$address] = $amount24Hs;
		}

//		print_r($addressesAmount24Hs);
//		exit;
		$data = [];
		foreach($todayAmounts as $address => $amount){
			
			for($i=0;$i<14;$i++){
				$date = date("Y-m-d",time() - (86400 * $i));
				$tokenSymbol = $this->checkNetToken($address);
				
				if($i == 0){
					$data[$tokenSymbol] = [];
					$data[$tokenSymbol][$date] = $amount;
				}else{
					$nextDate = date("Y-m-d",time() - (86400 * ($i-1)));
					
					$nextAmount = $data[$tokenSymbol][$nextDate];
					if(empty($addressesAmount24Hs[$address][$date]) == true){
						$data[$tokenSymbol][$date] = $nextAmount;
					}else{
						//날짜 역순 계산이기 때문에 발행 , 소각 등의 계산을 반대로 진행
						$nextAmount = bcadd($nextAmount,$addressesAmount24Hs[$address][$date]['swapAmountOut']);
						$nextAmount = bcadd($nextAmount,$addressesAmount24Hs[$address][$date]['totalBurnAmount']);
						$nextAmount = bcsub($nextAmount,$addressesAmount24Hs[$address][$date]['swapAmountIn']);
						$nextAmount = bcsub($nextAmount,$addressesAmount24Hs[$address][$date]['totalMintAmount']);
						
						$data[$tokenSymbol][$date] = $nextAmount;
					}
					
				}
			}
			
		}
		
		return response()->json([
			'data' => $data
		]);			
	}
	
	public function volume24H(Request $request){
		$tokenHour = $this->contractDatabase('tokenHour');
		if(empty($tokenHour) == true){
			return response()->json([
				'error' => [
					'message' => "존재하지 않는 테이블"
				]
			]);
		}
		
		$addresses = $this->netTokenAddresses();
		
		$data = [];
		foreach($addresses as $address){
			$tokens = $this->contractDatabase('tokens');
			$result = $tokens->where('tokenAddress',$address)->offset(0)->limit(1)->get();
			if(empty($result['data']) == true || count($result['data']) == 0){
				continue;
			}

			$tokenHour = $this->contractDatabase('tokenHour');
			$result = $tokenHour->where('tokenAddress',$address)->orderBy('timestamp','desc')->offset(0)->limit(720)->get();

			$statistics = $result['data'];
//			print_r($statistics);
//			exit;
			$tokenSymbol = $this->checkNetToken($address);

//			echo "--------- ".$tokenSymbol . "--------\n";

			$swapAmount24Hs = [];
			if(empty($statistics) == false && count($statistics) > 0){
				foreach($statistics as $hourData){
					$hourData['y'] = $this->bchexdec($hourData['y']);
					$hourData['m'] = $this->bchexdec($hourData['m']);
					$hourData['d'] = $this->bchexdec($hourData['d']);

					$date = "{$hourData['y']}-{$hourData['m']}-{$hourData['d']}";
					$time = strtotime($date);
					$date = date("Y-m-d",$time);

//					echo  $date . "\n";

					if($time < (time() - 2592000)){//해당 데이터가 30일 이상된 데이터인 경우에는 처리하지 않음.
						continue; // 주석이었음 왜?
					}
					
					if(empty($swapAmount24Hs[$date]) == true){
						$swapAmount24Hs[$date] = "0";
					}
					
					if(empty($hourData['swapAmountIn']) == true){
						$hourData['swapAmountIn'] = "0";
					}
					if(empty($hourData['swapAmountOut']) == true){
						$hourData['swapAmountOut'] = "0";
					}

//					print_r($swapAmount24Hs);

					$swapAmount24Hs[$date] = bcadd($swapAmount24Hs[$date],$this->bchexdec($hourData['swapAmountIn']));
					$swapAmount24Hs[$date] = bcadd($swapAmount24Hs[$date],$this->bchexdec($hourData['swapAmountOut']));
				}
				
			}
			
			$data[$tokenSymbol] = $swapAmount24Hs;
		}

        $totalData = [];
        foreach($data as $symbol => $swapAmount24Hs){

            $dateArr = [];  // 60일치 0으로 초기화한 데이터
			for($i=0;$i<14;$i++){
				$date = date("Y-m-d",time() - (86400 * $i));

                // 원인 ㄴ: 위에서 나온 값들과 매칭이 안됨 ...
//                if(empty($swapAmount24Hs[$date]) == true){
//					$swapAmount24Hs[$date] = "0";
//				}
                if(empty($swapAmount24Hs[$date]) == true) {
                    $dateArr[$date] = "0";
                }

                foreach ($swapAmount24Hs as $key => $val){
                    if($key == $date){
                        $dateArr[$date] = $val;
                    }
                }
			}

//			$totalData[$symbol] = $swapAmount24Hs;
			$totalData[$symbol] = $dateArr;
		}


		return response()->json([
//			'data' => $data
			'data' => $totalData
		]);
	}
	
	public function majorPrices(Request $request){
		$pairHour = $this->contractDatabase('pairHour');
		if(empty($pairHour) == true){
			return response()->json([
				'error' => [
					'message' => "존재하지 않는 테이블"
				]
			]);
		}
		
		$addresses = $this->netTokenAddresses();
		
		if(envDB('BASE_MAINNET') == 'ETH'){
			$usdtAddress = "0xdac17f958d2ee523a2206206994597c13d831ec7";
		}else if(envDB('BASE_MAINNET') == 'BSC'){
			$usdtAddress = "0x55d398326f99059ff775485246999027b3197955";
		}else if(envDB('BASE_MAINNET') == 'KLAY'){
			$usdtAddress = "0xcee8faf64bb97a73bb51e115aa89c17ffa8dd167";
		}
		
		$data = [];
		foreach($addresses as $address){
			$lpAddress = $this->factoryContract(envDB('FACTORY_CONTRACT_ADDRESS'))->getCachePair($address,$usdtAddress);
			if($lpAddress == '0x0000000000000000000000000000000000000000' || $lpAddress == '0x'){
				continue;
			}
			
			$tokenSymbol = $this->checkNetToken($address);
			$reserves	 = $this->getReserves($lpAddress);
	
			$token0 = $this->getCacheTokenInfo($reserves['token0']);
			$token1 = $this->getCacheTokenInfo($reserves['token1']);
			
			for($i=0;$i<$token0['decimals'];$i++){
				$reserves['reserve0'] = bcdiv($reserves['reserve0'],"10",10);
			}
			
			for($i=0;$i<$token1['decimals'];$i++){
				$reserves['reserve1'] = bcdiv($reserves['reserve1'],"10",10);
			}
			
			if($reserves['token0'] == $address){
				$info = $token0;
				$price = bcdiv($reserves['reserve1'],$reserves['reserve0'],10);
			}else{
				$info = $token1;
				$price = bcdiv($reserves['reserve0'],$reserves['reserve1'],10);
			}
			
			$pairHour = $this->contractDatabase('pairHour');

            $result = $pairHour->where('lpAddress',$lpAddress)->orderBy('timestamp','desc')->offset(0)->limit(168)->get();//1시간 x 168 = 7일
//            print_r($result);
//            exit;

			$statistics = $result['data'];
			
			$price24H = "0";
			$history = [];
			if(empty($statistics) == false && count($statistics) > 0){
//			    print_r($statistics);
//			    exit;
				foreach($statistics as $hourData){
					$hourData['y'] = $this->bchexdec($hourData['y']);
					$hourData['m'] = $this->bchexdec($hourData['m']);
					$hourData['d'] = $this->bchexdec($hourData['d']);

                    $hourData['reserve0'] = $this->bchexdec($hourData['reserve0']);
                    $hourData['reserve1'] = $this->bchexdec($hourData['reserve1']);

					$date = "{$hourData['y']}-{$hourData['m']}-{$hourData['d']}";
					$time = strtotime($date);
					if($time < (time() - 604800) || empty($hourData['reserve0']) || empty($hourData['reserve1'])){//해당 데이터가 일주일 이상된 데이터인 경우에는 처리하지 않음.
						continue;
					}

					for($i=0;$i<$token0['decimals'];$i++){
						$hourData['reserve0'] = bcdiv($hourData['reserve0'],"10",10);
					}
					
					for($i=0;$i<$token1['decimals'];$i++){
						$hourData['reserve1'] = bcdiv($hourData['reserve1'],"10",10);
					}
			
					if($reserves['token0'] == $address){
						$hourPrice = bcdiv($hourData['reserve1'],$hourData['reserve0'],10);
					}else{
						$hourPrice = bcdiv($hourData['reserve0'],$hourData['reserve1'],10);
					}
					
					if($time > (time() - 86400)){
						$price24H = $hourPrice;
					}
					
					$history[$date] = $hourPrice;
				}
			}
			
			if($price24H == "0"){
				$price24H = $price;
			}
			
			$data[$tokenSymbol] = [
				'last' => $price,
				'price24H' => $price24H,
				'history' => $history,
				'info' => $info,
			];
		}
		
		return response()->json([
			'data' => $data
		]);	
	}
	
	public function allTxs(Request $request){
		$contractDatabase = $this->contractDatabase('allTxs');
		if(empty($contractDatabase) == true){
			return response()->json([
				'error' => [
					'message' => "존재하지 않는 테이블"
				]
			]);
		}


//        if ($this->get_client_ip() == "61.74.109.18"){
//            return 'test';
//        }
//        dd($contractDatabase->orderBy('timestamp','desc')->offset(0)->limit(10)->get());
		$result = $contractDatabase->orderBy('timestamp','desc')->offset(0)->limit(30)->get();
		$txs = $result['data'];

		$data = [];
		foreach($txs as $tx){
		    try{
                $pair = $this->getReserves($tx['lpAddress']);
                $token0 = $this->getCacheTokenInfo($pair['token0']);
                $token1 = $this->getCacheTokenInfo($pair['token1']);


                array_push($data,[
                    'tx_hash' => $tx['txHash'],
                    'type' => $tx['type'],
                    'token0' => $token0,
                    'token1' => $token1,
                    'tokenAmount0' => $tx['tokenAmount0'],
                    'tokenAmount1' => $tx['tokenAmount1'],
                    'timestamp' 	=> $tx['timestamp'],
                ]);

            }catch (Exception $e1){
                return response()->json([
                    'error' => "error",
                    'code' => $e1
                ]);
            }
		}


        return response()->json([
            'data' => $data
        ]);

    }


    public function tradePrices(Request $request){
        $req['to'] = isset($request->to) == false ? "KRW" : $request->to;
        $req['from'] = isset($request->from) == false ? "" : $request->from;
        $arr = ['ETH', 'BNB', 'MATIC', 'BTC'];

        foreach ($arr as $key => $val){
            $req['from'] = $val;
            $temp = json_decode($this->requestCURL($val.'_'.$req['to'], "GET"), true);
            if($temp['status'] != "0000"){
                $data['error'] = "문제 발생 문의바람";
                $data['status'] = false;
                break;
            }
            $temp['data']['closing_price'] = number_format($temp['data']['closing_price']);
            $temp['data']['token_name'] = $val;
            $data[] = $temp;
        }

        return new JsonResponse($data, 200);
    }

    public function requestCURL($url, $method, $data = []){
        $url = "https://api.bithumb.com/public/ticker/".$url;
        $ch = curl_init($url);

        if($method == "POST" || $method == "DELETE"){
            $json = json_encode($data,JSON_NUMERIC_CHECK);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        }

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','x-api-token: '. $this->api_token));
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

}