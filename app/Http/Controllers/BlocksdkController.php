<?php

namespace App\Http\Controllers;

use BlockSDK;

trait BlocksdkController
{
    public function bchexdec($hex) {
		if(substr($hex,0,2) == '0x'){
			$hex = substr($hex,2);
		}
        if(strlen($hex) == 1) {
            return hexdec($hex);
        } else {
            $remain = substr($hex, 0, -1);
            $last = substr($hex, -1);
            return bcadd(bcmul(16, $this->bchexdec($remain)), hexdec($last));
        }
    }  
	
    public function bcdechex($dec) {
        $last = bcmod($dec, 16);
        $remain = bcdiv(bcsub($dec, $last), 16);

        if($remain == 0) {
            return dechex($last);
        } else {
            return $this->bcdechex($remain).dechex($last);
        }
    }
	
	public function hexToAddress($hex) {
        return '0x' . strtolower(substr($hex,24,40));
    }
	
	public function contractDB(){
		return BlockSDK::createContractDB(envDB('BLOCKSDK_TOKEN'));
	}
	
	public function netClient(){
		if(envDB('BASE_MAINNET') == 'ETH')
			return BlockSDK::createEthereum(envDB('BLOCKSDK_TOKEN'));
		if(envDB('BASE_MAINNET') == 'BSC'){
			return BlockSDK::createBinanceSmart(envDB('BLOCKSDK_TOKEN'));
		}if(envDB('BASE_MAINNET') == 'KLAY')
			return BlockSDK::createKlaytn(envDB('BLOCKSDK_TOKEN'));
	}
	
	public function getTokenInfo($address){
		if(envDB('BASE_MAINNET') == 'ETH'){
			$data = $this->netClient()->getErc20([
				'contract_address' => $address,
			]);
		}else if(envDB('BASE_MAINNET') == 'BSC'){
			$data = $this->netClient()->getBep20([
				'contract_address' => $address,
			]);
		}else if(envDB('BASE_MAINNET') == 'KLAY'){
			$data = $this->netClient()->getKip7([
				'contract_address' => $address,
			]);
		}
			
		
		$json = json_encode($data['payload']);
		$this->cacheSave('tokeninfo_' . $address,$json);
		
		return $data['payload'];
	}
	
	public function getTokenBalance($contract_address,$from){
		if(envDB('BASE_MAINNET') == 'ETH'){
			$data = $this->netClient()->getErc20Balance([
				'contract_address' => $contract_address,
				'from' 			   => $from,
			]);
		}else if(envDB('BASE_MAINNET') == 'BSC'){
			$data = $this->netClient()->getBep20Balance([
				'contract_address' => $contract_address,
				'from' 			   => $from,
			]);
		}else if(envDB('BASE_MAINNET') == 'KLAY'){
			$data = $this->netClient()->getKip7Balance([
				'contract_address' => $contract_address,
				'from' 			   => $from,
			]);
		}

		if(empty($data['payload']) == true){
			return false;
		}
		
		return $data['payload'];
	}	
	
	public function contractRead($request){
		$data = $this->netClient()->getContractRead([
			"contract_address" => $request['contract_address'],
			"method" 		   => $request['method'],
			'parameter_type'   => $request['parameter_type'],
			'parameter_data'   => $request['parameter_data'],
			'return_type' 	   => $request['return_type']
		]);
		
		return $data;
	}
	
	public function netTokenAddresses(){
		if(envDB('BASE_MAINNET') == 'ETH'){
			return ['0xc02aaa39b223fe8d0a0e5c4f27ead9083c756cc2','0xb8c77482e45f1f44de1745f52c74426c631bdd52','0x2260fac5e5542a773aa44fbcfedf7c193bc2c599'];
		}else if(envDB('BASE_MAINNET') == 'BSC'){
			return ['0x2170ed0880ac9a755fd29b2688956bd959f933f8','0xbb4cdb9cbd36b01bd1cbaebf2de08d9173bc095c','0x7130d2a12b9bcbfae4f2634d864a1ee1ce3ead9c'];
		}else if(envDB('BASE_MAINNET') == 'KLAY'){
			return ['0x574e9c26bda8b95d7329505b4657103710eb32ea','0xbb4cdb9cbd36b01bd1cbaebf2de08d9173bc095c','0x16d0e1fbd024c600ca0380a4c5d57ee7a2ecbf9c'];
		}
		
		return [];
	}
	
	public function checkNetToken($address){
		if(envDB('BASE_MAINNET') == 'ETH' && ( strtolower($address) == '0xc02aaa39b223fe8d0a0e5c4f27ead9083c756cc2' )){
			return "ETH";
		}else if(envDB('BASE_MAINNET') == 'ETH' && ( strtolower($address) == '0xb8c77482e45f1f44de1745f52c74426c631bdd52' )){
			return "BNB";
		}else if(envDB('BASE_MAINNET') == 'ETH' && ( strtolower($address) == '0x2260fac5e5542a773aa44fbcfedf7c193bc2c599' )){
			return "BTC";
		}
		
		if(envDB('BASE_MAINNET') == 'BSC' && ( strtolower($address) == '0x2170ed0880ac9a755fd29b2688956bd959f933f8' )){
			return "ETH";
		}else if(envDB('BASE_MAINNET') == 'BSC' && ( strtolower($address) == '0xbb4cdb9cbd36b01bd1cbaebf2de08d9173bc095c' )){
			return "BNB";
		}else if(envDB('BASE_MAINNET') == 'BSC' && ( strtolower($address) == '0x7130d2a12b9bcbfae4f2634d864a1ee1ce3ead9c' )){
			return "BTC";
		}
		
		if(envDB('BASE_MAINNET') == 'KLAY' && ( strtolower($address) == '0x574e9c26bda8b95d7329505b4657103710eb32ea' )){
			return "ETH";
		}else if(envDB('BASE_MAINNET') == 'KLAY' && ( strtolower($address) == '0xbb4cdb9cbd36b01bd1cbaebf2de08d9173bc095c' )){
			return "BNB";
		}else if(envDB('BASE_MAINNET') == 'KLAY' && ( strtolower($address) == '0x16d0e1fbd024c600ca0380a4c5d57ee7a2ecbf9c' )){
			return "BTC";
		}
		
		return false;
	}

    /**
     * test용 함수
     * @param $req
     * @return mixed
     */
    public function getTestTrades(){
        $req['from'] = ""; $req['to'] = "KRW";
//        $this->getTrades($req);
        return $req;
    }
}