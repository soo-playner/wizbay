<?php

namespace App\Http\Controllers\Contracts;


class Erc20Contract{
	use \App\Http\Controllers\BlocksdkController;
	
	function __construct($contractAddress){
		$this->contractAddress = $contractAddress;
	}
	
	public function balanceOf($address){
		$data = $this->netClient()->getContractRead([
			'contract_address' => $this->contractAddress,
			'method' => 'balanceOf',
			'return_type' => 'uint256',
			'parameter_type' => ['address'],
			'parameter_data' => [$address]
		]);
		
		if(empty($data['payload']) == true){
			return false;
		}
		
		$balance = $data['payload']['result'];
		return $balance;
	}	
}

trait Erc20 {

	public function erc20Contract($contractAddress){
		return new Erc20Contract($contractAddress);
	}
}