<?php
namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\Contact;

use App\Models\Transfer;

use App\Models\Nft;

use App\Models\EndAuction;
use App\Models\Purchase;

use App\Models\LogContractEvent;
use App\Models\LogContractCallback;


use BlockSDK;
class CallbackController extends Controller
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

	public function __construct(){
		$blocksdk      = new BlockSDK(envDB('BLOCKSDK_TOKEN'));
		$this->webhook = $blocksdk->createWebHook();
	}

	/*
		//발행
		Minted(address,uint256,uint256,string)
		0xf2cb5e52

		//판매됨
		Purchase(address,address,uint256,uint256,string)
		0xef258f47

		//경매성사
		EndAuction(uint256,uint256)
		0xc8703608
	*/
	public function hexToMethod($hex){
		if(substr($hex,0,10) == '0xf2cb5e52'){//Minted(address,uint256,uint256,string)
			return 'Minted';
		}else if(substr($hex,0,10) == '0xef258f47'){//Purchase(address,address,uint256,uint256,string)
			return 'Purchase';
		}else if(substr($hex,0,10) == '0xc8703608'){//EndAuction(uint256,uint256)
			return 'EndAuction';
		}else if(substr($hex,0,10) == '0x5e4dbe79'){//CreateAuction(address,uint256,uint256,uint256)
			return 'CreateAuction';
		}else if(substr($hex,0,10) == '0xddf252ad'){//CreateAuction(address,uint256,uint256,uint256)
			return 'Transfer';
		}


		return substr($hex,0,10);
	}

	public function check(Request $request){
		/*if($request->api_token != md5(envDB('BLOCKSDK_TOKEN'))){
			return response()->json([
				'error' => [
					'message' => 'Authentication Failed'
				]
			],401);
		}else if($request->event != 'confirmed'){
			return response()->json([
				'error' => [
					'message' => 'Only confirmed transactions'
				]
			],400);
		}
		*/
		$tx = $this->netClient()->getTransaction([
			'hash' => $request->tx_hash
		]);

		if(empty($tx['payload']) == true){
			return response()->json([
				'error' => [
					'message' => 'non-existent transaction'
				]
			],400);
		}

		LogContractCallback::firstOrCreate([
			'tx_hash' => $request->tx_hash
		]);

		$year = date('Y');
		$month = date('m');
		$day  = date('d');
		$hour = date('H');
		$minute = date('i');
		$minute = $minute - ($minute % 5);//5분단위로 저장

		$transfer = null;
		$price = 0;
		$rawtx = $tx['payload'];
		foreach($rawtx['logs'] as $log){
			$method = $this->hexToMethod($log['topics'][0]);
			LogContractEvent::firstOrCreate([
				'tx_hash' => $request->tx_hash,
				'method' => $method
			]);

			if($method == 'Minted'){
				//발행 횟수 증가
				DB::table('count_mint')
				->updateOrInsert(
					['year' => $year,'month' => $month,'day' => $day,'hour' => $hour,'minute' => $minute],
					['cun' => DB::raw('cun+1')]
				);

				//발행 트랜잭션 컨펌 이전에 창을 닫앗을경우 콜백에서 처리 진행
				sleep(2);
				$nft_id = hex2bin(substr($log['data'],258));
				$nft = Nft::find($nft_id);
				if(empty($nft->token_id) == true && $rawtx['from'] == $nft->creator_address){
                    $nft->tx_hash =  $request->tx_hash;
                    $nft->token_id =  hexdec(substr($log['data'],66,64));
                    $nft->year_creation =  date('Y');
                    $nft->save(); 
				}
			}if($method == 'Purchase'){
				$price = hexdec(substr($log['data'],0,66)) / 1000000000000000000;

				//즉시 판매 금액 및 횟수 증가
				DB::table('count_purchase')
				->updateOrInsert(
					['year' => $year,'month' => $month,'day' => $day,'hour' => $hour,'minute' => $minute],
					['total' => DB::raw('total+' . $price),'cun' => DB::raw('cun+1')]
				);

				$token_id = hexdec(substr($log['data'],66,64));

				$nft      = Nft::where('token_id',$token_id)->first();
				Purchase::firstOrCreate([
					'nft_id'  => $nft->id,
					'tx_hash' => $request->tx_hash
				]);
			}if($method == 'EndAuction'){
				$price = hexdec(substr($log['data'],66,64)) / 1000000000000000000;

				//경매 성사 금액 및 횟수 증가
				DB::table('count_end_auction')
				->updateOrInsert(
					['year' => $year,'month' => $month,'day' => $day,'hour' => $hour,'minute' => $minute],
					['total' => DB::raw('total+' . $price),'cun' => DB::raw('cun+1')]
				);

				$token_id = hexdec(substr($log['data'],0,66));
				$nft      = Nft::where('token_id',$token_id)->first();

				EndAuction::firstOrCreate([
					'nft_id'  => $nft->id,
					'tx_hash' => $request->tx_hash
				]);
			}if($method == 'CreateAuction'){
				//경매 생성 횟수 증가
				DB::table('count_create_auction')
				->updateOrInsert(
					['year' => $year,'month' => $month,'day' => $day,'hour' => $hour,'minute' => $minute],
					['cun' => DB::raw('cun+1')]
				);
			}if($method == 'Transfer'){
				//소유자가 변경됨
				$transfer = [
					'tx_hash' => $request->tx_hash,
					'from'    => '0x' . substr($log['topics'][1],26),
					'to'      => '0x' . substr($log['topics'][2],26),
					'value'   => 0
				];
			}
		}

		if(empty($transfer) == false){
			$transfer['value'] = $price;
			Transfer::firstOrCreate($transfer);
		}

		return response()->json([
		]);
	}

}
