<?php

namespace App\Http\Controllers\Contracts;

class FarmContract{
	use \App\Http\Controllers\BlocksdkController;
	
	
	function __construct($contractAddress){
		$this->contractAddress = $contractAddress;
	}
	
	public function poolInfo($pid){
		$data = $this->contractRead([
			'contract_address'	=> $this->contractAddress,
			'method'			=> 'poolInfo',
			'return_type'		=> 'bool',
			
			'parameter_type'	=> ['uint256'],
			'parameter_data'	=> [$pid],
		]);
		
		if(empty($data['payload']) == true){
			return false;
		}

		$hex = $data['payload']['hex'];
		
		$lpToken = $this->hexToAddress(substr($hex,2,64));
		$allocPoint = $this->bchexdec(substr($hex,66,64));
		$lastRewardBlock = $this->bchexdec(substr($hex,130,64));
		$accDexPerShare = $this->bchexdec(substr($hex,194,64));
		 
		return [
			'lpAddress' => $lpToken,
			'allocPoint' => $allocPoint,
			'lastRewardBlock' => $lastRewardBlock,
			'accDexPerShare' => $accDexPerShare,
		];
	}	
	
	public function dexPerBlock(){
		$data = $this->contractRead([
			'contract_address'	=> $this->contractAddress,
			'method'			=> 'dexPerBlock',
			'return_type'		=> 'uint256',
			
			'parameter_type'	=> [],
			'parameter_data'	=> [],
		]);
		
		if(empty($data['payload']) == true){
			return false;
		}
		
		return $data['payload']['result'];
	}	
	
	public function totalAllocPoint(){
		$data = $this->contractRead([
			'contract_address'	=> $this->contractAddress,
			'method'			=> 'totalAllocPoint',
			'return_type'		=> 'uint256',
			
			'parameter_type'	=> [],
			'parameter_data'	=> [],
		]);
		
		if(empty($data['payload']) == true){
			return false;
		}
		
		return $data['payload']['result'];
	}
	
	public function info($pid){
		$data = $this->contractRead([
			'contract_address'	=> $this->contractAddress,
			'method'			=> 'poolInfo',
			'return_type'		=> 'bool',
			
			'parameter_type'	=> ['uint256'],
			'parameter_data'	=> [$pullID],
		]);
		
		if(empty($data['payload']) == true){
			return false;
		}
		
		return $data['payload']['hex'];
	}
	
	public function earned($pid,$userAddress){
        $data = $this->contractRead([
			'contract_address'	=> $this->contractAddress,
			'method'			=> 'pendingDex',
			'return_type'		=> 'uint256',

			'parameter_type'	=> ['uint256','address'],
			'parameter_data'	=> [$pid,$userAddress],
		]);

		if(empty($data['payload']) == true){
			return false;
		}
		
		return $this->bchexdec(substr($data['payload']['hex'],2));
	}
	
	public function userInfo($pid,$userAddress){
		$data = $this->contractRead([
			'contract_address'	=> $this->contractAddress,
			'method'			=> 'userInfo',
			'return_type'		=> 'bool',
			'parameter_type'	=> ['uint256','address'],
			'parameter_data'	=> [$pid,$userAddress],
		]);
		
		if(empty($data['payload']) == true){
			return null;
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

trait Farm {
	
	public function farmContract($contractAddress){
		return new FarmContract($contractAddress);
	}
		
}
