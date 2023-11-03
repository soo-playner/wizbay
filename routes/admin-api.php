<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;



/*
	JSON API V1
*/

//Route::get('dev-mail','App\Http\Controllers\DashController@testdevmail');

//// 주간방문자 통계
Route::get('test-statistics','App\Http\Controllers\DashController@getStatistics');

//관리자 로그인
Route::get('auth-check','App\Http\Controllers\AdminController@authCheck');

Route::post('login','App\Http\Controllers\AdminController@login');
Route::get('logout','App\Http\Controllers\AdminController@logout');

Route::middleware(['auth.admin'])->group(function () {
	Route::get('me','App\Http\Controllers\AdminController@me');
	
	//대시보드 통계 데이터
	Route::get('dash','App\Http\Controllers\DashController@get');
	
	//NFT 전체 목록
//	Route::get('nfts','App\Http\Controllers\DashController@nfts');//ㅅ

	// 유동성 전체 목록 불러오기
//    Route::get('liquidity','App\Http\Controllers\TopController@topFarms');
    Route::get('liquidity','App\Http\Controllers\DashController@getLiquidity');

    // 팜 목록 불러오기
    Route::get('farms','App\Http\Controllers\DashController@getFarms');

    // pool 목록 불러오기
    Route::get('pools','App\Http\Controllers\DashController@pools');

    // poolSTAKING 추가하기
    Route::post('/pools/add','App\Http\Controllers\DashController@createPool');

    // poolSTAKING 삭제하기
    Route::post('/pools/remove','App\Http\Controllers\DashController@removePool');

    // 이미지 가져오기
    Route::get('/symbol/images','App\Http\Controllers\DashController@getImages');

    // 이미지 추가
    Route::post('/symbol/add-image','App\Http\Controllers\DashController@imageAdd');

    // owner 정보 (스테이킹 팜)
    Route::get('owner','App\Http\Controllers\DashController@owner');

    // volume24H
    Route::get('volume-24h','App\Http\Controllers\DashController@volume24H');

    // liquidity-24h
    Route::get('liquidity-24h','App\Http\Controllers\DashController@liquidity24H');

    // major-prices
    Route::get('major-prices','App\Http\Controllers\DashController@majorPrices');

    // 블록 높이
    Route::get('blockinfo','App\Http\Controllers\DashController@blockInfo');

    // 토큰 정보 불러오기
    Route::get('tokeninfo', 'App\Http\Controllers\DashController@getTokenInfo');

    //거래소 시세
    Route::get('prices','App\Http\Controllers\ExchangeController@getPrices');


    // 주간 방문자 통계
    Route::get('visit/week','App\Http\Controllers\DashController@getVisitWeek');

    // 주간 스왑, 유동성 추가 삭제 통계
    Route::get('statistics','App\Http\Controllers\DashController@getStatistics');
///////////////
	//NFT 검색
//	Route::get('nfts/search','App\Http\Controllers\DashController@searchNfts');
	
	//NFT 삭제
//	Route::post('nfts/delete','App\Http\Controllers\DashController@deleteNft');
	
	//경매 진행중인 NFT 전체 목록
//	Route::get('nfts/auction','App\Http\Controllers\DashController@auctionNfts');
	
	//경매 성사
//	Route::get('nfts/end-auction','App\Http\Controllers\DashController@endAuction');
	
	//경매 성사 검색
//	Route::get('nfts/end-auction/search','App\Http\Controllers\DashController@searchEndAuction');
	
	//거래 성사
//	Route::get('nfts/purchase','App\Http\Controllers\DashController@purchase');
	
	//거래 성사 검색
//	Route::get('nfts/purchase/search','App\Http\Controllers\DashController@searchPurchase');
	
	//인증된 저자 목록
//	Route::get('authors/auth','App\Http\Controllers\DashController@authAuthors');
	
	//인증된 저자 검색
//	Route::get('authors/auth/search','App\Http\Controllers\DashController@searchAuthAuthors');
	
	//인증된 신청 저자 목록
//	Route::get('authors/auth/apply','App\Http\Controllers\DashController@applyAuthAuthors');
	
	//인증된 수동 추가
//	Route::post('authors/auth/add','App\Http\Controllers\DashController@addAuthAuthors');
	
	//인증된 신청 승인,거부
//	Route::post('authors/auth/apply','App\Http\Controllers\DashController@processAuthAuthors');
	
	//인증 처리 로그
//	Route::get('authors/auth/logs','App\Http\Controllers\DashController@authAuthorLogs');
	
	//인증 처리 로그 검색
//	Route::get('authors/auth/logs/search','App\Http\Controllers\DashController@searchAuthAuthorLogs');
	//////////////////
    ///
	//관리자 목록
	Route::get('users','App\Http\Controllers\DashController@users');
	
	//관리자 갱신
	Route::post('users/update','App\Http\Controllers\DashController@updateUser')->middleware(['auth.super']);
	
	//관리자 추가 
	Route::post('users/create','App\Http\Controllers\DashController@createUser')->middleware(['auth.super']);
	
	//관리자 제거
	Route::post('users/delete','App\Http\Controllers\DashController@deleteUser')->middleware(['auth.super']);
	
	
	//문의 목록
	Route::get('contacts','App\Http\Controllers\DashController@contacts');
	
	//문의 검색
	Route::get('contacts/search','App\Http\Controllers\DashController@searchContacts');
	
	//문의 답변
	Route::post('contacts/reply','App\Http\Controllers\DashController@contactsReply');
	
	
	//백엔드 설정 가져오기
	Route::get('setting/backend','App\Http\Controllers\SettingController@getBackend')->middleware(['auth.super']);

    //백엔드 설정 가져오기
    Route::get('setting/dex','App\Http\Controllers\SettingController@getDex')->middleware(['auth.super']);

	//프론트 설정 가져오기
	Route::get('setting/front','App\Http\Controllers\SettingController@getFront')->middleware(['auth.super']);

	//DEX MAIN 설정 가져오기
	Route::get('setting/dex-main','App\Http\Controllers\SettingController@getDexMain')->middleware(['auth.super']);
	
	//백엔드 설정 업데이트
	Route::post('setting/backend/update','App\Http\Controllers\SettingController@setBackend')->middleware(['auth.super']);

    //덱스 설정 업데이트
//    Route::post('setting/dex/update','App\Http\Controllers\SettingController@setDex')->middleware(['auth.super']);

	//프론트 설정 업데이트
	Route::post('setting/front/update','App\Http\Controllers\SettingController@setFront')->middleware(['auth.super']);
	
	//dex 설정 업데이트
	Route::post('setting/dex/update','App\Http\Controllers\SettingController@setDexMain')->middleware(['auth.super']);


	///////////
	//카테고리 삭제
//	Route::post('setting/category/delete','App\Http\Controllers\SettingController@deleteCategory')->middleware(['auth.super']);
	
	//카테고리 추가
//	Route::post('setting/category/add','App\Http\Controllers\SettingController@addCategory')->middleware(['auth.super']);
	////////////
});
?>
