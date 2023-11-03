<?php

namespace App\Http\Controllers;

//use GuzzleHttp\Psr7\Utils;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;

use App\Models\Nft;

use App\Models\NftLikeCun; 

use App\Models\Profile;

use App\Models\CacheNft;

use App\Models\LogTxVerify;

use App\Models\CategoryEnv;

use BlockSDK;
use IPFS;
class NftController extends Controller
{
    //ㅅ
	public function netClient(){
		if(envDB('BASE_MAINNET') == 'ETH')
			return BlockSDK::createEthereum(envDB('BLOCKSDK_TOKEN'));
		if(envDB('BASE_MAINNET') == 'BSC')
			return BlockSDK::createBinanceSmart(envDB('BLOCKSDK_TOKEN'));
		if(envDB('BASE_MAINNET') == 'KLAY')
			return BlockSDK::createKlaytn(envDB('BLOCKSDK_TOKEN'));
	}
	
	
	public function likeCun($token_id){
		$nft_like_cun = NftLikeCun::find($token_id);
		if(empty($nft_like_cun) == true){
			return 0;
		}
		
		return $nft_like_cun['cun'];
	}
	
	public function getNftData($tokenInfo){
		$nft = Nft::where('token_id',$tokenInfo['token_id'])->first();
		if(empty($nft) == true){
			return false;
		}
		
		$tokenInfo['id'] = $nft->id;
		$tokenInfo['name'] = $nft->name;
		$tokenInfo['description'] = $nft->description;
		

		if(substr($nft->file_name,-4) == '.mp4'){
			if(empty(envDB('IS_AWS_S3')) == true){
				$tokenInfo['video_url'] = envDB('BASE_IMAGE_URI') . Storage::url('nft_files/' . $nft->file_name);
			}else if(empty(envDB('IS_AWS_S3')) == false && file_exists(storage_path('app/public/nft_files/' . $nft->file_name)) == true ){
				$tokenInfo['video_url'] = envDB('BASE_IMAGE_URI') . Storage::url('nft_files/' . $nft->file_name);
			}else if(empty(envDB('IS_AWS_S3')) == false){
				$tokenInfo['video_url'] = envDB('BASE_AWS_S3_URI') . '/nft-files/' . $nft->file_name;
			}
		}else{
			if(empty(envDB('IS_AWS_S3')) == true){
				$tokenInfo['image_url'] = envDB('BASE_IMAGE_URI') . Storage::url('nft_files/' . $nft->file_name);
			}else if(empty(envDB('IS_AWS_S3')) == false && file_exists(storage_path('app/public/nft_files/' . $nft->file_name)) == true ){
				$tokenInfo['image_url'] = envDB('BASE_IMAGE_URI') . Storage::url('nft_files/' . $nft->file_name);
			}else if(empty(envDB('IS_AWS_S3')) == false){
				$tokenInfo['image_url'] = envDB('BASE_AWS_S3_URI') . '/nft-files/' . $nft->file_name;
			}
		}
		
		$tokenInfo['owner'] = $this->getProfile($tokenInfo['owner']);
		
		$tokenInfo['like']  = $this->likeCun($tokenInfo['token_id']);
		$tokenInfo['price'] = $this->getCachePrice($nft->token_id) / 1000000000000000000;
		$tokenInfo['offer'] = $this->getCacheOffer($nft->token_id);
		
		return $tokenInfo;
	}
	
	public function getNfts($offset,$limit){
		$data = $this->netClient()->getNfts([
			'contract_address' => envDB('CONTRACT_ADDRESS'),
			'offset' => $offset,
			'limit' => $limit
		]);
		
		$payload = $data['payload'];
		
		$tokens = [];
		foreach($payload['tokens'] as $token){
			$token = $this->getNftData($token);
			if($token == false){
				continue;
			}
			
			array_push($tokens,$token);
		}
		
		return $tokens;
	}	
	
	public function getSaleNfts($seller_address,$offset,$limit){
		$data = $this->netClient()->getSaleNfts([
			'contract_address' => envDB('CONTRACT_ADDRESS'),
			'seller_address' => $seller_address,
			'order_direction'  => 'desc',
			'offset'           => $offset,
			'limit'            => $limit
		]);
		
		$payload = $data['payload'];

		$tokens = [];
		foreach($payload['sales'] as $token){
			$token = $this->getNftData($token);
			if($token == false){
				continue;
			}
			array_push($tokens,$token);
		}
		
		return [
			'total' => $payload['total_sales'],
			'data' => $tokens
		];
	}
	
	public function saleTokens(Request $request,$seller_address){
		if(empty($request->offset) == true){
			$offset = 0;
		}else{
			$offset = $request->offset;
		}
		
		$tokens = $this->getSaleNfts($seller_address,$offset,6);
		
		
		return response()->json($tokens);
	}
	
	public function getAuctionNfts($offset,$limit){
		$data = $this->netClient()->getAuctionNfts([
			'contract_address' => envDB('CONTRACT_ADDRESS'),
			'order_by'         => 'end_time',
			'order_direction'  => 'asc',
			'offset'           => $offset,
			'limit'            => $limit
		]);
		
		$payload = $data['payload'];
		
		$tokens = [];
		foreach($payload['auctions'] as $token){
			$token = $this->getNftData($token);
			if($token == false){
				continue;
			}
			array_push($tokens,$token);
		}
		
		return $tokens;
	}
	
	public function auctionEnding(Request $request){
		$tokens = $this->getAuctionNfts(0,8);
		
		
		return response()->json($tokens);
	}
	
	public function newTokens(Request $request){
		$tokens = $this->getNfts(0,8);
		
		
		return response()->json($tokens);
	}
	
	public function hexToBool($hex){
		return (bool)hexdec(substr($hex,0,64));
	}	
	
	public function hexToDec($hex){
		return hexdec(substr($hex,0,64));
	}	
	
	public function hexToOffer($hex){
		$isForSale = (bool)hexdec(substr($hex,0,64));
		$seller = substr($hex,64,64);
		$minValue = hexdec(substr($hex,128,64)) / 1000000000000000000;
		$endTime = hexdec(substr($hex,192,64));
		
		return [
			'isForSale' => $isForSale,
			'seller' => $seller,
			'minValue' => $minValue,
			'endTime' => $endTime,
		];
	}
	
	public function hexToBid($hex){
		$hasBid = (bool)hexdec(substr($hex,0,64));
		$bidder = substr($hex,64,64);
		$value = hexdec(substr($hex,128,64)) / 1000000000000000000;
		
		return [
			'hasBid' => $hasBid,
			'bidder' => '0x' . substr($bidder,-40),
			'value' => $value
		];
	}
	
	public function getCacheOffer($token_id){
		$result = $this->getCache('offer_' . $token_id);
		if(empty($result) == true){
			return $this->getOffer($token_id);
		}
		
		return $this->hexToOffer($result->data);
	}	
	
	public function getCacheBid($token_id){
		$result = $this->getCache('bid_' . $token_id);
		if(empty($result) == true){
			return $this->getBid($token_id);
		}
		
		return $this->hexToBid($result->data);
	}	
	
	public function getCacheListed($token_id){
		$result = $this->getCache('listed_' . $token_id);
		if(empty($result) == true){
			return $this->listed($token_id);
		}
		
		return $this->hexToBool($result->data);
	}
	
	public function getCachePrice($token_id){
		$result = $this->getCache('price_' . $token_id);
		if(empty($result) == true){
			return $this->getPrice($token_id);
		}
		
		return $this->hexToDec($result->data);
	}
	
	public function getOffer($token_id){
		$data = $this->netClient()->getContractRead([
			'contract_address' => envDB('CONTRACT_ADDRESS'),
			'method' => 'offers',
			'return_type' => 'bool',
			'parameter_type' => ['uint256'],
			'parameter_data' => [$token_id]
		]);
		
		$data = $data['payload'];
			
		$hex = substr($data['hex'],2);
		$this->cacheSave('offer_' . $token_id,$hex);
		
		return $this->hexToOffer($hex);
	}
	
	public function getBid($token_id){
		$data = $this->netClient()->getContractRead([
			'contract_address' => envDB('CONTRACT_ADDRESS'),
			'method' => 'bids',
			'return_type' => 'bool',
			'parameter_type' => ['uint256'],
			'parameter_data' => [$token_id]
		]);
		
		$data = $data['payload'];
			
		$hex = substr($data['hex'],2);
		$this->cacheSave('bid_' . $token_id,$hex);
		
		return $this->hexToBid($hex);
	}
	
	public function listed($token_id){
		$data = $this->netClient()->getContractRead([
			'contract_address' => envDB('CONTRACT_ADDRESS'),
			'method' => 'listedMap',
			'return_type' => 'bool',
			'parameter_type' => ['uint256'],
			'parameter_data' => [$token_id]
		]);
		
		$data = $data['payload'];
			
		$hex = substr($data['hex'],2);
		$this->cacheSave('listed_' . $token_id,$hex);
		
		return $this->hexToBool($hex);
	}
	
	public function getPrice($token_id){
		$data = $this->netClient()->getContractRead([
			'contract_address' => envDB('CONTRACT_ADDRESS'),
			'method' => 'price',
			'return_type' => 'uint256',
			'parameter_type' => ['uint256'],
			'parameter_data' => [$token_id]
		]);
		
		$data = $data['payload'];
			
		$hex = substr($data['hex'],2);
		$this->cacheSave('price_' . $token_id,$hex);
		
		return $this->hexToDec($hex);
	}
	
	public function getCache($id){
		$cache = CacheNft::find($id);
		if(empty($cache) == true){
			return false;
		}else if((strtotime($cache->updated_at) + envDB('CACHE_TIME_NFT')) < time() ){
			return false;//지정된 캐시시간보다 길어졋을경우
		}
		return CacheNft::find($id);
	}
	
	public function cacheSave($id,$hex){
		$cacheNft = CacheNft::find($id);
		if(empty($cacheNft) == true){
			$cacheNft = new CacheNft();
		}
		
		$cacheNft->id = $id;
		$cacheNft->data = $hex;
		$cacheNft->updated_at = date('Y-m-d H:i:s');
		$cacheNft->save();
	}
	
	public function getBids($token_id){
		$data = $this->netClient()->getNftBids([
			'contract_address' => envDB('CONTRACT_ADDRESS'),
			'token_id' => $token_id,
			'rawtx' => true,
			'offset' => 0,
			'limit' => 128
		]);
		
		if(empty($data['payload']) == true){
			return false;
		}
		
		$bids = [];
		foreach($data['payload']['bids'] as $bid){
			$rawtx = $bid['rawtx'];
			
			$bid['bidder'] = $this->getProfile($rawtx['from']);
			$bid['price'] = $rawtx['value'];
			
			array_push($bids,$bid);
		}
		
		return [
			'total' => $data['payload']['total_bids'],
			'data' => $bids
		];
	}
			
	public function getTransfers($token_id){
		$data = $this->netClient()->getNftTransfers([
			'contract_address' => envDB('CONTRACT_ADDRESS'),
			'token_id' => $token_id,
			'rawtx' => true,
			'offset' => 0,
			'limit' => 128
		]);
		
		$data = $data['payload'];
		
		$transfers = [];
		foreach($data['transfers'] as $transfer){
			$rawtx = $transfer['rawtx'];
			
			if(substr($rawtx['input'],0,10) == '0x7e8816b9'){//일반판매 발행	
				$transfer['method'] = 'mint';	
				$transfer['price'] = hexdec(substr($rawtx['input'],138,64)) / 1000000000000000000;	//등록할 판매 가격
				
			}else if(substr($rawtx['input'],0,10) == '0x8804da63'){//경매 발행	
				$transfer['method'] = 'auctionMint';
				$transfer['price'] = hexdec(substr($rawtx['input'],138,64)) / 1000000000000000000; // 최소 입찰 시작 가격
				
			}else if(substr($rawtx['input'],0,10) == '0xd96a094a'){//구매
				$transfer['method'] = 'buy';
				$transfer['price'] = $rawtx['value'];//구매 가격
				
			}else if(substr($rawtx['input'],0,10) == '0xb9a2de3a'){//낙찰
				$transfer['method'] = 'endAuction';
				foreach($rawtx['logs'] as $log){
					if($log['topics'][0] != '0xc87036081503cc1fd53dc456ee0c40aef140882f77b06b4b4b554fee2b60816a'){
						continue;
					}
					
					$transfer['price'] = hexdec(substr($log['data'],-64,64)) / 1000000000000000000; // 최종 낙찰 금액
				}
			}else if($transfer['to'] == '0x0000000000000000000000000000000000000000'){
				$transfer['method'] = 'burn';
			}
			
			$transfer['from'] = $this->getProfile($transfer['from']);
			$transfer['to'] = $this->getProfile($transfer['to']);
			
			array_push($transfers,$transfer);
		}
		
		return $transfers;
	}
	
	public function getCacheTokenInfo($token_id){
		$result = $this->getCache('tokeninfo_' . $token_id);
		if(empty($result) == true){
			return $this->getTokenInfo($token_id);
		}
		
		return json_decode($result->data,true);
	}
	
	public function getTokenInfo($token_id){
		$data = $this->netClient()->getNftInfo([
			'contract_address' => envDB('CONTRACT_ADDRESS'),
			'token_id' => $token_id,
		]);
		
		$json = json_encode($data['payload']);
		$this->cacheSave('tokeninfo_' . $token_id,$json);
		
		return $data['payload'];
	}
	
	public function getNFT(Request $request,$nft_id){
		$nft = Nft::find($nft_id);
		if(empty($nft) == true){
			return response()->json([
				'error' => [
					'message' => "존재하지 않는 NFT 입니다"
				]
			]);
		}
		
		$result = [
			'id'   => $nft->id,
			
			'metadata_uri'   => "ipfs://" . $nft->id,
			'metadata_gateway_url' => envDB("BASE_IPFS_GATEWAY") . '/' .$nft->id,
			
			'image_uri'   => 'ipfs://' . '/' .$nft->ipfs_image_hash,
			'image_gateway_url'   => envDB("BASE_IPFS_GATEWAY") . '/' .$nft->id,
			
			'tx_hash' => $nft->tx_hash,
			'token_id' => $nft->token_id,
			
			'name' => $nft->name,
			'description' => $nft->description,
			
			'year_creation' => $nft->year_creation,
		];
		
		if(substr($nft->file_name,-4) == '.mp4'){
			if(empty(envDB('IS_AWS_S3')) == true){
				$result['video_url'] = envDB('BASE_IMAGE_URI') . Storage::url('nft_files/' . $nft->file_name);
			}else if(empty(envDB('IS_AWS_S3')) == false && file_exists(storage_path('app/public/nft_files/' . $nft->file_name)) == true ){
				$result['video_url'] = envDB('BASE_IMAGE_URI') . Storage::url('nft_files/' . $nft->file_name);
			}else if(empty(envDB('IS_AWS_S3')) == false){
				$result['video_url'] = envDB('BASE_AWS_S3_URI') . '/nft-files/' . $nft->file_name;
			}
		}else{
			if(empty(envDB('IS_AWS_S3')) == true){
				$result['image_url'] = envDB('BASE_IMAGE_URI') . Storage::url('nft_files/' . $nft->file_name);
			}else if(empty(envDB('IS_AWS_S3')) == false && file_exists(storage_path('app/public/nft_files/' . $nft->file_name)) == true ){
				$result['image_url'] = envDB('BASE_IMAGE_URI') . Storage::url('nft_files/' . $nft->file_name);
			}else if(empty(envDB('IS_AWS_S3')) == false){
				$result['image_url'] = envDB('BASE_AWS_S3_URI') . '/nft-files/' . $nft->file_name;
			}
		}
		$result['creator'] = $this->getProfile($nft->creator_address);
		$result['total_creation'] = Nft::where('creator_address',$nft->creator_address)->count();
		
		if(empty($nft->token_id) == false){
			$data = $this->netClient()->getNftInfo([
				'contract_address' => envDB('CONTRACT_ADDRESS'),
				'token_id' => $nft->token_id,
			]);
		
			if(empty($data['payload']) == false){
				$nftToken = $data['payload'];
				$result['owner'] = $this->getProfile($nftToken['owner']);
			}
			
			$result['transfers'] = $this->getTransfers($nft->token_id);
			$result['bids'] = $this->getBids($nft->token_id);
			
			$result['listed'] = $this->listed($nft->token_id);
			if($result['listed'] == false){
				$result['offer'] = $this->getOffer($nft->token_id);
				
				if($result['offer']['isForSale'] == true){
					$result['bid'] = $this->getBid($nft->token_id);
				}
				
				$result['price'] = $result['offer']['minValue'];
			}else{
				$result['price'] = $this->getPrice($nft->token_id) / 1000000000000000000;
			}
			
			$result['like'] = $this->likeCun($nft->token_id);
		}else{
			$result['owner'] = $result['creator'];
			$result['like'] = 0;
		}
		
	
		return response()->json($result);
	}
	
	public function getProfile($address){
		$profile = Profile::where("address",$address)->first();
		if(empty($profile) == true){
			$profile = [
				'address' => $address,
				'avatar'  => envDB('BASE_IMAGE_URI') . '/img/profile.svg',
				'auth' => 0
			];
		}else{
			$profile = [
				'address' => $address,
				'avatar'  => $profile->avatar(),
				'name'    => $profile->name,
				'nick'    => $profile->nick,
				'auth'    => $profile->auth
			];
		}
		
		if(empty($profile['name']) == true){
			$profile['name'] = $address;
		}
		if(empty($profile['nick']) == true){
			$profile['nick'] = $address;
		}
		
		return $profile;
	}
	
	public function holdingTokens(Request $request,$owner_address){
		if(empty($request->offset) == true){
			$offset = 0;
		}else{
			$offset = $request->offset;
		}
		
		$data = $this->netClient()->getOwnerNfts([
			'contract_address' => envDB('CONTRACT_ADDRESS'),
			'owner_address' => $owner_address,
			'offset' => $offset,
			'limit' => 6
		]);
		
		$payload = $data['payload'];
		$tokens = [];
		foreach($payload['tokens'] as $token){
			$token = $this->getNftData($token);
			if($token == false){
				continue;
			}
			array_push($tokens,$token);
		}
		
		
		return response()->json([
			'total' => $payload['total_tokens'],
			'data'  => $tokens
		]);
	}
	
	public function getTokenData($nft){
		
		$token['id'] = $nft->id;
		$token['token_id'] = $nft->token_id;
		$token['name'] = $nft->name;
		$token['description'] = $nft->description;
		
		if(substr($nft->file_name,-4) == '.mp4'){
			if(empty(envDB('IS_AWS_S3')) == true){
				$token['video_url'] = envDB('BASE_IMAGE_URI') . Storage::url('nft_files/' . $nft->file_name);
			}else if(empty(envDB('IS_AWS_S3')) == false && file_exists(storage_path('app/public/nft_files/' . $nft->file_name)) == true ){
				$token['video_url'] = envDB('BASE_IMAGE_URI') . Storage::url('nft_files/' . $nft->file_name);
			}else if(empty(envDB('IS_AWS_S3')) == false){
				$token['video_url'] = envDB('BASE_AWS_S3_URI') . '/nft-files/' . $nft->file_name;
			}
		}else{
			if(empty(envDB('IS_AWS_S3')) == true){
				$token['image_url'] = envDB('BASE_IMAGE_URI') . Storage::url('nft_files/' . $nft->file_name);
			}else if(empty(envDB('IS_AWS_S3')) == false && file_exists(storage_path('app/public/nft_files/' . $nft->file_name)) == true ){
				$token['image_url'] = envDB('BASE_IMAGE_URI') . Storage::url('nft_files/' . $nft->file_name);
			}else if(empty(envDB('IS_AWS_S3')) == false){
				$token['image_url'] = envDB('BASE_AWS_S3_URI') . '/nft-files/' . $nft->file_name;
			}
		}
		
		if(empty($nft->tx_hash) == true){
			$token['owner'] = $this->getProfile($nft->creator_address);
			$token['price'] = 0;
			$token['offer'] = [];
			$token['like'] = 0;
		}else{
			$tokenInfo = $this->getCacheTokenInfo($nft->token_id);
			$token['owner'] = $this->getProfile($tokenInfo['owner']);
			$token['price'] = $this->getCachePrice($nft->token_id) / 1000000000000000000;
			$token['offer'] = $this->getCacheOffer($nft->token_id);
			$token['like'] = $this->likeCun($nft->token_id);
		}
		
		return $token;
	}
	public function createdTokens(Request $request,$creator_address){
		if(empty($request->offset) == true){
			$offset = 0;
		}else{
			$offset = $request->offset;
		}
		
		$total = Nft::where('creator_address',$creator_address)->count();
		$nfts = Nft::where('creator_address',$creator_address)->orderBy('created_at','desc')->offset($offset)->limit(6)->get();

		$tokens = [];
		foreach($nfts as $nft){
			$token = $this->getTokenData($nft);
			
			array_push($tokens,$token);
		}
		
		return response()->json([
			'total' => $total,
			'data' => $tokens
		]);
	}
	
    public function createdVerify($request){
		if($request->hasFile('nft_file') == false || $request->file('nft_file')->isValid() == false){
			return response()->json([
				'error' => [
					'message' => "파일을 업로드하여 주시길 바랍니다"
				]
			]);
		}else if(empty($request->nft_name) == true){
			return response()->json([
				'error' => [
					'message' => "이름을 입력해주시길 바랍니다"
				]
			]);
		}else if(empty($request->nft_description) == true){
			return response()->json([
				'error' => [
					'message' => "설명을 입력해주시길 바랍니다"
				]
			]);
		}else if(mb_strlen($request->nft_name,'utf-8') > 30){
			return response()->json([
				'error' => [
					'message' => "이름은 30자를 초과할수 없습니다"
				]
			]);
		}else if(mb_strlen($request->nft_description,'utf-8') > 300){
			return response()->json([
				'error' => [
					'message' => "설명은 300자를 초과할수 없습니다"
				]
			]);
		}
		
		$categoryEnv = CategoryEnv::find($request->nft_category);
		if(empty($categoryEnv) == true){
			return response()->json([
				'error' => [
					'message' => "잘못된 카테고리 입니다"
				]
			]);
		}
		
		$extension = strtolower($request->nft_file->extension());
		if($extension != 'jpg' && $extension != 'png' && $extension != 'gif' && $extension != 'mp4'){
			return response()->json([
				'error' => [
					'message' => "허가된 파일 확장자가 아닙니다"
				]
			]);
		}
		
		$profile = Profile::where('address',$request->auth_address)->first();
		if(empty($profile) == true){
			$auth_profile = false;
		}else{
			if($profile->auth == 1){
				$auth_profile = true;
			}else{
				$auth_profile = false;
			}
		}
		
		$UPLOAD_SIZE_AUTH_AUTHORS = envDB('UPLOAD_SIZE_AUTH_AUTHORS');
		$UPLOAD_SIZE_UNAUTH_AUTHORS = envDB('UPLOAD_SIZE_UNAUTH_AUTHORS');
		
		if($auth_profile == true && $request->file('nft_file')->getSize() > $UPLOAD_SIZE_AUTH_AUTHORS){
			if($UPLOAD_SIZE_AUTH_AUTHORS >= 1000000){
				$size = round($UPLOAD_SIZE_AUTH_AUTHORS / 1000000,1);
				$size .= 'MB';
			}else{
				$size = $UPLOAD_SIZE_AUTH_AUTHORS / 1000;
				$size .= 'KB';
			}
			
			return response()->json([
				'error' => [
					'message' => "인증된 저자는 {$size} 이하의 파일만 업로드하실수 있습니다"
				]
			]);
		}
		
		if($auth_profile == false && $request->file('nft_file')->getSize() > $UPLOAD_SIZE_UNAUTH_AUTHORS){
			if($UPLOAD_SIZE_UNAUTH_AUTHORS >= 1000000){
				$size = round($UPLOAD_SIZE_UNAUTH_AUTHORS / 1000000,1);
				$size .= 'MB';
			}else{
				$size = $UPLOAD_SIZE_UNAUTH_AUTHORS / 1000;
				$size .= 'KB';
			}
			
			return response()->json([
				'error' => [
					'message' => "미인증 저자는 {$size} 이하의 파일만 업로드하실수 있습니다"
				]
			]);
		}
		
		$UPLOAD_FILTER_ADDRESS = strtolower(envDB('UPLOAD_FILTER_ADDRESS'));
		if(strlen($UPLOAD_FILTER_ADDRESS) > 40 && strpos($UPLOAD_FILTER_ADDRESS,strtolower($request->auth_address)) === false){
			return response()->json([
				'error' => [
					'message' => "현재 관리자에게 승인받은 특정 주소만 업로드를 허용중 입니다"
				]
			]);
		}
		
		$UPLOAD_FILTER_TEXT = explode(',',envDB('UPLOAD_FILTER_TEXT'));
		foreach($UPLOAD_FILTER_TEXT as $text){
			if(empty($text) == true){
				continue;
			}
			if(strpos($request->nft_name,$text) !== false || strpos($request->nft_description,$text) !== false){
				return response()->json([
					'error' => [
						'message' => "금지된 단어가 발견되었습니다 [{$text}]"
					]
				]);
			}
			
		}

		
		$UPLOAD_FILTER_IP = explode(',',envDB('UPLOAD_FILTER_IP'));
		if(empty($UPLOAD_FILTER_IP) == false){

			foreach($UPLOAD_FILTER_IP as $benIP){
				if(empty($benIP) == true){
					continue;
				}
				
				$ip = $_SERVER["REMOTE_ADDR"];
				if(strpos($ip,$benIP) !== false){
					return response()->json([
						'error' => [
							'message' => "차단된 IP 입니다"
						]
					]);
				}
			}
		}
		
		return false;
	}
	
	public function created(Request $request){
		$verify = $this->createdVerify($request);
		if(empty($verify) == false){
			return $verify;
		}
			
		$extension = strtolower($request->nft_file->extension());
		$filename = Str::random(30) . "." . $extension;
		
		if(empty(envDB('IS_AWS_S3')) == true){
			$path = $request->nft_file->storeAs('nft_files',$filename,'public');
			if($path == false){
				return response()->json([
					'error' => [
						'message' => "파일 저장에 실패하였 습니다"
					]
				]);
			}
			
			$path = storage_path('app/public/'.$path);
		}else{
			$path = $request->nft_file->path();
			if(Storage::disk('s3')->put('/nft-files/'.$filename,file_get_contents($path),'public') == false){
				return response()->json([
					'error' => [
						'message' => "파일 저장에 실패하였 습니다"
					]
				]);
			}			
		}		


		$imageIPFS = IPFS::add(fopen($path, 'r'));
		if(empty($imageIPFS['Hash']) == true){
			return response()->json([
				'error' => [
					'message' => "IPFS에 이미지 업로드 실패"
				]
			]);	
		}
		IPFS::pin($imageIPFS['Hash']);
		
		$metadata = [
			"name" => $request->nft_name,
			"description" => $request->nft_description,
			"image" => "ipfs://" . $imageIPFS['Hash']
		];
		
		$metadataIPFS = IPFS::add(json_encode($metadata),'metadata.json',['pin' => true]);
		if(empty($metadataIPFS['Hash']) == true){
			return response()->json([
				'error' => [
					'message' => "IPFS에 메타데이터 업로드 실패"
				]
			]);	
		}
		IPFS::pin($metadataIPFS['Hash']);
		
		if(empty(envDB('IS_AWS_S3')) == false){
			unlink($path);
		}
		
		exec("curl https://ipfs.decoo.io/ipfs/" . $imageIPFS['Hash'] . " > /dev/null 2>/dev/null &");
		exec("curl https://ipfs.decoo.io/ipfs/" . $metadataIPFS['Hash'] . " > /dev/null 2>/dev/null &");
		
		$nft = Nft::find($metadataIPFS['Hash']);
		if(empty($nft) == false){
			return response()->json([
				'error' => [
					'message' => "이미 등록된 작품 입니다"
				]
			]);	
		}
		
		
		$id = $metadataIPFS['Hash'];
		$nft = new Nft();
		$nft->id = $id;
		$nft->ipfs_image_hash = $imageIPFS['Hash'];
		$nft->creator_address = $request->auth_address;
		$nft->category = $request->nft_category;
		$nft->name = $request->nft_name;
		$nft->description = $request->nft_description;
		$nft->file_name = $filename;
		$nft->creator_ip = getRemoteAddr();
		$nft->save();
		
		return response()->json([
			'id' => $id
		]);
	}
	
	public function deleted(Request $request){
		if(empty($request->nft_id) == true){
			return response()->json([
				'error' => [
					'message' => "NFT ID 를 입력해주시길 바랍니다"
				]
			]);
		}
		
		$nft = NFT::find($request->nft_id);
		if(empty($nft) == true){
			return response()->json([
				'error' => [
					'message' => "NFT를 찾을수 없습니다"
				]
			]);
		}
		
		if(empty($nft->token_id) == true){
			if($nft->creator_address != $request->auth_address){
				return response()->json([
					'error' => [
						'message' => "미발행 NFT는 생성자만 삭제권한을 가지고 있습니다"
					]
				]);
			}
		}else{
			$tokenInfo = $this->getTokenInfo($nft->token_id);
			if($tokenInfo['owner'] != $request->auth_address){
				return response()->json([
					'error' => [
						'message' => "NFT의 소유자만 삭제권한을 가지고 있습니다"
					]
				]);
			}
		}
		
		$nft->delete();
		
		return response()->json([
			'deleted' => true
		]);
	}
	
	public function txVerify(Request $request,$nft_id){
		$nft = Nft::find($nft_id);
		if(empty($nft) == true){
			return response()->json([
				'error' => [
					'message' => "존재하지 않는 NFT 입니다"
				]
			]);
		}else if(empty($request->tx_hash) == true){
			return response()->json([
				'error' => [
					'message' => "트랜잭션 해시를 입력해주세요"
				]
			]);
		}
		
		LogTxVerify::firstOrCreate([
			'tx_hash' => $request->tx_hash
		]);
		
		for($i=0;$i<300;$i++){
			$result = $this->netClient()->getTransaction([
				'hash' => $request->tx_hash
			]);
			
			if(empty($result['payload']) == false && empty($result['payload']['confirmations']) == false){
				break;
			}
			
			sleep(2);
		}
		
		if(empty($result['payload']) == true){
			return response()->json([
				'error' => [
					'message' => "트랜잭션을 찾을수 없습니다"
				]
			]);
		}
		
		$transaction = $result['payload'];
		if(empty($transaction['logs']) == true || $transaction['status'] == 0){
			return response()->json([
				'error' => [
					'message' => "컨트렉트 실행을 실패한 트랜잭션 입니다"
				]
			]);
		}
		
		
		$verify = false;
		$token_id = 0;
		foreach($transaction['logs'] as $log){
			if(strtolower($log['contract_address']) != strtolower(envDB('CONTRACT_ADDRESS'))){
				continue;
			}
			
			if(count($log['topics']) != 4 || $log['topics'][0] != '0xddf252ad1be2c89b69c2b068fc378daa952ba7f163c4a11628f55a4df523b3ef'){
				continue;
			}
			
			$token_id = hexdec($log['topics'][3]);
			$verify = true;
		}
		
		if($verify == false || $transaction['from'] != $nft->creator_address){
			return response()->json([
				'error' => [
					'message' => "검증에 실패하였습니다"
				]
			]);
		}
		
		$nft->tx_hash  = $request->tx_hash;
		$nft->token_id = $token_id;
		$nft->year_creation = substr($transaction['datetime'],0,4);
		$nft->save();
		
		return response()->json([
			'verify' => true,
			'token_id' => $token_id,
			'year_creation' => $nft->year_creation,
			'transaction' => $transaction
		]);
	}
	
	public function explorer(Request $request,$tab){
		if(empty($request->offset) == true){
			$offset = 0;
		}else{
			$offset = $request->offset;
		}

		
		if($tab == 'all'){
			$nfts = Nft::where('tx_hash','!=','');
		}else{
			$nfts = Nft::where('tx_hash','!=','')->where('category',$tab);
		}
		
		if($tab == 'search' && empty($request->q) == false){
			$nfts = Nft::where('tx_hash','!=','')->where('name','like',"%{$request->q}%");
		}
		
		$total = $nfts->count();
		$nfts = $nfts->orderBy('created_at','desc')->offset($offset)->limit(12)->get();
		
		$tokens = [];
		foreach($nfts as $nft){
			$token = $this->getTokenData($nft);
			
			array_push($tokens,$token);
		}
		
		
		return response()->json([
			'total' => $total,
			'data'  => $tokens
		]);
	}
}
