<?php

namespace App\Http\Controllers;

use App\Models\CoinInfo;
use App\Models\ContractDB;
use App\Models\Staking;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

use App\Mails\AmazonSes;

use App\Models\Nft;
use App\Models\Contact;
use App\Models\AuthAuthor;
use App\Models\ApplyAuthAuthor;
use App\Models\LogApplyAuthAuthor;
use App\Models\User;
use App\Models\UserPrivilege;
use App\Models\EndAuction;
use App\Models\Purchase;
use App\Models\Profile;
use App\Models\CacheNft;
use App\Models\Visit;
use App\Models\DayVisit;


use BlockSDK;
use Illuminate\Support\Str;

class DashController extends Controller
{
    public function netClient()
    {
        if (envDB('BASE_MAINNET') == 'ETH')
            return BlockSDK::createEthereum(envDB('BLOCKSDK_TOKEN'));
        if (envDB('BASE_MAINNET') == 'BSC')
            return BlockSDK::createBinanceSmart(envDB('BLOCKSDK_TOKEN'));
        if (envDB('BASE_MAINNET') == 'KLAY')
            return BlockSDK::createKlaytn(envDB('BLOCKSDK_TOKEN'));
    }

//	public function getCache($id){
//		$cache = CacheNft::find($id);
//		if(empty($cache) == true){
//			return false;
//		}else if((strtotime($cache->updated_at) + envDB('CACHE_TIME_NFT')) < time() ){
//			return false;//지정된 캐시시간보다 길어졋을경우
//		}
//		return CacheNft::find($id);
//	}
//	public function cacheSave($id,$hex){
//		$cacheNft = CacheNft::find($id);
//		if(empty($cacheNft) == true){
//			$cacheNft = new CacheNft();
//		}
//
//		$cacheNft->id = $id;
//		$cacheNft->data = $hex;
//		$cacheNft->save();
//	}
    public function hexToBool($hex)
    {
        return (bool)hexdec(substr($hex, 0, 64));
    }

    public function hexToDec($hex)
    {
        return hexdec(substr($hex, 0, 64));
    }

    public function hexToOffer($hex)
    {
        $isForSale = (bool)hexdec(substr($hex, 0, 64));
        $seller = substr($hex, 64, 64);
        $minValue = hexdec(substr($hex, 128, 64)) / 1000000000000000000;
        $endTime = hexdec(substr($hex, 192, 64));

        return [
            'isForSale' => $isForSale,
            'seller' => $seller,
            'minValue' => $minValue,
            'endTime' => $endTime,
        ];
    }

    public function getProfile($address)
    {
        $profile = Profile::where("address", $address)->first();
        if (empty($profile) == true) {
            $profile = [
                'address' => $address,
                'avatar' => envDB('BASE_IMAGE_URI') . '/img/profile.svg',
                'auth' => 0
            ];
        } else {
            $profile = [
                'address' => $address,
                'avatar' => $profile->avatar(),
                'name' => $profile->name,
                'nick' => $profile->nick,
                'auth' => $profile->auth
            ];
        }

        if (empty($profile['name']) == true) {
            $profile['name'] = $address;
        }
        if (empty($profile['nick']) == true) {
            $profile['nick'] = $address;
        }

        return $profile;
    }

    public function getCachePrice($token_id)
    {
        $result = $this->getCache('price_' . $token_id);
        if (empty($result) == true) {
            return $this->getPrice($token_id);
        }

        return $this->hexToDec($result->data);
    }

    public function getCacheOffer($token_id)
    {
        $result = $this->getCache('offer_' . $token_id);
        if (empty($result) == true) {
            return $this->getOffer($token_id);
        }

        return $this->hexToOffer($result->data);
    }

    public function getOffer($token_id)
    {
        $data = $this->netClient()->getContractRead([
            'contract_address' => envDB('CONTRACT_ADDRESS'),
            'method' => 'offers',
            'return_type' => 'bool',
            'parameter_type' => ['uint256'],
            'parameter_data' => [$token_id]
        ]);

        $data = $data['payload'];

        $hex = substr($data['hex'], 2);
        $this->cacheSave('offer_' . $token_id, $hex);

        return $this->hexToOffer($hex);
    }

    public function getPrice($token_id)
    {
        $data = $this->netClient()->getContractRead([
            'contract_address' => envDB('CONTRACT_ADDRESS'),
            'method' => 'price',
            'return_type' => 'uint256',
            'parameter_type' => ['uint256'],
            'parameter_data' => [$token_id]
        ]);

        $data = $data['payload'];

        $hex = substr($data['hex'], 2);
        $this->cacheSave('price_' . $token_id, $hex);

        return $this->hexToDec($hex);
    }

    public function getNftData($tokenInfo)
    {
        $nft = Nft::where('token_id', $tokenInfo['token_id'])->first();
        if (empty($nft) == true) {
            return false;
        }

        $tokenInfo = $nft;
        $tokenInfo->file_url = $nft->file();


        return $tokenInfo;
    }


    //대시보드 통계 데이터
    public function get(Request $request)
    {
        $year = date('Y');
        $month = date('m');

        if ($month == 1) {
            $prevYear = $year - 1;
            $prevMonth = 12;
        } else {
            $prevYear = $year;
            $prevMonth = $month - 1;
        }

        $lastMonthMint = DB::table('count_mint')->where('year', $year)->where('month', $month)->orderBy('id', 'desc')->groupBy('year', 'month')->select(DB::raw('sum(cun) as cun,year,month,day'))->first();
        $prevMonthMint = DB::table('count_mint')->where('year', $prevYear)->where('month', $prevMonth)->orderBy('id', 'desc')->groupBy('year', 'month')->select(DB::raw('sum(cun) as cun,year,month,day'))->first();
        $lastMonthMintCun = empty($lastMonthMint) ? 0 : $lastMonthMint->cun;
        $prevMonthMintCun = empty($prevMonthMint) ? 0 : $prevMonthMint->cun;


        $lastMonthCreateAuction = DB::table('count_create_auction')->where('year', $year)->where('month', $month)->groupBy('year', 'month')->select(DB::raw('sum(cun) as cun,year,month,day'))->first();
        $prevMonthCreateAuction = DB::table('count_create_auction')->where('year', $prevYear)->where('month', $prevMonth)->groupBy('year', 'month')->select(DB::raw('sum(cun) as cun,year,month,day'))->first();
        $lastMonthCreateAuctionCun = empty($lastMonthCreateAuction) ? 0 : $lastMonthCreateAuction->cun;
        $prevMonthCreateAuctionCun = empty($prevMonthCreateAuction) ? 0 : $prevMonthCreateAuction->cun;


        $lastMonthPurchase = DB::table('count_purchase')->where('year', $year)->where('month', $month)->groupBy('year', 'month')->select(DB::raw('sum(cun) as cun,sum(total) as total,year,month,day'))->first();
        $prevMonthPurchase = DB::table('count_purchase')->where('year', $prevYear)->where('month', $prevMonth)->groupBy('year', 'month')->select(DB::raw('sum(cun) as cun,sum(total) as total,year,month,day'))->first();
        $lastMonthPurchaseCun = empty($lastMonthPurchase) ? 0 : $lastMonthPurchase->cun;
        $lastMonthPurchaseTotal = empty($lastMonthPurchase) ? 0 : $lastMonthPurchase->total;
        $prevMonthPurchaseCun = empty($prevMonthPurchase) ? 0 : $prevMonthPurchase->cun;
        $prevMonthPurchaseTotal = empty($prevMonthPurchase) ? 0 : $prevMonthPurchase->total;

        $lastMonthEndAuction = DB::table('count_end_auction')->where('year', $year)->where('month', $month)->groupBy('year', 'month')->select(DB::raw('sum(cun) as cun,sum(total) as total,year,month,day'))->first();
        $prevMonthEndAuction = DB::table('count_end_auction')->where('year', $prevYear)->where('month', $prevMonth)->groupBy('year', 'month')->select(DB::raw('sum(cun) as cun,sum(total) as total,year,month,day'))->first();
        $lastMonthEndAuctionCun = empty($lastMonthEndAuction) ? 0 : $lastMonthEndAuction->cun;
        $lastMonthEndAuctionTotal = empty($lastMonthEndAuction) ? 0 : $lastMonthEndAuction->total;
        $prevMonthEndAuctionCun = empty($prevMonthEndAuction) ? 0 : $prevMonthEndAuction->cun;
        $prevMonthEndAuctionTotal = empty($prevMonthEndAuction) ? 0 : $prevMonthEndAuction->total;


        $purchase30 = DB::table('count_purchase')->orderBy('id', 'desc')->groupBy('year', 'month', 'day')->select(DB::raw('sum(cun) as cun,sum(total) as total,year,month,day'))->limit(30)->get();
        $endAuction30 = DB::table('count_end_auction')->orderBy('id', 'desc')->groupBy('year', 'month', 'day')->select(DB::raw('sum(cun) as cun,sum(total) as total,year,month,day'))->limit(30)->get();


        $purchase30Days = [];
        foreach ($purchase30 as $purchase) {
            $purchaseTime = strtotime("{$purchase->year}-{$purchase->month}-{$purchase->day}");
            if ($purchaseTime < time() - (29 * 86400)) {
                continue;
            }

            $purchase30Days[$purchaseTime] = $purchase;
        }

        $endAuction30Days = [];
        foreach ($endAuction30 as $endAuction) {
            $endAuctionTime = strtotime("{$endAuction->year}-{$endAuction->month}-{$endAuction->day}");
            if ($endAuctionTime < time() - (29 * 86400)) {
                continue;
            }

            $endAuction30Days[$endAuctionTime] = $endAuction;
        }

        for ($i = 29; $i >= 0; $i--) {
            $time = time() - ($i * 86400);
            $time = strtotime(date('Y-m-d', $time));

            if (empty($purchase30Days[$time]) == true) {
                $purchase30Days[$time] = [
                    'cun' => 0,
                    'total' => 0
                ];
            }

            if (empty($endAuction30Days[$time]) == true) {
                $endAuction30Days[$time] = [
                    'cun' => 0,
                    'total' => 0
                ];
            }
        }

        return response()->json([
            'data' => [
                'mint' => [
                    'last' => [
                        'cun' => $lastMonthMintCun
                    ],
                    'prev' => [
                        'cun' => $prevMonthMintCun
                    ]
                ],
                'create_auction' => [
                    'last' => [
                        'cun' => $lastMonthCreateAuctionCun
                    ],
                    'prev' => [
                        'cun' => $prevMonthCreateAuctionCun
                    ]
                ],
                'purchase' => [
                    'last' => [
                        'cun' => $lastMonthPurchaseCun,
                        'total' => round($lastMonthPurchaseTotal, 6),
                    ],
                    'prev' => [
                        'cun' => $prevMonthPurchaseCun,
                        'total' => round($prevMonthPurchaseTotal, 6),
                    ]
                ],
                'end_auction' => [
                    'last' => [
                        'cun' => $lastMonthEndAuctionCun,
                        'total' => round($lastMonthEndAuctionTotal, 6),
                    ],
                    'prev' => [
                        'cun' => $prevMonthEndAuctionCun,
                        'total' => round($prevMonthEndAuctionTotal, 6),
                    ]
                ],
                'purchase_30d' => $purchase30Days,
                'endAuction_30d' => $endAuction30Days,
            ]
        ]);
    }

    public function volume24H(Request $request)
    {
        $tokenHour = $this->contractDatabase('tokenHour');
        if (empty($tokenHour) == true) {
            return response()->json([
                'error' => [
                    'message' => "존재하지 않는 테이블"
                ]
            ]);
        }

        $addresses = $this->netTokenAddresses();

        $data = [];
        foreach ($addresses as $address) {
            $tokens = $this->contractDatabase('tokens');
            $result = $tokens->where('tokenAddress', $address)->offset(0)->limit(1)->get();
            if (empty($result['data']) == true || count($result['data']) == 0) {
                continue;
            }

            $tokenHour = $this->contractDatabase('tokenHour');
            $result = $tokenHour->where('tokenAddress', $address)->orderBy('timestamp', 'desc')->offset(0)->limit(720)->get();

            $statistics = $result['data'];
//			print_r($statistics);
//			exit;
            $tokenSymbol = $this->checkNetToken($address);

//			echo "--------- ".$tokenSymbol . "--------\n";

            $swapAmount24Hs = [];
            if (empty($statistics) == false && count($statistics) > 0) {
                foreach ($statistics as $hourData) {
                    $hourData['y'] = $this->bchexdec($hourData['y']);
                    $hourData['m'] = $this->bchexdec($hourData['m']);
                    $hourData['d'] = $this->bchexdec($hourData['d']);

                    $date = "{$hourData['y']}-{$hourData['m']}-{$hourData['d']}";
                    $time = strtotime($date);
                    $date = date("Y-m-d", $time);

//					echo  $date . "\n";

                    if ($time < (time() - 2592000)) {//해당 데이터가 30일 이상된 데이터인 경우에는 처리하지 않음.
                        continue; // 주석이었음 왜?
                    }

                    if (empty($swapAmount24Hs[$date]) == true) {
                        $swapAmount24Hs[$date] = "0";
                    }

                    if (empty($hourData['swapAmountIn']) == true) {
                        $hourData['swapAmountIn'] = "0";
                    }
                    if (empty($hourData['swapAmountOut']) == true) {
                        $hourData['swapAmountOut'] = "0";
                    }

//					print_r($swapAmount24Hs);

                    $swapAmount24Hs[$date] = bcadd($swapAmount24Hs[$date], $this->bchexdec($hourData['swapAmountIn']));
                    $swapAmount24Hs[$date] = bcadd($swapAmount24Hs[$date], $this->bchexdec($hourData['swapAmountOut']));
                }

            }

            $data[$tokenSymbol] = $swapAmount24Hs;
        }

        $totalData = [];
        foreach ($data as $symbol => $swapAmount24Hs) {

            $dateArr = [];  // 60일치 0으로 초기화한 데이터
            for ($i = 0; $i < 60; $i++) {
                $date = date("Y-m-d", time() - (86400 * $i));

                // 원인 ㄴ: 위에서 나온 값들과 매칭이 안됨 ...
//                if(empty($swapAmount24Hs[$date]) == true){
//					$swapAmount24Hs[$date] = "0";
//				}
                if (empty($swapAmount24Hs[$date]) == true) {
                    $dateArr[$date] = "0";
                }

                foreach ($swapAmount24Hs as $key => $val) {
                    if ($key == $date) {
                        $dateArr[$date] = $val;
                    }
                }
            }

//			$totalData[$symbol] = $swapAmount24Hs;
            $totalData[$symbol] = $dateArr;
        }


        return response()->json([
//			'data' => $data
            'data' => $totalData
        ]);
    }

    public function liquidity24H(Request $request)
    {
        $tokens = $this->contractDatabase('tokens');
        if (empty($tokens) == true) {
            return response()->json([
                'error' => [
                    'message' => "존재하지 않는 테이블"
                ]
            ]);
        }

        $tokenHour = $this->contractDatabase('tokenHour');
        if (empty($tokenHour) == true) {
            return response()->json([
                'error' => [
                    'message' => "존재하지 않는 테이블"
                ]
            ]);
        }

        $todayAmounts = [];
        $addresses = $this->netTokenAddresses();
//		print_r($addresses);
//		exit;
        foreach ($addresses as $address) {
            $tokens = $this->contractDatabase('tokens');
            $result = $tokens->where('tokenAddress', $address)->offset(0)->limit(1)->get();

            if (empty($result['data']) == true || count($result['data']) == 0) {
                continue;
            }

            // (totalMintAmount + totalAmountIn) - (totalBurnAmount + totalAmountOut)
            $data = $result['data'][0];

            $amount = "0";
            if (empty($data['totalMintAmount']) == false) {
                $amount = bcadd($amount, $this->bchexdec($data['totalMintAmount']));


            }
            if (empty($data['totalAmountIn']) == false) {
                $amount = bcadd($amount, $this->bchexdec($data['totalAmountIn']));
            }
            if (empty($data['totalBurnAmount']) == false) {
                $amount = bcsub($amount, $this->bchexdec($data['totalBurnAmount']));
            }
            if (empty($data['totalAmountOut']) == false) {
                $amount = bcsub($amount, $this->bchexdec($data['totalAmountOut']));
            }

            $todayAmounts[$address] = $amount;
        }
//		print_r($todayAmounts);
//		exit;
//		0xbb4cdb9cbd36b01bd1cbaebf2de08d9173bc095c 가 - 나옴

        $addressesAmount24Hs = [];
        foreach ($todayAmounts as $address => $amount) {
            $tokenHour = $this->contractDatabase('tokenHour');
            $result = $tokenHour->where('tokenAddress', $address)->orderBy('timestamp', 'desc')->offset(0)->limit(720)->get();
            $statistics = $result['data'];
            $tokenSymbol = $this->checkNetToken($address);

            $amount24Hs = [];
            if (empty($statistics) == false && count($statistics) > 0) {
                foreach ($statistics as $hourData) {
                    $hourData['y'] = $this->bchexdec($hourData['y']);
                    $hourData['m'] = $this->bchexdec($hourData['m']);
                    $hourData['d'] = $this->bchexdec($hourData['d']);

                    $date = "{$hourData['y']}-{$hourData['m']}-{$hourData['d']}";
                    $time = strtotime($date);
                    $date = date("Y-m-d", $time);
                    if ($time < (time() - 2592000)) {//해당 데이터가 30일 이상된 데이터인 경우에는 처리하지 않음.
                        //continue;
                    }

                    if (empty($amount24Hs[$date]) == true) {
                        $amount24Hs[$date] = [];
                        $amount24Hs[$date]['swapAmountIn'] = "0";
                        $amount24Hs[$date]['swapAmountOut'] = "0";
                        $amount24Hs[$date]['totalMintAmount'] = "0";
                        $amount24Hs[$date]['totalBurnAmount'] = "0";
                    }

                    if (empty($hourData['swapAmountIn']) == true) {
                        $hourData['swapAmountIn'] = "0";
                    }
                    if (empty($hourData['swapAmountOut']) == true) {
                        $hourData['swapAmountOut'] = "0";
                    }
                    if (empty($hourData['totalMintAmount']) == true) {
                        $hourData['totalMintAmount'] = "0";
                    }
                    if (empty($hourData['totalBurnAmount']) == true) {
                        $hourData['totalBurnAmount'] = "0";
                    }

                    $amount24Hs[$date]['swapAmountIn'] = bcadd($amount24Hs[$date]['swapAmountIn'], $this->bchexdec($hourData['swapAmountIn']));
                    $amount24Hs[$date]['swapAmountOut'] = bcadd($amount24Hs[$date]['swapAmountOut'], $this->bchexdec($hourData['swapAmountOut']));
                    $amount24Hs[$date]['totalMintAmount'] = bcadd($amount24Hs[$date]['totalMintAmount'], $this->bchexdec($hourData['totalMintAmount']));
                    $amount24Hs[$date]['totalBurnAmount'] = bcadd($amount24Hs[$date]['totalBurnAmount'], $this->bchexdec($hourData['totalBurnAmount']));
                }

            }

            $addressesAmount24Hs[$address] = $amount24Hs;
        }


        $data = [];
        foreach ($todayAmounts as $address => $amount) {

            for ($i = 0; $i < 60; $i++) {
                $date = date("Y-m-d", time() - (86400 * $i));
                $tokenSymbol = $this->checkNetToken($address);

                if ($i == 0) {
                    $data[$tokenSymbol] = [];
                    $data[$tokenSymbol][$date] = $amount;
                } else {
                    $nextDate = date("Y-m-d", time() - (86400 * ($i - 1)));

                    $nextAmount = $data[$tokenSymbol][$nextDate];
                    if (empty($addressesAmount24Hs[$address][$date]) == true) {
                        $data[$tokenSymbol][$date] = $nextAmount;
                    } else {
                        //날짜 역순 계산이기 때문에 발행 , 소각 등의 계산을 반대로 진행
                        $nextAmount = bcadd($nextAmount, $addressesAmount24Hs[$address][$date]['swapAmountOut']);
                        $nextAmount = bcadd($nextAmount, $addressesAmount24Hs[$address][$date]['totalBurnAmount']);
                        $nextAmount = bcsub($nextAmount, $addressesAmount24Hs[$address][$date]['swapAmountIn']);
                        $nextAmount = bcsub($nextAmount, $addressesAmount24Hs[$address][$date]['totalMintAmount']);

                        $data[$tokenSymbol][$date] = $nextAmount;
                    }

                }
            }

        }

        return response()->json([
            'data' => $data
        ]);
    }


    public function majorPrices(Request $request)
    {
        $pairHour = $this->contractDatabase('pairHour');
        if (empty($pairHour) == true) {
            return response()->json([
                'error' => [
                    'message' => "존재하지 않는 테이블"
                ]
            ]);
        }

        $addresses = $this->netTokenAddresses();

        if (envDB('BASE_MAINNET') == 'ETH') {
            $usdtAddress = "0xdac17f958d2ee523a2206206994597c13d831ec7";
        } else if (envDB('BASE_MAINNET') == 'BSC') {
            $usdtAddress = "0x55d398326f99059ff775485246999027b3197955";
        } else if (envDB('BASE_MAINNET') == 'KLAY') {
            $usdtAddress = "0xcee8faf64bb97a73bb51e115aa89c17ffa8dd167";
        }

        $data = [];
        foreach ($addresses as $address) {
            $lpAddress = $this->factoryContract(envDB('FACTORY_CONTRACT_ADDRESS'))->getCachePair($address, $usdtAddress);
            if ($lpAddress == '0x0000000000000000000000000000000000000000') {
                continue;
            }

            $tokenSymbol = $this->checkNetToken($address);
            $reserves = $this->getReserves($lpAddress);

            $token0 = $this->getCacheTokenInfo($reserves['token0']);
            $token1 = $this->getCacheTokenInfo($reserves['token1']);

            for ($i = 0; $i < $token0['decimals']; $i++) {
                $reserves['reserve0'] = bcdiv($reserves['reserve0'], "10", 10);
            }

            for ($i = 0; $i < $token1['decimals']; $i++) {
                $reserves['reserve1'] = bcdiv($reserves['reserve1'], "10", 10);
            }

            if ($reserves['token0'] == $address) {
                $info = $token0;
                $price = bcdiv($reserves['reserve1'], $reserves['reserve0'], 10);
            } else {
                $info = $token1;
                $price = bcdiv($reserves['reserve0'], $reserves['reserve1'], 10);
            }

            $pairHour = $this->contractDatabase('pairHour');
            $result = $pairHour->where('lpAddress', $lpAddress)->orderBy('timestamp', 'desc')->offset(0)->limit(168)->get();//1시간 x 168 = 7일
            $statistics = $result['data'];

            $price24H = "0";
            $history = [];
            if (empty($statistics) == false && count($statistics) > 0) {
//			    print_r($statistics);
//			    exit;
                foreach ($statistics as $hourData) {
                    $hourData['y'] = $this->bchexdec($hourData['y']);
                    $hourData['m'] = $this->bchexdec($hourData['m']);
                    $hourData['d'] = $this->bchexdec($hourData['d']);

                    $hourData['reserve0'] = $this->bchexdec($hourData['reserve0']);
                    $hourData['reserve1'] = $this->bchexdec($hourData['reserve1']);

                    $date = "{$hourData['y']}-{$hourData['m']}-{$hourData['d']}";
                    $time = strtotime($date);
                    if ($time < (time() - 604800) || empty($hourData['reserve0']) || empty($hourData['reserve1'])) {//해당 데이터가 일주일 이상된 데이터인 경우에는 처리하지 않음.
                        continue;
                    }

                    for ($i = 0; $i < $token0['decimals']; $i++) {
                        $hourData['reserve0'] = bcdiv($hourData['reserve0'], "10", 10);
                    }

                    for ($i = 0; $i < $token1['decimals']; $i++) {
                        $hourData['reserve1'] = bcdiv($hourData['reserve1'], "10", 10);
                    }

                    if ($reserves['token0'] == $address) {
                        $hourPrice = bcdiv($hourData['reserve1'], $hourData['reserve0'], 10);
                    } else {
                        $hourPrice = bcdiv($hourData['reserve0'], $hourData['reserve1'], 10);
                    }

                    if ($time > (time() - 86400)) {
                        $price24H = $hourPrice;
                    }

                    $history[$date] = $hourPrice;
                }
            }

            if ($price24H == "0") {
                $price24H = $price;
            }

            $data[$tokenSymbol] = [
                'last' => $price,
                'price24H' => $price24H,
                'history' => $history,
                'info' => $info,
            ];
        }

        return response()->json([
            'data' => $data
        ]);
    }

    //NFT 전체 목록
    public function nfts(Request $request)
    {
        $order_key = empty($request->order_key) ? 'created_at' : $request->order_key;
        $order_sort = empty($request->order_sort) ? 'desc' : $request->order_sort;
        $offset = empty($request->offset) ? 0 : $request->offset;
        $limit = empty($request->limit) ? 10 : $request->limit;

        $nfts = Nft::orderBy($order_key, $order_sort)->offset($offset)->limit($limit)->get();
        foreach ($nfts as $key => $nft) {
            $nft->file_url = $nft->file();
            $nfts[$key] = $nft;
        }

        $total = Nft::count();
        return response()->json([
            'total' => $total,
            'data' => $nfts
        ]);
    }

    //블록 높이 정보
    public function blockInfo()
    {
        $result = $this->netClient()->getBlockChain();

        if (empty($result['payload']) || $result['state']['success'] != 1) {
            return response()->json([
                'error' => [
                    'message' => "Not Data"
                ]
            ]);
        }
        return response()->json([
            'data' => $result['payload']
        ]);
    }

//	// 유동성 페이지 정보 가져오기
    public function getFarms(Request $req)
    {
        $contractDB = ContractDB::find('farms');
//        $offset = empty($request->offset) ? 0 : $request->offset;
//        $limit = empty($request->limit) ? 10 : $request->limit;

        $tokenHour = $this->contractDatabase('farms');

        $result = $tokenHour->orderBy('allocPoint', 'desc')->get();

        if ($result['total'] == 0) {
            return response()->json([
                'error' => [
                    'message' => "Not Data"
                ]
            ]);
        }

        $data = [];
        foreach ($result['data'] as $farm) {
            $tokenAddress0 = $this->getCacheToken0($farm['lpAddress']);
            $tokenAddress1 = $this->getCacheToken1($farm['lpAddress']);
            $lpInfo = $this->getCacheTokenInfo($farm['lpAddress']);
            $tokenInfo0 = $this->getCacheTokenInfo($tokenAddress0);
            $tokenInfo1 = $this->getCacheTokenInfo($tokenAddress1);
            $reserves = $this->getCacheReserves($farm['lpAddress']);

            $farm['address'] = $farm['lpAddress'];
            $farm['lpInfo'] = $lpInfo;
            $farm['tokenInfo0'] = $tokenInfo0;
            $farm['tokenInfo1'] = $tokenInfo1;
            $farm['reserves'] = $reserves;
            $addressInfo = $this->netClient()->getAddressInfo([
                'address' => $farm['lpAddress']
            ]);
            if ($addressInfo['state']['success'] == true) {
                $farm['addressInfo'] = $addressInfo['payload'];
            }

            array_push($data, $farm);
        }

        return response()->json([
            'data' => $data,
//			'total'=> $result['payload']['total'],
            'total' => $result['total'],
        ]);

    }

    public function getLiquidity(Request $req)
    {

        $pairs = $this->contractDatabase('pairs');
        $total = 0;
//        print_r($pairs);
        if (empty($pairs) == true) {
            return response()->json([
                'error' => [
                    'message' => "존재하지 않는 테이블"
                ]
            ]);
        }

        $pairHour = $this->contractDatabase('pairHour');
        if (empty($pairHour) == true) {
            return response()->json([
                'error' => [
                    'message' => "존재하지 않는 테이블"
                ]
            ]);
        }

        if (empty($request->tokenAddress) == true) {
//            $result = $pairs->orderBy('totalSwapCount','desc')->offset(0)->limit(30)->get();
            $result = $pairs->orderBy('totalSwapCount', 'desc')->get();
            $pools = $result['data'];


            if (empty($pools) == true) {
                return response()->json([
                    'error' => [
                        'message' => "Not Data"
                    ]
                ]);
            }

            $total = $result['total'];
        } else {
            $pairs = $this->contractDatabase('pairs');
            $result = $pairs->where('tokenAddress0', $request->tokenAddress)->orderBy('totalSwapCount', 'desc')->get();
            $tokenAddressPools0 = empty($result['data']) ? [] : $result['data'];
//            print_r($result);
//            exit;
            $pairs = $this->contractDatabase('pairs');
            $result = $pairs->where('tokenAddress1', $request->tokenAddress)->orderBy('totalSwapCount', 'desc')->get();
            $tokenAddressPools1 = empty($result['data']) ? [] : $result['data'];


            foreach ($tokenAddressPools0 as $key => $pool) {
                $tokenAddressPools0[$key]['totalSwapCount'] = empty($tokenAddressPools0[$key]['totalSwapCount']) ? 0 : $this->bchexdec($tokenAddressPools0[$key]['totalSwapCount']);
            }

            foreach ($tokenAddressPools1 as $key => $pool) {
                $tokenAddressPools1[$key]['totalSwapCount'] = empty($tokenAddressPools1[$key]['totalSwapCount']) ? 0 : $this->bchexdec($tokenAddressPools1[$key]['totalSwapCount']);
            }

            $pools = array_merge($tokenAddressPools0, $tokenAddressPools1);
            for ($i = 0; $i < count($pools); $i++) {
                $tmpCount = $pools[count($pools) - 1];

                for ($y = 0; $y < count($pools); $y++) {
                    if ($tmpCount['totalSwapCount'] > $pools[$y]['totalSwapCount']) {
                        $frontArr = array_slice($pools, 0, $y);
                        $backArr = array_slice($pools, $y, count($pools) - $y - 1);

                        $pools = array_merge($frontArr, [$tmpCount], $backArr);
                        break;
                    }
                }
            }
        }
//		print_r($pools);
//		exit;
        $i = 1;
        $data = [];
        foreach ($pools as $pool) {
            $reserves = $this->getReserves($pool['lpAddress']);
            $token0 = $this->getCacheTokenInfo($reserves['token0']);
            $token1 = $this->getCacheTokenInfo($reserves['token1']);

            $pairHour = $this->contractDatabase('pairHour');
            $result = $pairHour->where('lpAddress', $pool['lpAddress'])->orderBy('timestamp', 'desc')->offset(0)->limit(168)->get();//1시간 x 168 = 7일
            $statistics = $result['data'];

            $swapAmountIn24H0 = "0";
            $swapAmountIn24H1 = "0";
            $swapAmountOut24H0 = "0";
            $swapAmountOut24H1 = "0";

            $swapAmountIn7D0 = "0";
            $swapAmountIn7D1 = "0";
            $swapAmountOut7D0 = "0";
            $swapAmountOut7D1 = "0";

            if (empty($statistics) == false && count($statistics) > 0) {
                foreach ($statistics as $hourData) {
                    $time = strtotime("{$hourData['y']}-{$hourData['m']}-{$hourData['d']} {$hourData['h']}:00:00");
                    if ($time < (time() - 604800)) {//해당 데이터가 일주일 이상된 데이터인 경우에는 처리하지 않음.
                        //continue;
                    }

                    if ($time >= (time() - 86400)) {//24시간 이내의 데이터인 경우
                        $swapAmountIn24H0 = empty($hourData['swapAmountIn0']) ? bcadd($swapAmountIn24H0, "0") : bcadd($swapAmountIn24H0, $this->bchexdec($hourData['swapAmountIn0']));
                        $swapAmountIn24H1 = empty($hourData['swapAmountIn1']) ? bcadd($swapAmountIn24H1, "0") : bcadd($swapAmountIn24H1, $this->bchexdec($hourData['swapAmountIn1']));
                        $swapAmountOut24H0 = empty($hourData['swapAmountOut0']) ? bcadd($swapAmountOut24H0, "0") : bcadd($swapAmountOut24H0, $this->bchexdec($hourData['swapAmountOut0']));
                        $swapAmountOut24H1 = empty($hourData['swapAmountOut1']) ? bcadd($swapAmountOut24H1, "0") : bcadd($swapAmountOut24H1, $this->bchexdec($hourData['swapAmountOut1']));
                    }

                    $swapAmountIn7D0 = empty($hourData['swapAmountIn0']) ? bcadd($swapAmountIn7D0, "0") : bcadd($swapAmountIn7D0, $this->bchexdec($hourData['swapAmountIn0']));
                    $swapAmountIn7D1 = empty($hourData['swapAmountIn1']) ? bcadd($swapAmountIn7D1, "0") : bcadd($swapAmountIn7D1, $this->bchexdec($hourData['swapAmountIn1']));
                    $swapAmountOut7D0 = empty($hourData['swapAmountOut0']) ? bcadd($swapAmountOut7D0, "0") : bcadd($swapAmountOut7D0, $this->bchexdec($hourData['swapAmountOut0']));
                    $swapAmountOut7D1 = empty($hourData['swapAmountOut1']) ? bcadd($swapAmountOut7D1, "0") : bcadd($swapAmountOut7D1, $this->bchexdec($hourData['swapAmountOut1']));
                }

            }

            $addressResult = $this->netClient()->getAddressInfo([
                'address' => $pool['lpAddress']
            ]);
            if ($addressResult['state']['success'] == true) {
                $addressInfo = $addressResult['payload'];
            }

            array_push($data, [
                '#' => $i,
                'lpAddress' => $pool['lpAddress'],
                'token0' => $token0,
                'token1' => $token1,
                'reserves' => $reserves,
                'swapAmount24H0' => $this->bcdechex(bcadd($swapAmountIn24H0, $swapAmountOut24H0)),
                'swapAmount24H1' => $this->bcdechex(bcadd($swapAmountIn24H1, $swapAmountOut24H1)),
                'swapAmount7D0' => $this->bcdechex(bcadd($swapAmountIn7D0, $swapAmountOut7D0)),
//                'swapAmount7D0' => bcadd($swapAmountIn7D0,$swapAmountOut7D0),
                'swapAmount7D1' => $this->bcdechex(bcadd($swapAmountIn7D1, $swapAmountOut7D1)),
                'addressInfo' => !empty($addressInfo) ? $addressInfo : null
            ]);
            $i++;
        }

//        print_r($data);
//        exit;
        return response()->json([
            'data' => $data,
            'total' => $total = $total != 0 ? $total : null
        ]);
    }

    public function pools(Request $req)
    {
        $stakings = Staking::orderBy('id', 'desc')->get();
        $result = $this->netClient()->getBlockChain();

        if ($result['state']['success'] != true) {
            return response()->json([
                'error' => [
                    'message' => "API BLOCK INFO ERROR"
                ]
            ]);
        }
        $last_block_height = $result['payload']['last_block_height'];

        $data = [];
        foreach ($stakings as $staking) {
            if ($last_block_height > $staking->end_block) {
                $staking->is_end = 1;
                $staking->save();
//                continue;
            }
            $stakedInfo = $this->getCacheTokenInfo($staking->staked_token);
            $rewardInfo = $this->getCacheTokenInfo($staking->reward_token);

            $result = $staking;
            $result['staked'] = $stakedInfo;
            $result['reward'] = $rewardInfo;
            array_push($data, $result);
        }


        return response()->json([
            'data' => $stakings,
            'total' => Staking::count()
        ]);
    }


    public function owner(Request $req)
    {
        // form스테이킹 오너 정보
        $owner = $this->netClient()->getContractRead([
            'contract_address' => envDB('STAKING_CONTRACT_ADDRESS'),
            'method' => 'owner',
            'return_type' => 'address',
            'parameter_type' => [],
            'parameter_data' => []
        ]);

        if (empty($owner['payload']) == true) {
            return response()->json([
                'error' => "not data"
            ]);
        }
        return response()->json([
            'data' => $owner['payload']['result'],
        ]);
    }

    //관리자 목록
    public function users(Request $request)
    {
        $offset = empty($request->offset) ? 0 : $request->offset;
        $limit = empty($request->limit) ? 10 : $request->limit;

        $userPrivileges = UserPrivilege::orderBy('id', 'desc')->offset($offset)->limit($limit)->get();

        $data = [];
        foreach ($userPrivileges as $userPrivilege) {
            $user = $userPrivilege->user();
            $user->authority = $userPrivilege->authority;
            array_push($data, $user);
        }

        $total = UserPrivilege::count();
        return response()->json([
            'total' => $total,
            'data' => $data
        ]);
    }

    //관리자 갱신
    public function updateUser(Request $request)
    {
        $user = User::find($request->id);
        if (empty($user) == true) {
            return response()->json([
                'error' => [
                    'message' => '존재하지 않는 유저 입니다'
                ],
            ]);
        }

        $user->email = $request->email;
        if (empty($request->password) == false) {
            $user->password = Hash::make($request->password);
        }
        $user->name = $request->name;
        $user->phon = $request->phone;
        $user->save();

        return response()->json([
            'data' => $user,
            'update' => true
        ]);
    }

    //관리자 추가
    public function createUser(Request $request)
    {
        if (empty($request->password) == true) {
            return response()->json([
                'error' => [
                    'message' => '비밀번호를 입력해 주세요'
                ],
            ]);
        }

        $user = new User();
        $user->email = $request->email;
        $user->password = Hash::make($request->password);
        $user->name = $request->name;
        $user->phon = $request->phone;
        $user->save();

        $userPrivileges = new UserPrivilege();
        $userPrivileges->user_id = $user->id;
        $userPrivileges->authority = 'admin';
        $userPrivileges->save();

        return response()->json([
            'data' => $user,
            'update' => false
        ]);
    }

    //관리자 제거
    public function deleteUser(Request $request)
    {
        $user = User::find($request->user_id);
        if (empty($user) == true) {
            return response()->json([
                'error' => [
                    'message' => '존재하지 않는 유저 입니다'
                ],
            ]);
        }
        if ($user->isSuperAdmin() == true) {
            return response()->json([
                'error' => [
                    'message' => '슈퍼 관리자 계정은 삭제할수 없습니다'
                ],
            ]);
        }
        $user->delete();
        return response()->json([
            'data' => true
        ]);
    }

    //문의 목록
    public function contacts(Request $request)
    {
        $offset = empty($request->offset) ? 0 : $request->offset;
        $limit = empty($request->limit) ? 10 : $request->limit;

        $contact = Contact::orderBy('id', 'desc')->offset($offset)->limit($limit)->get();

        $total = Contact::count();
        return response()->json([
            'total' => $total,
            'data' => $contact
        ]);
    }

    //문의 검색
    public function searchContacts(Request $request)
    {
        $where_key = empty($request->where_key) ? 'name' : $request->where_key;
        $offset = empty($request->offset) ? 0 : $request->offset;
        $limit = empty($request->limit) ? 10 : $request->limit;

        $contacts = Contact::where($request->where_key, 'like', '%' . $request->where_value . '%')->orderBy('created_at', 'desc');

        $data = $contacts->offset($offset)->limit($limit)->get();
        foreach ($data as $key => $contact) {
            $data[$key] = $contact;
        }

        $total = $contacts->count();
        return response()->json([
            'total' => $total,
            'data' => $data
        ]);
    }


    //문의 답변
    public function contactsReply(Request $request)
    {
        if (empty($request->id) == true) {
            return response()->json([
                'error' => [
                    'message' => '문의 ID 를 입력해주세요'
                ],
            ]);
        } else if (empty($request->reply) == true) {
            return response()->json([
                'error' => [
                    'message' => 'reply 를 입력해주세요'
                ],
            ]);
        }

        $contact = Contact::find($request->id);
        if (empty($contact) == true) {
            return response()->json([
                'error' => [
                    'message' => '문의를 찾을수 없습니다'
                ],
            ]);
        }

        $data_arr = array(
            'subject' => $contact->subject,
            'name' => $contact->name,
            'email' => $contact->email,
            'content' => $request->reply
        );
        $emailMessage = $request->reply;

        Mail::send('emails.contact-reply', ['emailMessage' => $emailMessage], function ($message) use ($data_arr) {
            $message->from('blocksdk@blocksdk.com');
            $message->to($data_arr['email'])->subject($data_arr['subject']);
        });


        $contact->reply = $request->reply;
        $contact->save();

        return response()->json([
            'data' => true
        ]);
    }


    /**
     * 디비에 풀(staking save)
     * @param Request $req
     */
    public function createPool(Request $req)
    {

        if (Staking::where('contract_address', $req->contractAddress)->exists() == true) {
            return response()->json([
                'error' => "이미 존재하는 컨트랙트 입니다.",
                'state' => false
            ]);
        }

        $url = null;
        if (isset($req->url) && empty($req->url) == false) {
            if (filter_Var($req->url, FILTER_VALIDATE_URL) == false) {
                return response()->json([
                    'error' => "Invalid URL",
                    'state' => false
                ]);
            }
            $url = $req->url;
        }

        $staking = new Staking;

        $staking->contract_address = $req->contractAddress;
        $staking->staked_token = $req->stakedToken;
        $staking->reward_token = $req->rewardToken;
        $staking->reward_per_block = $req->rewardPerBlock;
        $staking->start_block = $req->startBlock;
        $staking->end_block = $req->bonusEndBlock;
        $staking->poolLimitPerUser = $req->poolLimitPerUser;
//        $staking->project_url = null;
        $staking->project_url = $url;
        $staking->is_end = 0;
        $staking->save();

        return response()->json([
            'data' => $staking,
            'state' => true
        ]);
    }

    public function removePool(Request $req)
    {
        if (Staking::where('contract_address', $req->contractAddress)->exists() == false) {
            return response()->json([
                'error' => "존재하지 않는 스테이킹입니다.",
                'state' => false
            ]);
        }

        $endBlock = $this->netClient()->getContractRead([
            'contract_address' => $req->contractAddress,
            'method' => 'lastRewardBlock',
            'return_type' => 'uint256',
            'parameter_type' => [],
            'parameter_data' => []
        ]);

        $staking = Staking::where('contract_address', $req->contractAddress)->first();
        $staking->end_block = $endBlock['payload']['result'];
        $staking->is_end = 1;
        $staking->save();

        return response()->json([
            'data' => $staking,
            'state' => true
        ]);
    }

    public function getImages(Request $req)
    {
        $offset = empty($req->offset) ? 0 : $req->offset;
        $limit = empty($req->limit) ? 10 : $req->limit;


        if (isset($req->where_key) || isset($req->where_value)) {
            $coinInfo = CoinInfo::where($req->where_key, $req->where_value)->offset($offset)->limit($limit)->get();
        } else {
            $coinInfo = CoinInfo::offset($offset)->limit($limit)->get();
        }


        foreach ($coinInfo as $key => $val) {
            $coinInfo[$key]->image = json_decode($val->image, true);
        }

        return response()->json([
            'data' => $coinInfo,
            'total' => (isset($req->where_key) || isset($req->where_value)) ? CoinInfo::where($req->where_key, $req->where_value)->count() : CoinInfo::count(),
            'state' => true
        ]);
    }

    public function mainNet()
    {
        if (envDB('BASE_MAINNET') == 'ETH')
            return "ethereum";
        if (envDB('BASE_MAINNET') == 'BSC')
            return "binance";
        if (envDB('BASE_MAINNET') == 'KLAY')
            return "polygon";
    }

    public function imageAdd(Request $req)
    {
        // 이미지 추가  coinInfo
        if (empty($req->symbol) == true) {
            return response()->json([
                'error' => "심볼 이름이 없습니다.",
                'state' => false
            ]);
        }

        if (!isset($req->thumb) || !isset($req->small) || !isset($req->large)) {
            return response()->json([
                'error' => "파일이 없습니다?",
                'state' => false
            ]);
        }

        // 기본 저장 값
        $coinInfo = new CoinInfo;
        $coinInfo->id = md5(time() . $req->symbol);
        $coinInfo->symbol = $req->symbol;
        $coinInfo->contract_address = empty($req->contract_address) ? null : Str::lower($req->contract_address);
        $coinInfo->ethereum = empty($req->ethereum) ? null : Str::lower($req->ethereum);
        $coinInfo->binance = empty($req->binance) ? null : Str::lower($req->binance);
        $coinInfo->polygon = empty($req->polygon) ? null : Str::lower($req->polygon);


        // 이미지 저장 하고 해당 이미지를
        $temp['thumb'] = $this->fileSaved($req->thumb, "thumb");
        $temp['small'] = $this->fileSaved($req->small, "small");;
        $temp['large'] = $this->fileSaved($req->large, "large");;
        $coinInfo->image = json_encode($temp, JSON_UNESCAPED_UNICODE);
        $coinInfo->save();

        return response()->json([
            'data' => $coinInfo,
            'state' => true
        ]);
    }

    public function fileSaved($req, $name)
    {
        $extension = strtolower($req->extension());
        $filename = Str::random(30) . "." . $extension;

        $resPath = null;

        $path = $req->path();
        if (Storage::disk('s3')->put('/dex-tokenImages/' . $filename, file_get_contents($path), 'public') == false) {
            return response()->json([
                'error' => [
                    'message' => "파일 저장에 실패하였 습니다"
                ]
            ]);
        }
        $resPath = envDB('BASE_AWS_S3_URI') .'/dex-tokenImages/' . $filename;

        return $resPath;
    }

    public function getVisitWeek()
    {
//        $visit = DayVisit::all();

//        return $visit;
        $totalCnt = 0;
        $dateArr = [];  // 60일치 0으로 초기화한 데이터
        for ($i = 0; $i < 7; $i++) {
            $date = date("Y-m-d", time() - (86400 * $i));
            $visit = DayVisit::where('date', $date)->first();

            if (empty($visit) == true) {
                $dateArr[$date] = 0;
                continue;
            }

            $dateArr[$date] = $visit->cnt;
            $totalCnt = $totalCnt + $visit->cnt;
        }

        $data['today'] = $dateArr[date("Y-m-d")];
        $data['total'] = $totalCnt;
        return $data;
    }

    public function getStatistics()
    {

        //BNB
        //BNB => WETH 0x2170ed0880ac9a755fd29b2688956bd959f933f8
        //BNB => USDT 0x55d398326f99059ff775485246999027b3197955
        //BNB => WBNB 0xbb4CdB9CBd36B01bD1cBaEBF2De08d9173bc095c
        //BNB => USDC 0x8ac76a51cc950d9822d68b83fe1ad97b32cd580d

        //ETH
        //ETH => WETH 0xc02aaa39b223fe8d0a0e5c4f27ead9083c756cc2
        //ETH => USDT 0xdac17f958d2ee523a2206206994597c13d831ec7
        //ETH => WBNB 0xB8c77482e45F1F44dE1745F52C74426C631bDD52
        //ETH => USDC 0xa0b86991c6218b36c1d19d4a2e9eb0ce3606eb48

        //KLAY
        //KLAY => KETH 0x34d21b1e550d73cee41151c77f3c73359527a396
        //KLAY => KUSDT 0xcee8faf64bb97a73bb51e115aa89c17ffa8dd167
        //KLAY => KBNB 0x574e9c26bda8b95d7329505b4657103710eb32ea
        //KLAY => KUSDC 0x754288077d0ff82af7a5317c7cb8c444d421d103
        $tokens = [];
        if (envDB('BASE_MAINNET') == 'ETH') {
            $tokens['WETH'] = "0xc02aaa39b223fe8d0a0e5c4f27ead9083c756cc2";
            $tokens['USDT'] = "0xdac17f958d2ee523a2206206994597c13d831ec7";
            $tokens['WBNB'] = "0xB8c77482e45F1F44dE1745F52C74426C631bDD52";
            $tokens['USDC'] = "0xa0b86991c6218b36c1d19d4a2e9eb0ce3606eb48";
        } else if (envDB('BASE_MAINNET') == 'BSC') {
            $tokens['WETH'] = "0x2170ed0880ac9a755fd29b2688956bd959f933f8";
            $tokens['USDT'] = "0x55d398326f99059ff775485246999027b3197955";
            $tokens['WBNB'] = "0xbb4CdB9CBd36B01bD1cBaEBF2De08d9173bc095c";
            $tokens['USDC'] = "0x8ac76a51cc950d9822d68b83fe1ad97b32cd580d";
        } else if (envDB('BASE_MAINNET') == 'KLAY') {
            $tokens['WETH'] = "0x34d21b1e550d73cee41151c77f3c73359527a396";
            $tokens['USDT'] = "0xcee8faf64bb97a73bb51e115aa89c17ffa8dd167";
            $tokens['WBNB'] = "0x574e9c26bda8b95d7329505b4657103710eb32ea";
            $tokens['USDC'] = "0x754288077d0ff82af7a5317c7cb8c444d421d103";
        }

//        print_r($tokens);

        // 메인 넷 비교구분해서 메이저 코인 BNB, BTC, ETH, USDT 4개의 토큰 주소를 가져와야됨 ->  컨트렉트디비 가지고 정보 뽑음
//        $tokenHour = $this->contractDatabase('tokenHour');
        $totalSwapCnt = 0;
        $todaySwapCnt = 0;
        $totalLiquidityCnt = 0;
        $todayLiquidityCnt = 0;
        $totalBurnCnt = 0;
        $todayBurnCnt = 0;

        $data = [];
        foreach ($tokens as $key => $val) {
            $tokenHour = $this->contractDatabase('tokenHour');
//            echo Str::lower($val)."\n";
            $result = $tokenHour->where('tokenAddress', Str::lower($val))->orderBy('timestamp', 'desc')->limit(24 * 7)->get();
//            print_r($result);
            if (empty($result['data']) == true) {
                $data[$key] = null;
                continue;
            }

            $temp = [];
            foreach ($result['data'] as $k => $v) {
//                $temp[$k]['data'] = $v;
                $hourData['y'] = $this->bchexdec($v['y']);
                $hourData['m'] = $this->bchexdec($v['m']);
                $hourData['d'] = $this->bchexdec($v['d']);

                $date = "{$hourData['y']}-{$hourData['m']}-{$hourData['d']}";
                $time = strtotime($date);
                $date = date("Y-m-d", $time);
                $week = date("Y-m-d", time() - (86400 * 6));

                if ($week <= $date) {
//                    $ttt['date'] = "date";
//                    $ttt['value'] = $v;
//                    print_r($ttt);

                    // 주간 값
                    $temp[$k] = $date;
                    if (isset($v['swapCount']))
                        $totalSwapCnt = $totalSwapCnt + $this->bchexdec($v['swapCount']); // 스왑

//                    if(isset($v['totalMintCount']) && !isset($v['totalBurnCount']) && !isset($v['swapCount']) ){
                    if (isset($v['totalMintCount'])) {
                        $totalLiquidityCnt = $totalLiquidityCnt + $this->bchexdec($v['totalMintCount']); // 유동성 추가
                    }

//                    if(isset($v['totalMintCount']) && isset($v['totalBurnCount'])  && !isset($v['swapCount']) ){
                    if (isset($v['totalBurnCount'])) {
                        $totalBurnCnt = $totalBurnCnt + $this->bchexdec($v['totalBurnCount']); // 유동성 삭제
                    }


                }
                if ($date == date("Y-m-d")) {
                    // 오늘 값
                    if (isset($v['swapCount']))
                        $todaySwapCnt = $todaySwapCnt + +$this->bchexdec($v['swapCount']); // 스왑; // 스왑

//                    if(isset($v['totalMintCount']) && !isset($v['totalBurnCount'])  && !isset($v['swapCount'])){
                    if (isset($v['totalMintCount'])) {
                        $todayLiquidityCnt = $todayLiquidityCnt + $this->bchexdec($v['totalMintCount']); // 유동성 추가
                    }
//                    if(isset($v['totalMintCount']) && isset($v['totalBurnCount'])  && !isset($v['swapCount'])){
                    if (isset($v['totalBurnCount'])) {
                        $todayBurnCnt = $todayBurnCnt + +$this->bchexdec($v['totalBurnCount']); // 유동성 삭제
                    }
                }

            }
            $data[$key] = $temp;
        }
        $res['Swap']['totalSwapCnt'] = $totalSwapCnt;
        $res['Swap']['todaySwapCnt'] = $todaySwapCnt;
        $res['Liquidity']['totalLiquidityCnt'] = $totalLiquidityCnt;
        $res['Liquidity']['todayLiquidityCnt'] = $todayLiquidityCnt;
        $res['Burn']['totalBurnCnt'] = $totalBurnCnt;
        $res['Burn']['todayBurnCnt'] = $todayBurnCnt;

//        print_r($data);
//        exit;
        return [
            "data" => $res,
            "total" => 0
        ];
    }
}