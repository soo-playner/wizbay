<?php

namespace App\Http\Controllers\Contracts;


class FactoryContract{
	use \App\Http\Controllers\BlocksdkController;
	use \App\Http\Controllers\CacheController;
	
	function __construct($contractAddress){
		$this->contractAddress = $contractAddress;
	}
	
	public function getPair($input1,$input2){
		$data = $this->netClient()->getContractRead([
			'contract_address' => $this->contractAddress,
			'method' => 'getPair',
			'return_type' => 'address',
			'parameter_type' => ['address','address'],
			'parameter_data' => [$input1,$input2]
		]);
		if(empty($data['payload']) == true){
			return false;
		}
		
		$address = $data['payload']['result'];
		if($address != '0x0000000000000000000000000000000000000000'){
			$this->cacheSave('factory_pair_' . md5($input1 . $input2),$address);
		}
		
		return $address;
	}
	
	public function getCachePair($input1,$input2){
		$result = $this->getPermanenCache('factory_pair_' . $input1 . $input2);
		if(empty($result) == true){
			$data = $this->getPair($input1,$input2);
		}else{
			$data = $result->data;
		}
		
		return $data;
	}	
}

trait Factory {

	public function factoryContract($contractAddress){
		return new FactoryContract($contractAddress);
	}
	
	
}