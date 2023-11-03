<?php

namespace App\Http\Controllers;

use App\Models\CacheData;
use App\Models\CoinInfo;
/*
	BlocksdkController 와 필수적으로 함께 상속 되어야함.
*/
trait CacheController
{
	public function getPermanenCache($id){
		$cache = CacheData::find($id);
		if(empty($cache) == true){
			return false;
		}
		
		return CacheData::find($id);
	}
	
	public function getCache($id){
		$cache = CacheData::find($id);
		if(empty($cache) == true){
			return false;
		}else if((strtotime($cache->updated_at) + envDB('CACHE_TIME_NFT')) < time() ){
			return false;//지정된 캐시시간보다 길어졋을경우
		}
		return CacheData::find($id);
	}
	
	public function cacheSave($id,$hex){
		$cacheData = CacheData::find($id);
		if(empty($cacheData) == true){
			$cacheData = new CacheData();
		}
		
		$cacheData->id = $id;
		$cacheData->data = $hex;
		$cacheData->save();
	}
	
	public function getCacheTokenInfo($address){

		$result = $this->getCache('tokeninfo_' . $address);

		if(empty($result) == true){
			$data = $this->getTokenInfo($address);
		}else{
			$data = json_decode($result->data,true);
		}

		$data['tokenAddress'] = $address;


		$data['address'] = $data['tokenAddress'];
//		$data['image'] = "https://bscscan.com/token/images/ftm_32.png?=v2";

        $image = $this->tokenImage($address);
        if($image['states'] == true){
            foreach($image as $k => $v){
                if($k != "states"){
                    $data[$k] = $v;
                }
            }
        }else {
            $data['image'] = $image['image'];
        }


		//코인당 이미지 /img/coinmolru.png



		return $data;
	}

	
	public function getCacheReserves($address){
		$result = $this->getCache('reserves_' . $address);
		if(empty($result) == true){
			$data = $this->getReserves($address);
		}else{
			$data = json_decode($result->data,true);
		}
		
		return $data;
	}
	
	
	public function getCacheToken0($address){
		$result = $this->getPermanenCache('token0_' . $address);
		if(empty($result) == true){
			$data = $this->getToken0($address);
		}else{
			$data = $result->data;
		}
		
		return $data;
	}
	
	public function getCacheToken1($address){
		$result = $this->getPermanenCache('token1_' . $address);
		if(empty($result) == true){
			$data = $this->getToken1($address);
		}else{
			$data = $result->data;
		}
		
		return $data;
	}



    public function tokenImage($token){
        $netName = $this->mainNet();
        $res = [];
        if(CoinInfo::where($netName, $token)->exists()){
            $res = CoinInfo::where($netName, $token)->get();
            $res[0]->image = json_decode($res[0]->image, true);
            $res['thumb'] = $res[0]['image']['thumb'];
            $res['small'] = $res[0]['image']['small'];;
            $res['large'] = $res[0]['image']['large'];;
            $res['states'] = true;
        }else {
            $res['error'] = "token is not exist.";
            $res['image'] = '/img/coinmolru.png';
            $res['states'] = false;
        }

        return $res;
    }

    public function mainNet(){
        if(envDB('BASE_MAINNET') == 'ETH')
            return "ethereum";
        if(envDB('BASE_MAINNET') == 'BSC')
            return "binance";
        if(envDB('BASE_MAINNET') == 'KLAY')
            return "polygon";
    }
}