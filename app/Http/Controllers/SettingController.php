<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

use App\Models\User;

use App\Models\LaravelEnv;
use App\Models\FrontEnv;
use App\Models\CategoryEnv;

use BlockSDK;
class SettingController extends Controller
{
	public function netClient(){
		if(envDB('BASE_MAINNET') == 'ETH')
			return BlockSDK::createEthereum(envDB('BLOCKSDK_TOKEN'));
		if(envDB('BASE_MAINNET') == 'BSC')
			return BlockSDK::createBinanceSmart(envDB('BLOCKSDK_TOKEN'));
		if(envDB('BASE_MAINNET') == 'KLAY')
			return BlockSDK::createKlaytn(envDB('BLOCKSDK_TOKEN'));
	}
	
	public function setBackendEnv($name,$value){
		$env = LaravelEnv::firstOrCreate(
			[
				'name' => $name
			],
			[
				'value' => $value
			]
		);
		$env->value = $value;
		$env->save();
		
		return $env;
	}
	public function getBackendEnv($name){
		$env = LaravelEnv::find($name);
		if(empty($env) == true){
			return "";
		}
		
		return $env->value;
	}
	
	
	public function setFrontEnv($name,$value){
		$env = FrontEnv::firstOrCreate(
			[
				'name' => $name
			],
			[
				'value' => $value
			]
		);
		$env->value = $value;
		$env->save();
		
		return $env;
	}
	public function getFrontEnv($name){
		$env = FrontEnv::find($name);
		if(empty($env) == true){
			return "";
		}
		
		return $env->value;
	}
	
	public function getBackend(Request $request){
		$app_name = $this->getBackendEnv('APP_NAME');
		$base_description = $this->getBackendEnv('BASE_DESCRIPTION');
		$app_url = $this->getBackendEnv('APP_URL');
		$app_keyword = $this->getBackendEnv('APP_KEYWORD');
		
		$aws_access_key = substr($this->getBackendEnv('AWS_ACCESS_KEY_ID'),0,4) . "********";
		$aws_secret_access_key = substr($this->getBackendEnv('AWS_SECRET_ACCESS_KEY'),0,4) . "********";
		$aws_region = $this->getBackendEnv('AWS_DEFAULT_REGION');
		$aws_bucket = $this->getBackendEnv('AWS_BUCKET');
		$aws_s3_uri = $this->getBackendEnv('BASE_AWS_S3_URI');
		$aws_mail_from_address = $this->getBackendEnv('MAIL_FROM_ADDRESS');
		$aws_mail_from_name = $this->getBackendEnv('MAIL_FROM_NAME');

        $contract_address = $this->getBackendEnv('CONTRACT_ADDRESS');
        $eth_address = $this->getBackendEnv('ETH_ADDRESS');
        $factory_address = $this->getBackendEnv('FACTORY_CONTRACT_ADDRESS');
        $router_address = $this->getBackendEnv('ROUTER_CONTRACT_ADDRESS');
        $staking_address = $this->getBackendEnv('STAKING_CONTRACT_ADDRESS');


		return response()->json([
			'data' => [
				'web' => [
					'name' => $app_name,
					'url' => $app_url,
					'description' => $base_description,
					'keyword' => $app_keyword,
				],
				'aws' => [
					'accessKey' => $aws_access_key,
					'secretKey' => $aws_secret_access_key,
					'region' => $aws_region,
					'bucket' => $aws_bucket,
					'imageURI' => $aws_s3_uri,
					'senderEmail' => $aws_mail_from_address,
					'senderName' => $aws_mail_from_name,
				],
                'contract' => [
                    'contract_address' => $contract_address,
                    'eth_address' => $eth_address,
                    'factory_address' => $factory_address,
                    'router_address' => $router_address,
                    'staking_address' => $staking_address,
                ]
			]
		]);
	}

	public function getDex(Request $req){


        $weth_address = $this->getBackendEnv('ETH_ADDRESS');
        $factory_address = $this->getBackendEnv('FACTORY_CONTRACT_ADDRESS');
        $router_address = $this->getBackendEnv('ROUTER_CONTRACT_ADDRESS');
        $staking_address = $this->getBackendEnv('STAKING_CONTRACT_ADDRESS');
        $token_address = $this->getFrontEnv('VUE_APP_TOKEN_ADDRESS');
        
        return response()->json([
            'data' => [
                'contract' => [
                    'token_address' => $token_address,
                    'weth_address' => $weth_address,
                    'factory_address' => $factory_address,
                    'router_address' => $router_address,
                    'staking_address' => $staking_address,
                ]
            ]
        ]);
    }
	
	public function getFront(Request $request){
		
		$app_name = $this->getFrontEnv('VUE_APP_NAME');
		$base_mainnet = $this->getFrontEnv('VUE_APP_BASE_MAINNET');
		$base_smart_contract_address = $this->getFrontEnv('VUE_APP_BASE_SMART_CONTRACT_ADDRESS');
		$base_wallet = $this->getFrontEnv('VUE_APP_BASE_WALLET');
		$base_api_uri = $this->getFrontEnv('VUE_APP_BASE_API_URI');
		$base_exchange_api_uri = $this->getFrontEnv('VUE_APP_BASE_EXCHANGE_API_URI');
		
		$company_name = $this->getFrontEnv('VUE_APP_COMPANY_NAME');
		$company_text = $this->getFrontEnv('VUE_APP_COMPANY_TEXT');
		$company_address = $this->getFrontEnv('VUE_APP_COMPANY_ADDRESS');
		$company_number = $this->getFrontEnv('VUE_APP_COMPANY_NUMBER');
		$company_description = $this->getFrontEnv('VUE_APP_COMPANY_TEXT');
		$company_phone = $this->getFrontEnv('VUE_APP_COMPANY_PHONE');
		$company_email = $this->getFrontEnv('VUE_APP_COMPANY_EMAIL');
		$company_facebook = $this->getFrontEnv('VUE_APP_COMPANY_FACEBOOK');
		$company_twitter = $this->getFrontEnv('VUE_APP_COMPANY_TWITTER');
		$company_instagram = $this->getFrontEnv('VUE_APP_COMPANY_INSTAGRAM');
		$company_blog = $this->getFrontEnv('VUE_APP_COMPANY_BLOG');
		$company_telegram = $this->getFrontEnv('VUE_APP_COMPANY_TELEGRAM');
		$footer_text = $this->getFrontEnv('VUE_APP_FOOTER_TEXT');
		
		$analytics = $this->getFrontEnv('ANALYTICS');
		$service = $this->getFrontEnv('VUE_APP_SERVICE');
		$privacy = $this->getFrontEnv('VUE_APP_PRIVACY');

		$router_address = $this->getFrontEnv('VUE_APP_ROUTER_ADDRESS');
		$factory_address = $this->getFrontEnv('VUE_APP_FACTORY_ADDRESS');
		$staking_address = $this->getFrontEnv('VUE_APP_STAKING_ADDRESS');
		$token_address = $this->getFrontEnv('VUE_APP_TOKEN_ADDRESS');
		$weth_address = $this->getFrontEnv('VUE_APP_WETH_ADDRESS');
//		$smart_contract_address = $this->getFrontEnv('VUE_APP_BASE_SMART_CONTRACT_ADDRESS');

		return response()->json([
			'data' => [
				'web' => [
					'name' => $app_name,
					'mainnet' => $base_mainnet,
					'contract_address' => $base_smart_contract_address,
					'wallet' => $base_wallet,
					'api_uri' => $base_api_uri,
					'exchange_api_uri' => $base_exchange_api_uri,
				],
				'contract' => [
				    'router_address' => $router_address,
                    'factory_address' => $factory_address,
                    'staking_address' => $staking_address,
                    'token_address' => $token_address,
                    'weth_address' => $weth_address,
                    'smart_contract_address' => $base_smart_contract_address
                ],
				'company' => [
					'name' => $company_name,
					'text' => $company_text,
					'address' => $company_address,
					'number' => $company_number,
					'description' => $company_description,
					'phone' => $company_phone,
					'email' => $company_email,
					'facebook' => $company_facebook,
					'twitter' => $company_twitter,
					'instagram' => $company_instagram,
					'blog' => $company_blog,
					'telegram' => $company_telegram,
					'footer_text' => $footer_text,
				],
				'analytics' => [
					'source' => $analytics,
				],
				'service' => [
					'source' => $service,
				],
				'privacy' => [
					'source' => $privacy,
				],
			]
		]);
	}
	
	public function getDexMain(Request $request){
		$mainnet = $this->getBackendEnv('BASE_MAINNET');
		$blocksdk = substr($this->getBackendEnv('BLOCKSDK_TOKEN'),0,4) . "********";
		$contract_cache_time = $this->getBackendEnv('CACHE_TIME_NFT');
		
//		$auth_nft_size = $this->getBackendEnv('UPLOAD_SIZE_AUTH_AUTHORS');
//		$unauth_nft_size = $this->getBackendEnv('UPLOAD_SIZE_UNAUTH_AUTHORS');
//		$profile_size = $this->getBackendEnv('UPLOAD_SIZE_PROFILE');
//		$cover_size = $this->getBackendEnv('UPLOAD_SIZE_COVER');
//
//		$address_filter = $this->getBackendEnv('UPLOAD_FILTER_ADDRESS');
//		$filter = $this->getBackendEnv('UPLOAD_FILTER_TEXT');
//		$ip = $this->getBackendEnv('UPLOAD_FILTER_IP');
//
//		$categoryEnv = CategoryEnv::get();
//
//		$owner = $this->netClient()->getContractRead([
//			'contract_address' => envDB('CONTRACT_ADDRESS'),
//			'method' => 'owner',
//			'return_type' => 'address',
//			'parameter_type' => [],
//			'parameter_data' => []
//		]);
//		$ownerAddress = null;
//		if(empty($owner['payload']) == false){
//			$ownerAddress = $owner['payload']['result'];
//		}
//
//		$fee = $this->netClient()->getContractRead([
//			'contract_address' => envDB('CONTRACT_ADDRESS'),
//			'method' => 'feeRate',
//			'return_type' => 'uint256',
//			'parameter_type' => [],
//			'parameter_data' => []
//		]);
//		$feeRate = null;
//		if(empty($fee['payload']) == false){
//			$feeRate = $fee['payload']['result'];
//		}
//
//		$cancelFee = $this->netClient()->getContractRead([
//			'contract_address' => envDB('CONTRACT_ADDRESS'),
//			'method' => 'cancelFeeRate',
//			'return_type' => 'uint256',
//			'parameter_type' => [],
//			'parameter_data' => []
//		]);
//		$cancelFeeRate = null;
//		if(empty($cancelFee['payload']) == false){
//			$cancelFeeRate = $cancelFee['payload']['result'];
//		}
//
//		$pausedData = $this->netClient()->getContractRead([
//			'contract_address' => envDB('CONTRACT_ADDRESS'),
//			'method' => 'paused',
//			'return_type' => 'bool',
//			'parameter_type' => [],
//			'parameter_data' => []
//		]);
//		$paused = false;
//		if(empty($pausedData['payload']) == false){
//			$paused = $pausedData['payload']['result'];
//		}
		
		return response()->json([
			'data' => [
//				'contract' => [
//					'owner' => $ownerAddress,
//					'feeRate' => $feeRate,
//					'cancelFeeRate' => $cancelFeeRate,
//					'paused' => $paused,
//				],
				'dex' => [
					'mainnet' => $mainnet,
//					'image_uri' => $image_uri,
//					'ipfs' => $ipfs,
					'blocksdk' => $blocksdk,
//					'contract_address' => $contract_address,
					'contract_cache_time' => $contract_cache_time,
				],
//				'upload' => [
//					'auth_nft_size' => $auth_nft_size,
//					'unauth_nft_size' => $unauth_nft_size,
//					'profile_size' => $profile_size,
//					'cover_size' => $cover_size,
//					'address_filter' => $address_filter,
//				],
//				'filter' => [
//					'source' => $filter,
//				],
//				'ip' => [
//					'source' => $ip,
//				],
//				'category' => $categoryEnv
			]
		]);
	}
	
	public function setBackendWeb($request){
		if(empty($request->name) == true){
			return response()->json([
				'error' => [
					'message' => '마켓 이름을 입력해주세요'
				],
			]);
		}else if(empty($request->description) == true){
			return response()->json([
				'error' => [
					'message' => '마켓 설명을 입력해주세요'
				],
			]);
		}else if(empty($request->url) == true){
			return response()->json([
				'error' => [
					'message' => '마켓 URL 입력해주세요'
				],
			]);
		}else if(empty($request->keyword) == true){
			return response()->json([
				'error' => [
					'message' => '마켓 키워드를 입력해주세요'
				],
			]);
		}
		
		$this->setBackendEnv('APP_NAME',$request->name);
		$this->setBackendEnv('BASE_DESCRIPTION',$request->description);
		$this->setBackendEnv('APP_URL',$request->url);
		$this->setBackendEnv('APP_KEYWORD',$request->keyword);
		
		return false;
	}
	
	public function setBackendAws(Request $request){
		if(empty($request->accessKey) == true){
			return response()->json([
				'error' => [
					'message' => '액세스 키를 입력해주세요'
				],
			]);
		}else if(empty($request->secretKey) == true){
			return response()->json([
				'error' => [
					'message' => '시크릿 키를 입력해주세요'
				],
			]);
		}else if(empty($request->region) == true){
			return response()->json([
				'error' => [
					'message' => '리전을 입력해주세요'
				],
			]);
		}else if(empty($request->bucket) == true){
			return response()->json([
				'error' => [
					'message' => '버킷을 입력해주세요'
				],
			]);
		}else if(empty($request->senderEmail) == true){
			return response()->json([
				'error' => [
					'message' => '전송자 이메일을 입력해주세요'
				],
			]);
		}else if(empty($request->senderName) == true){
			return response()->json([
				'error' => [
					'message' => '전송자 이름을 입력해주세요'
				],
			]);
		}else if(empty($request->imageURI) == true){
			return response()->json([
				'error' => [
					'message' => '이미지 URI를 입력해주세요'
				],
			]);
		}
		
		if(substr($request->accessKey,-4) != '****'){
			$this->setBackendEnv('AWS_ACCESS_KEY_ID',$request->accessKey);
		}
		
		if(substr($request->secretKey,-4) != '****'){
			$this->setBackendEnv('AWS_SECRET_ACCESS_KEY',$request->secretKey);
		}
		
		$this->setBackendEnv('AWS_DEFAULT_REGION',$request->region);
		$this->setBackendEnv('AWS_SES_REGION',$request->region);
		$this->setBackendEnv('AWS_BUCKET',$request->bucket);
		$this->setBackendEnv('MAIL_FROM_ADDRESS',$request->senderEmail);
		$this->setBackendEnv('MAIL_FROM_NAME',$request->senderName);
		$this->setBackendEnv('BASE_AWS_S3_URI',$request->imageURI);
		$this->setBackendEnv('IS_AWS_S3',1);
		
		return false;
	}

    public function setBackendContract($request){
        if(empty($request->contract_address) == true){
            return response()->json([
                'error' => [
                    'message' => '컨트렉트 주소를 입력해주세요'
                ],
            ]);
        }else if(empty($request->router_address) == true){
            return response()->json([
                'error' => [
                    'message' => '라우터 컨트렉트 주소를 입력해주세요'
                ],
            ]);
        }else if(empty($request->factory_address) == true){
            return response()->json([
                'error' => [
                    'message' => '팩토리 컨트렉트 주소를 입력해주세요'
                ],
            ]);
        }else if(empty($request->staking_address) == true){
            return response()->json([
                'error' => [
                    'message' => '스테이킹 컨트렉트 주소를 입력해주세요'
                ],
            ]);
        }else if(empty($request->eth_address) == true){
            return response()->json([
                'error' => [
                    'message' => '이더리움 주소 입력해주세요'
                ],
            ]);
        }

        $this->setBackendEnv('CONTRACT_ADDRESS',$request->contract_address);
        $this->setBackendEnv('ROUTER_CONTRACT_ADDRESS',$request->router_address);
        $this->setBackendEnv('FACTORY_CONTRACT_ADDRESS',$request->factory_address);
        $this->setBackendEnv('STAKING_CONTRACT_ADDRESS',$request->staking_address);
        $this->setBackendEnv('ETH_ADDRESS',$request->eth_address);

        return false;
    }

    public function setEnvContract($request){
        if(empty($request->router_address) == true){
            return response()->json([
                'error' => [
                    'message' => '라우터 컨트렉트 주소를 입력해주세요'
                ],
            ]);
        }else if(empty($request->factory_address) == true){
            return response()->json([
                'error' => [
                    'message' => '팩토리 컨트렉트 주소를 입력해주세요'
                ],
            ]);
        }else if(empty($request->staking_address) == true){
            return response()->json([
                'error' => [
                    'message' => '스테이킹 컨트렉트 주소를 입력해주세요'
                ],
            ]);
        }else if(empty($request->weth_address) == true){
            return response()->json([
                'error' => [
                    'message' => '메인넷 토큰 주소 입력해주세요'
                ],
            ]);
        }else if(empty($request->token_address) == true){
            return response()->json([
                'error' => [
                    'message' => '발행 토큰 주소 입력해주세요'
                ],
            ]);
        }

        $this->setBackendEnv('ROUTER_CONTRACT_ADDRESS',$request->router_address);
        $this->setBackendEnv('FACTORY_CONTRACT_ADDRESS',$request->factory_address);
        $this->setBackendEnv('STAKING_CONTRACT_ADDRESS',$request->staking_address);
        $this->setBackendEnv('ETH_ADDRESS',$request->weth_address);

        $this->setFrontEnv('VUE_APP_ROUTER_ADDRESS',$request->router_address);
        $this->setFrontEnv('VUE_APP_FACTORY_ADDRESS',$request->factory_address);
        $this->setFrontEnv('VUE_APP_STAKING_ADDRESS',$request->staking_address);
        $this->setFrontEnv('VUE_APP_TOKEN_ADDRESS',$request->token_address);
        $this->setFrontEnv('VUE_APP_WETH_ADDRESS',$request->weth_address);

        return false;
    }


    public function setDex(Request $req){
        $result = $this->setEnvContract($req);

        if($result != false){
            return $result;
        }

        return response()->json([
            'data' => true,
        ]);


    }

	public function setBackend(Request $request){
		if($request->type == 'web'){
			$result = $this->setBackendWeb($request);
		}else if($request->type == 'aws'){
			$result = $this->setBackendAws($request);
		}else if($request->type == 'contract'){
            $result = $this->setBackendContract($request);
        }
		
		if($result != false){
			return $result;
		}
		
		return response()->json([
			'data' => true,
		]);
	}
	
	public function setFrontWeb($request){
		if(empty($request->name) == true){
			return response()->json([
				'error' => [
					'message' => '마켓 이름을 입력해주세요'
				],
			]);
		}else if(empty($request->mainnet) == true){
			return response()->json([
				'error' => [
					'message' => '메인넷을 선택해주세요'
				],
			]);
		}else if(empty($request->contract_address) == true){
			return response()->json([
				'error' => [
					'message' => '계약 주소를 입력해주세요'
				],
			]);
		}else if(empty($request->wallet) == true){
			return response()->json([
				'error' => [
					'message' => '지갑을 선택해주세요'
				],
			]);
		}else if(empty($request->api_uri) == true){
			return response()->json([
				'error' => [
					'message' => 'API 주소를 입력 해주세요'
				],
			]);
		}else if(empty($request->exchange_api_uri) == true){
			return response()->json([
				'error' => [
					'message' => '거래소 API 주소를 입력 해주세요'
				],
			]);
		}
		
		$this->setFrontEnv('VUE_APP_NAME',$request->name);
		$this->setFrontEnv('VUE_APP_BASE_MAINNET',$request->mainnet);
		$this->setFrontEnv('VUE_APP_BASE_SMART_CONTRACT_ADDRESS',$request->contract_address);
		$this->setFrontEnv('VUE_APP_BASE_WALLET',$request->wallet);
		$this->setFrontEnv('VUE_APP_BASE_API_URI',$request->api_uri);
		$this->setFrontEnv('VUE_APP_BASE_EXCHANGE_API_URI',$request->exchange_api_uri);
		
		return false;
	}
	
	public function setFrontCompany($request){
		
		$this->setFrontEnv('VUE_APP_COMPANY_NAME',$request->name);
		$this->setFrontEnv('VUE_APP_COMPANY_ADDRESS',$request->address);
		$this->setFrontEnv('VUE_APP_COMPANY_NUMBER',$request->number);
		$this->setFrontEnv('VUE_APP_COMPANY_TEXT',$request->description);
		$this->setFrontEnv('VUE_APP_COMPANY_PHONE',$request->phone);
		$this->setFrontEnv('VUE_APP_COMPANY_EMAIL',$request->email);
		$this->setFrontEnv('VUE_APP_COMPANY_FACEBOOK',$request->facebook);
		$this->setFrontEnv('VUE_APP_COMPANY_TWITTER',$request->twitter);
		$this->setFrontEnv('VUE_APP_COMPANY_INSTAGRAM',$request->instagram);
		$this->setFrontEnv('VUE_APP_COMPANY_BLOG',$request->blog);
		$this->setFrontEnv('VUE_APP_COMPANY_TELEGRAM',$request->telegram);
		$this->setFrontEnv('VUE_APP_FOOTER_TEXT',$request->footer_text);
		
		return false;
	}

    public function setFrontContract($request){

        $this->setFrontEnv('VUE_APP_ROUTER_ADDRESS',$request->router_address);
        $this->setFrontEnv('VUE_APP_FACTORY_ADDRESS',$request->factory_address);
        $this->setFrontEnv('VUE_APP_STAKING_ADDRESS',$request->staking_address);
        $this->setFrontEnv('VUE_APP_TOKEN_ADDRESS',$request->token_address);
        $this->setFrontEnv('VUE_APP_WETH_ADDRESS',$request->weth_address);
        $this->setFrontEnv('VUE_APP_BASE_SMART_CONTRACT_ADDRESS',$request->smart_contract_address);

        return false;
    }
	
	public function setFrontAnalytics($request){
		$this->setFrontEnv('ANALYTICS',$request->source);
		return false;
	}
	
	public function setFrontService($request){
		$this->setFrontEnv('VUE_APP_SERVICE',$request->source);
		return false;
	}
	
	public function setFrontPrivacy($request){
		$this->setFrontEnv('VUE_APP_PRIVACY',$request->source);
		return false;
	}
	
	public function setFront(Request $request){
		if($request->type == 'web'){
			$result = $this->setFrontWeb($request);
		}else if($request->type == 'company'){
			$result = $this->setFrontCompany($request);
		}else if($request->type == 'analytics'){
			$result = $this->setFrontAnalytics($request);
		}else if($request->type == 'service'){
			$result = $this->setFrontService($request);
		}else if($request->type == 'privacy'){
			$result = $this->setFrontPrivacy($request);
		}else if($request->type == "contract"){
		    $result = $this->setFrontContract($request);
//            return $request->all();
//            exit;
        }
		
		if($result != false){
			return $result;
		}
		
		return response()->json([
			'data' => true,
		]);
	}
	
	
	public function setNftEnv($request){
		if(empty($request->mainnet) == true){
			return response()->json([
				'error' => [
					'message' => '메인넷을 선택해주세요'
				],
			]);
		}else if(empty($request->image_uri) == true){
			return response()->json([
				'error' => [
					'message' => '이미지 주소 앞부분을 입력해 주세요'
				],
			]);
		}else if(empty($request->ipfs) == true){
			return response()->json([
				'error' => [
					'message' => 'IPFS 게이트웨이 주소를 입력해주세요'
				],
			]);
		}else if(empty($request->blocksdk) == true){
			return response()->json([
				'error' => [
					'message' => 'BLOCKSDK 토큰을 입력 해주세요'
				],
			]);
		}else if(empty($request->contract_address) == true){
			return response()->json([
				'error' => [
					'message' => '스마트 계약 주소를 입력 해주세요'
				],
			]);
		}else if(empty($request->contract_cache_time) == true){
			return response()->json([
				'error' => [
					'message' => '스마트 계약 캐시 타임 입력 해주세요'
				],
			]);
		}
		
		$this->setBackendEnv('BASE_MAINNET',$request->mainnet);
		$this->setBackendEnv('BASE_IMAGE_URI',$request->image_uri);
		$this->setBackendEnv('BASE_IPFS_GATEWAY',$request->ipfs);
		if(substr($request->blocksdk,-4) != '****'){
			$this->setBackendEnv('BLOCKSDK_TOKEN',$request->blocksdk);
		}
		$this->setBackendEnv('CONTRACT_ADDRESS',$request->contract_address);
		$this->setBackendEnv('CACHE_TIME_NFT',$request->contract_cache_time);
		
		return false;
	}
	
	public function setNftUpload($request){
		if(empty($request->auth_nft_size) == true){
			return response()->json([
				'error' => [
					'message' => '인증된 저자의 NFT 업로드 최대 사이즈를 입력해주세요'
				],
			]);
		}else if(empty($request->unauth_nft_size) == true){
			return response()->json([
				'error' => [
					'message' => '미인증된 저자의 NFT 업로드 최대 사이즈를 입력해주세요'
				],
			]);
		}else if(empty($request->profile_size) == true){
			return response()->json([
				'error' => [
					'message' => '프로필 사진 업로드 최대 사이즈를 입력해주세요'
				],
			]);
		}else if(empty($request->cover_size) == true){
			return response()->json([
				'error' => [
					'message' => '커버 사진 업로드 최대 사이즈를 입력해주세요'
				],
			]);
		}
		
		$this->setBackendEnv('UPLOAD_SIZE_AUTH_AUTHORS',$request->auth_nft_size);
		$this->setBackendEnv('UPLOAD_SIZE_UNAUTH_AUTHORS',$request->unauth_nft_size);
		$this->setBackendEnv('UPLOAD_SIZE_PROFILE',$request->profile_size);
		$this->setBackendEnv('UPLOAD_SIZE_COVER',$request->cover_size);
		$this->setBackendEnv('UPLOAD_FILTER_ADDRESS',$request->address_filter);
		
		return false;
	}
	
	public function setNftFilter($request){
		$this->setBackendEnv('UPLOAD_FILTER_TEXT',strip_tags($request->source));
		return false;
	}
	
	public function setNftIP($request){
		$this->setBackendEnv('UPLOAD_FILTER_IP',strip_tags($request->source));
		return false;
	}

	public function setDexMain(Request $request){

	    if ($request->type == 'dex'){
            if(empty($request->mainnet) == true){
                return response()->json([
                    'error' => [
                        'message' => '메인넷을 선택해주세요'
                    ],
                ]);
            }else if(empty($request->blocksdk) == true){
                return response()->json([
                    'error' => [
                        'message' => 'BLOCKSDK 토큰을 입력 해주세요'
                    ],
                ]);
            }else if(empty($request->contract_cache_time) == true){
                return response()->json([
                    'error' => [
                        'message' => '스마트 계약 캐시 타임 입력 해주세요'
                    ],
                ]);
            }
            $this->setBackendEnv('BASE_MAINNET',$request->mainnet);
            if(substr($request->blocksdk,-4) != '****'){
                $this->setBackendEnv('BLOCKSDK_TOKEN',$request->blocksdk);
            }
            $this->setBackendEnv('CACHE_TIME_NFT',$request->contract_cache_time);
        }

//		if($request->type == 'nft'){
//			$result = $this->setNftEnv($request);
//		}else if($request->type == 'category'){
//			$result = $this->setNftCategory($request);
//		}else if($request->type == 'upload'){
//			$result = $this->setNftUpload($request);
//		}else if($request->type == 'filter'){
//			$result = $this->setNftFilter($request);
//		}else if($request->type == 'ip'){
//			$result = $this->setNftIP($request);
//		}
		
//		if($result != false){
//			return $result;
//		}
		
		return response()->json([
			'data' => true,
		]);
	}

}