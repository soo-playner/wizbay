<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;

use App\Models\ContractDB;

use BlockSDK;

class ContractDBController extends Controller{

	public function selected(Request $request,$tableName){
		$contractDB = ContractDB::find($tableName);
		if(empty($contractDB) == true){
			return response()->json([
				'error' => [
					'message' => "존재하지 않는 테이블"
				]
			]);
		}
		
		$result = $this->contractDB()->selected([
			'table_id' => $contractDB->table_id,
			'where' => $request->where,
			'order' => $request->order,
			'offset' => $request->offset,
			'limit' => $request->limit,
		]);
		
		if(empty($result['payload']) == true){
			return response()->json([
				'error' => $result['error']
			]);
		}
		
		return response()->json($result['payload']);
	}
}