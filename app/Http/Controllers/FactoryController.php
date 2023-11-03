<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use BlockSDK;

use App\Models\CacheData;

class FactoryController extends Controller
{
	
	public function pairAddress(Request $request){
		$result = $this->factoryContract(envDB('FACTORY_CONTRACT_ADDRESS'))->getPair($request->input1,$request->input2);
		if($result === false){
			return response()->json([
				'error' => [
					'message' => "Not Pair"
				]
			]);
		}
		
		return response()->json([
			'data' => $result
		]);
	}
}