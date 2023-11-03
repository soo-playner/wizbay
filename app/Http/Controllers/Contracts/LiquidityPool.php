<?php

namespace App\Http\Controllers\Contracts;

/*
	BlocksdkController 와 필수적으로 함께 상속 되어야함.
*/

class LiquidityContract{
	use \App\Http\Controllers\BlocksdkController;
	
	function __construct($contractAddress){
		$this->contractAddress = $contractAddress;
	}
	
		
}

trait LiquidityPool {
	public function liquidityContract($contractAddress){
		return new LiquidityContract($contractAddress);
	}
	
	public function getReserves($address){
		$data = $this->contractRead([
			'contract_address'	=> $address,
			'method'			=> 'getReserves',
			'return_type'		=> 'bool',
			'parameter_type'	=> [],
			'parameter_data'	=> [],
		]);

		if(empty($data['payload']) == true){
			return null;
		}
		
		$hex = $data['payload']['hex'];
		
		$_reserve0 = $this->bchexdec(substr($hex,2,64));
		$_reserve1 = $this->bchexdec(substr($hex,66,64));
		
		$token0	= $this->getCacheToken0($address);
		$token1	= $this->getCacheToken1($address);
		
		$result = [
			'token0'   => $token0,
			'token1'   => $token1,
			'reserve0' => $_reserve0,
			'reserve1' => $_reserve1,
		];


		$this->cacheSave('reserves_' . $address,json_encode($result));
		return $result;
	}

    public function getToken0($address){
		$data = $this->contractRead([
			'contract_address'	=> $address,
			'method'			=> 'token0',
			'return_type'		=> 'address',
			'parameter_type'	=> [],
			'parameter_data'	=> [],
		]);
		
		if(empty($data['payload']) == true){
			return null;
		}
		
		$this->cacheSave('token0_' . $address,$data['payload']['result']);
		
		return $data['payload']['result'];
	}		
	
	public function getToken1($address){
		$data = $this->contractRead([
			'contract_address'	=> $address,
			'method'			=> 'token1',
			'return_type'		=> 'address',
			'parameter_type'	=> [],
			'parameter_data'	=> [],
		]);
		
		if(empty($data['payload']) == true){
			return null;
		}
		
		$this->cacheSave('token1_' . $address,$data['payload']['result']);
		
		return $data['payload']['result'];
	}	
}