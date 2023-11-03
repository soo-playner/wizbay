<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use BlockSDK;

use App\Models\CacheData;
use App\Models\ContractDB;

class TokenController extends Controller
{
	public function tokenInfo(Request $request,$address){
		$info = $this->getCacheTokenInfo($address);
		if(empty($info['name']) == true){
			return response()->json([
				'error' => [
					'message' => "Not Token"
				]
			]);
		}
		
		
		$data = $info;
		$data['address'] = $address;
		
		return response()->json([
			'data' => $data
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
	
	public function tokenAllowance(Request $request,$address){
		$info = $this->getCacheTokenInfo($address);
		if(empty($info['name']) == true){
			return response()->json([
				'error' => [
					'message' => "Not Token"
				]
			]);
		}
		
		$data = $this->contractRead([
			'contract_address'	=> $address,
			'method'			=> 'allowance',
			'return_type'		=> 'uint256',
			
			'parameter_type'	=> ['address','address'],
			'parameter_data'	=> [$request->owner,$request->spender],
		]);

		if(empty($data['payload']) == true){
			return response()->json([
				'error' => [
					'message' => "Not Data"
				]
			]);
		}

		$result = $data['payload']['result'];
		if($result > 0){
			for($i=0;$i<$info['decimals'];$i++){
//				$result = $result / 10;
				$result = bcdiv($result, "10", 8);
			}
		}
		
		return response()->json([
			'data' => $result
		]);
	}
}