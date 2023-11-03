<?php

namespace App\Http\Controllers\Contracts;

use App\Models\ContractDB as ModelContractDB;

class ContractDatabase{
	use \App\Http\Controllers\BlocksdkController;
	
	function __construct($tableName){
		
		$this->db = ModelContractDB::find($tableName);
		
	}
	
	public function where($columnName,$value){
		if(empty($this->where_) == true){
			$this->where_ = [];
		}
		
		array_push($this->where_,[
			'columnName' => $columnName,
			'value' => $value,
		]);
		
		return $this;
	}
	
	public function orderBy($columnName,$sort){
		$this->order_ = [
			'columnName' => $columnName,
			'sort' => $sort,
		];

		return $this;
	}
	
	public function offset($set){
		$this->offset_ = $set;
		
		return $this;
	}
	
	public function limit($set){
		$this->limit_ = $set;
		
		return $this;
	}
	
	public function get(){
		$result = $this->contractDB()->selected([
			'table_id' => $this->db->table_id,
			'where' => empty($this->where_)?null:$this->where_,
			'order' => empty($this->order_)?null:$this->order_,
			'offset' => empty($this->offset_)?0:$this->offset_,
			'limit' => empty($this->limit_)?10:$this->limit_,
		]);

//		print_r($result);

		if(empty($result['payload']) == true){
			return false;
		} 
		
		return $result['payload'];
	}

}

trait ContractDB {

	public function contractDatabase($tableName){
		$obj = new ContractDatabase($tableName);
		if(empty($obj->db) == true){
			return false;
		}
		
		return $obj;
	}
}