<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;

use App\Models\Contact;



use App\Helper\Web3\Lottery;

class LotteryController extends Controller
{
	//특정 로또 정보 가져오기
	public function lotteryInfo(Request $request){
		if(empty($request->lotteryId) == true){
			return response()->json([
				'error' => [
					'message' => "Not lotteryId"
				]
			]);
		}
		
		/*
		
		
		Status status;//상태(열린로또,닫힌로또,처리됨)
        uint256 startTime;//시작시간
        uint256 endTime;//종료시간
        uint256 priceTicketInRewardToken;//로또 가격
        uint256 discountDivisor;//
        uint256[6] rewardsBreakdown; // 0: 1 matching number // 5: 6 matching numbers
        uint256 treasuryFee; // 500: 5% // 200: 2% // 50: 0.5%
        uint256[6] rewardTokenPerBracket;
        uint256[6] countWinnersPerBracket;
        uint256 firstTicketId;
        uint256 firstTicketIdNextLottery;
        uint256 amountCollectedInRewardToken;//티켓구매 총 비용
        uint32 finalNumber;
		*/
	}
	
	//특정 사용자 티켓 내역 가져오기  컨트렉트 디비 사용해야함.
	public function ticketHistory(Request $request){
		if(empty($request->fromAddress) == true){
			return response()->json([
				'error' => [
					'message' => "Not fromAddress"
				]
			]);
		}
		
		/*
		
		로또 id
		티켓 구매 개수
		*/
		
		$result = Lottery::MIN_DISCOUNT_DIVISOR();
		return response()->json([
			'data' => $result
		]);
	}
	
	//특정 사용자 특정 로또 티켓 정보 가져오기
	public function ticketInfo(Request $request){
		if(empty($request->lotteryId) == true){
			return response()->json([
				'error' => [
					'message' => "Not lotteryId"
				]
			]);
		}else if(empty($request->fromAddress) == true){
			return response()->json([
				'error' => [
					'message' => "Not fromAddress"
				]
			]);
		}
		
		
		/*
		티켓 구매 개수
		티켓 아이디[]
		티켓 상태
		*/
	}
	
}