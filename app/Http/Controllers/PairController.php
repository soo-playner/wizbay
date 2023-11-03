<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use BlockSDK;

use App\Models\CacheData;

class PairController extends Controller
{	
	public function info(Request $request,$pairAddress){
		
		$token0 = $this->getToken0($pairAddress);
		$token1 = $this->getToken1($pairAddress);
		
		$tokenInfo0 = $this->getCacheTokenInfo($token0);
		$tokenInfo1 = $this->getCacheTokenInfo($token1);
		
		
		return response()->json([
			'data' => [
				'token0' => $tokenInfo0,
				'token1' => $tokenInfo1,
			]
		]);
	}
	
	public function tokenBalance(Request $request,$address){
		$payload = $this->getTokenBalance($address,$request->from);
		if(empty($payload) == true){
			return response()->json([
				'error' => [
					'message' => "Not From"
				]
			]);
		}
		
		return response()->json([
			'data' => $payload
		]);
	}	
	
	public function reserves(Request $request,$pairAddress){
		$result = $this->getReserves($pairAddress);
		if(empty($result) == true){
			return response()->json([
				'error' => [
					'message' => "Not Data"
				]
			]);
		}
		
		$tokenInfo0 = $this->getCacheTokenInfo($result['token0']);
		if(empty($tokenInfo0['name']) == true){
			return response()->json([
				'error' => [
					'message' => "Not Token"
				]
			]);
		}	
		
		$tokenInfo1 = $this->getCacheTokenInfo($result['token1']);
		if(empty($tokenInfo0['name']) == true){
			return response()->json([
				'error' => [
					'message' => "Not Token"
				]
			]);
		}
		
		
		for($i=0;$i<$tokenInfo0['decimals'];$i++){
			$result['reserve0'] = bcdiv($result['reserve0'],'10',8);
		}
		
		for($i=0;$i<$tokenInfo1['decimals'];$i++){
			$result['reserve1'] = bcdiv($result['reserve1'],'10',8);
		}
		
		return response()->json([
			'data' => $result
		]);
	}
	
	public function amountIn(Request $request,$pairAddress){
		$result = $this->getReserves($pairAddress);
		if(empty($result) == true){
			return response()->json([
				'error' => [
					'message' => "Not Data"
				]
			]);
		}
		
		if($this->getToken0($pairAddress) == $request->formAddress){
			$parameterData = [
						$request->amountOut,
						$result['reserve0'],
						$result['reserve1']
			];
		}else{
			$parameterData = [
						$request->amountOut,
						$result['reserve1'],
						$result['reserve0']
			];
		}
		
		$data = $this->contractRead([
			'contract_address'	=> envDB('ROUTER_CONTRACT_ADDRESS'),
			'method'			=> 'getAmountIn',
			'return_type'		=> 'uint256',
			'parameter_type'	=> ['uint256','uint256','uint256'],
			'parameter_data'	=> $parameterData,
		]);
			
		if(empty($data['payload']) == true){
			return response()->json([
				'error' => [
					'message' => "Not Data"
				]
			]);
		}
		
		return response()->json([
			'data' => $this->bchexdec(substr($data['payload']['hex'],2))
		]);
	}
	
	public function amountOut(Request $request,$pairAddress){
		$result = $this->getReserves($pairAddress);
		if(empty($result) == true){
			return response()->json([
				'error' => [
					'message' => "Not Data"
				]
			]);
		}

		$token0 = $this->getToken0($pairAddress);
		$token1 = $this->getToken1($pairAddress);
		
		$tokenInfo0 = $this->getCacheTokenInfo($token0);
		$tokenInfo1 = $this->getCacheTokenInfo($token1);



		if($token0 == $request->formAddress){
			
			$amountIn = $request->amountIn;
			
			for($i=0;$i<$tokenInfo0['decimals'];$i++){
				$amountIn = bcmul($amountIn,'10',8);
			}
		
			$parameterData = [
                "0x" . $this->bcdechex(explode('.',$amountIn)[0]),
				"0x" . $this->bcdechex($result['reserve0']),
                "0x" . $this->bcdechex($result['reserve1']),
			];


		}else{
			
			$amountIn = $request->amountIn;
			
			for($i=0;$i<$tokenInfo1['decimals'];$i++){
				$amountIn = bcmul($amountIn,'10',8);
			}
			
			$parameterData = [
                "0x" . $this->bcdechex(explode('.',$amountIn)[0]),
                "0x" . $this->bcdechex($result['reserve1']),
                "0x" . $this->bcdechex($result['reserve0']),
			];

		}

		$data = $this->contractRead([
			'contract_address'	=> envDB('ROUTER_CONTRACT_ADDRESS'),
			'method'			=> 'getAmountOut',
			'return_type'		=> 'uint256',
			'parameter_type'	=> ['uint256','uint256','uint256'],
			'parameter_data'	=> $parameterData,
		]);

		if(empty($data['payload']) == true){
			return response()->json([
				'error' => [
					'message' => "Not Data2"
				]
			]);
		}
		
		$amountOut = $this->bchexdec(substr($data['payload']['hex'],2));
		if($token0 == $request->formAddress){
			for($i=0;$i<$tokenInfo1['decimals'];$i++){
				$amountOut = bcdiv($amountOut,'10',8);
			}
		}else{
			for($i=0;$i<$tokenInfo0['decimals'];$i++){
				$amountOut = bcdiv($amountOut,'10',8);
			}
		}
		
		
		return response()->json([
			'data' => $amountOut
		]);
	}
}