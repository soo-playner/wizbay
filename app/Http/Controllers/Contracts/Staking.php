<?php

namespace App\Http\Controllers\Contracts;


class StakingContract{
	use \App\Http\Controllers\BlocksdkController;
	
	function __construct($contractAddress){
		$this->contractAddress = $contractAddress;
	}
	
	public function rewardPerBlock(){
		$data = $this->contractRead([
			'contract_address'	=> $this->contractAddress,
			'method'			=> 'rewardPerBlock',
			'return_type'		=> 'uint256',
			'parameter_type'	=> [],
			'parameter_data'	=> [],
		]);
		
		if(empty($data['payload']) == true && isset($data['payload']) == false){
			return false;
		}
		
		$hex = $data['payload']['hex'];
		$amount = $this->bchexdec(substr($hex,2,64));
		
		return $amount;
	}	
	
	public function stakedToken(){
		$data = $this->contractRead([
			'contract_address'	=> $this->contractAddress,
			'method'			=> 'stakedToken',
			'return_type'		=> 'address',
			'parameter_type'	=> [],
			'parameter_data'	=> [],
		]);
		
		if(empty($data['payload']) == true && isset($data['payload']) == false){
			return false;
		}
		
		$address = $data['payload']['result'];
		return $address;
	}	
	
	public function rewardToken(){
		$data = $this->contractRead([
			'contract_address'	=> $this->contractAddress,
			'method'			=> 'rewardToken',
			'return_type'		=> 'address',
			'parameter_type'	=> [],
			'parameter_data'	=> [],
		]);
		
		if(empty($data['payload']) == true && isset($data['payload']) == false){
			return false;
		}
		
		$address = $data['payload']['result'];
		return $address;
	}
	
	public function earned($userAddress){
		$data = $this->contractRead([
			'contract_address'	=> $this->contractAddress,
			'method'			=> 'pendingReward',
			'return_type'		=> 'bool',
			'parameter_type'	=> ['address'],
			'parameter_data'	=> [$userAddress],
		]);
		
		if(empty($data['payload']) == true && isset($data['payload']) == false){
			return false;
		}
	
		$hex = $data['payload']['hex'];
		$amount = $this->bchexdec(substr($hex,2,64));
		
		return $amount;
	}
	
	public function userInfo($userAddress){
		$data = $this->contractRead([
			'contract_address'	=> $this->contractAddress,
			'method'			=> 'userInfo',
			'return_type'		=> 'bool',
			'parameter_type'	=> ['address'],
			'parameter_data'	=> [$userAddress],
		]);
		
		if(empty($data['payload']) == true && isset($data['payload']) == false){
			return false;
		}
	
		$hex = $data['payload']['hex'];
		
		$amount = $this->bchexdec(substr($hex,2,64));
		$rewardDebt = $this->bchexdec(substr($hex,66,64));
		
		return [
			'amount' => $amount,
			'rewardDebt' => $rewardDebt,
		];
	}
}

trait Staking {

	public function stakingContract($contractAddress){
		return new StakingContract($contractAddress);
	}
}