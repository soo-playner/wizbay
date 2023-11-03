<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WalletController;

use App\Http\Controllers\TopController;

/*
	JSON API V1
*/
Route::prefix('v1')->group(function() {
	/*
		토큰 APIs
	*/
	Route::prefix('token')->group(function () {

		//토큰 정보 가져오기
		Route::get('{address}/info','App\Http\Controllers\TokenController@tokenInfo');

		//토큰 잔액 가져오기
		Route::get('{address}/balance','App\Http\Controllers\TokenController@tokenBalance');

		//토큰 승인 잔액 가져오기
		Route::get('{address}/allowance','App\Http\Controllers\TokenController@tokenAllowance');

	});

	//  http://domain/api/v1/factory/pair httpmethod = get
	/*
		팩토리 APIs
	*/
	Route::prefix('factory')->group(function () {

		//교환쌍 주소 가져오기
		Route::get('pair','App\Http\Controllers\FactoryController@pairAddress');

	});

	/*
		거래쌍 APIs
	*/
	Route::prefix('pair')->group(function () {

		//교환쌍 상세 정보 가져오기
		Route::get('{pairAddress}/info','App\Http\Controllers\PairController@info');

		//교환쌍 유동성 가져오기
		Route::get('{pairAddress}/reserves','App\Http\Controllers\PairController@reserves');

		//교환쌍 예측 거래량 (Input 가져오기)
		Route::get('{pairAddress}/amountin','App\Http\Controllers\PairController@amountIn');

		//교환쌍 예측 거래량 (Output 가져오기)
		Route::get('{pairAddress}/amountout','App\Http\Controllers\PairController@amountOut');


	});

	/*
		스테이킹 APIs
	*/
	Route::prefix('staking')->group(function () {

		//스테이킹
		Route::get('{contractAddress}/earned','App\Http\Controllers\StakingController@earned');

		//스테이킹 사용자 정보 가져오기
		Route::get('{contractAddress}/userInfo','App\Http\Controllers\StakingController@userInfo');

		//스테이킹 예상 수익율
		Route::get('{contractAddress}/apr','App\Http\Controllers\StakingController@apr');

		//진행중인 스테이킹
		Route::get('live','App\Http\Controllers\StakingController@live');

		//종료한 스테이킹
        Route::get('finished','App\Http\Controllers\StakingController@finished');

        //사용자가 스테이킹한 목록
        Route::get('{fromAddress}/only','App\Http\Controllers\StakingController@only');
	});

	/*
		팜 APIs
	*/	
	Route::prefix('farm')->group(function () {

		//농장 풀 예상 수익률
		Route::get('{lpAddress}/apr','App\Http\Controllers\FarmController@farmApr');


		//농장 풀 정보 가져오기
		//Route::get('{pullID}/info','App\Http\Controllers\FarmController@farmInfo');

		//농장 풀 보상 가져오기
		Route::get('{pullID}/earned','App\Http\Controllers\FarmController@farmEarned');


		//농장 풀 사용자 정보 가져오기
		Route::get('{pullID}/userInfo','App\Http\Controllers\FarmController@farmUserInfo');

        //사용자가 스테이킹한 목록
        Route::get('{fromAddress}/only','App\Http\Controllers\FarmController@only');


	});

	/*
		정보 APIs
	*/
	Route::prefix('info')->group(function () {

		//최신 트랜션 목록 가져오기
		Route::get('txs','App\Http\Controllers\InfoController@allTxs');

		//메이저 토큰 가격 목록
		Route::get('major-prices','App\Http\Controllers\InfoController@majorPrices');

		//일별 유동성
		Route::get('liquidity-24h','App\Http\Controllers\InfoController@liquidity24H');

		//일별 거래량
		Route::get('volume-24h','App\Http\Controllers\InfoController@volume24H');

		//쌍 일별 유동성
		Route::get('pool/{lpAddress}/liquidity-24h','App\Http\Controllers\InfoController@poolLiquidity24H');

		//쌍 일별 거래량
		Route::get('pool/{lpAddress}/volume-24h','App\Http\Controllers\InfoController@poolVolume24H');

		//쌍 최신 트랜잭션 목록 가져오기
		Route::get('pool/{lpAddress}/txs','App\Http\Controllers\InfoController@poolTxs');


		//토큰 일별 유동성
		Route::get('token/{tokenAddress}/liquidity-24h','App\Http\Controllers\InfoController@tokenLiquidity24H');

		//토큰 일별 거래량
		Route::get('token/{lpAddress}/volume-24h','App\Http\Controllers\InfoController@tokenVolume24H');

		//토큰 일별 가격
		Route::get('token/{lpAddress}/prices','App\Http\Controllers\InfoController@tokenPrices');

        // 메인 페이지 화폐값 getTrades
        Route::get('trades','App\Http\Controllers\InfoController@tradePrices');

        // 메인 페이지 메인넷 거래량
        Route::get('netinfo','App\Http\Controllers\ExchangeController@netinfo');
	});

	//로터리 APIs
	Route::prefix('lottery')->group(function () {
		Route::get('info','App\Http\Controllers\LotteryController@lotteryInfo');
		
		Route::get('ticket-history','App\Http\Controllers\LotteryController@ticketHistory');
		
		Route::get('ticket-info','App\Http\Controllers\LotteryController@ticketInfo');
	});
	

	/*
	  TOP 30 토큰들
	*/
	Route::get('/top-30-tokens',[TopController::class, 'topTokens']);

	/*
	  TOP 30 유동성 목록
	*/
	Route::get('/top-30-pools','App\Http\Controllers\TopController@topPools');

    Route::get('/top-5-pools','App\Http\Controllers\TopController@top5Pools');
	/*
	  TOP 100 농장 목록
	*/
	Route::get('/top-100-farms','App\Http\Controllers\TopController@topFarms');

	/*
	  스마트 계약 함수 hex로 인코딩 API
	*/
	Route::post('/solc/encodefunction','App\Http\Controllers\SolcController@encodefunction');

	/*

	*/
	Route::get('/solc/paused','App\Http\Controllers\SolcController@paused');// ㅅ

	/*
	  문의접수 API
	*/
	Route::post('/contact','App\Http\Controllers\ContactController@created');


    // 이미지 토큰주소별 조회
//    Route::get("/token/image/{token}", 'App\Http\Controllers\TestMyController@tokenImage');

    Route::get('/wallet/{address}/balance',[WalletController::class, 'getBalance']);
    
	//월렛 커넥트 트랜잭션 조회
	Route::get('/confirm/{tx_hash}',[WalletController::class, 'hash_confirm']);
});


/*
	거래소 APIs
*/
Route::prefix('exchange')->group(function () {

	//거래소 시세
	Route::get('prices','App\Http\Controllers\ExchangeController@getPrices');
//	Route::get('prices1','App\Http\Controllers\ExchangeController@getPrices1');
//	Route::get('newprices','App\Http\Controllers\TestMyController@getPrices');
//    Route::get('netinfo','App\Http\Controllers\ExchangeController@netinfo');
});


/*
  컨트렉트 DB SELECT
*/
Route::post('/contractDB/{tableName}/select','App\Http\Controllers\ContractDBController@selected');


/*
  스마트 계약 트랜잭션 이벤트 콜백
*/
Route::post('/callback','App\Http\Controllers\CallbackController@check'); // ㅅ
