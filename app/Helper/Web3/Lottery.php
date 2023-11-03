<?php

namespace App\Helper\Web3;


use Illuminate\Http\Request;

use App\Models\Contact;

use Web3\Eth;
use Web3\Contract;
use Web3\Utils;

class Lottery extends Web3
{
	const STATUS_PENDING = 0;
	const STATUS_OPEN = 1;
	const STATUS_CLOSE = 2;
	const STATUS_CLAIMABLE = 3;
	
	public static function lottery(){
		$abis = '[
	{
		"constant": true,
		"payable": false,
		"stateMutability": "view",
		"inputs":[{"internalType":"uint256","name":"_lotteryId","type":"uint256"}],
		"name":"viewLottery",
		"outputs":[
			{
			  "name": "status",
			  "type": "uint256"
			},
			{
			  "name": "startTime",
			  "type": "uint256"
			},
			{
			  "name": "endTime",
			  "type": "uint256"
			},
			{
			  "name": "priceTicketInRewardToken",
			  "type": "uint256"
			},
			{
			  "name": "discountDivisor",
			  "type": "uint256"
			},
			{
			  "name": "rewardsBreakdown",
			  "type": "uint256[6]"
			},
			{
			  "name": "treasuryFee",
			  "type": "uint256"
			},
			{
			  "name": "rewardTokenPerBracket",
			  "type": "uint256[6]"
			},
			{
			  "name": "countWinnersPerBracket",
			  "type": "uint256[6]"
			},
			{
			  "name": "firstTicketId",
			  "type": "uint256"
			},
			{
			  "name": "firstTicketIdNextLottery",
			  "type": "uint256"
			},
			{
			  "name": "amountCollectedInRewardToken",
			  "type": "uint256"
			},
			{
			  "name": "finalNumber",
			  "type": "uint32"
			}
		],
		"type": "function"
	},
	{
		"constant": true,
		"stateMutability": "view",
		"inputs":[],
		"name":"MIN_DISCOUNT_DIVISOR",
		"outputs":[
			{
			  "name": "",
			  "type": "uint256"
			}
		],
		"type": "function"
	}
]';
		
		$contract = new Contract('https://public-en-cypress.klaytn.net', $abis);
		$contract->at("0x3dfb8f509adb114852a053edc9773576b1dc8df2");
		return $contract;
	}
	
	public static function arrToString($arr){
	
		foreach($arr as $key => $data){
			$arr[$key] = $arr[$key]->toString();
		}
		
		return $arr;
	}
	
	public static function viewLottery($lotteryId){
		$contract = self::lottery();
			
		$result = null;
		$contract->call('viewLottery', $lotteryId, function ($err, $lottery) use(&$result) {
			if ($err !== null) {
				// 에러 처리
				return;
			}
			
			$result = $lottery;
		});
		
		if($result == null){
			return false;
		}
		
		return [
			'status' => $result['status']->toString(),
			'startTime' => $result['startTime']->toString(),
			'endTime' => $result['endTime']->toString(),
			'priceTicketInRewardToken' => $result['priceTicketInRewardToken']->toString(),
			'discountDivisor' => $result['discountDivisor']->toString(),
			'rewardsBreakdown' => self::arrToString($result['rewardsBreakdown']),
			'treasuryFee' => $result['treasuryFee']->toString(),
			'rewardTokenPerBracket' => self::arrToString($result['rewardTokenPerBracket']),
			'countWinnersPerBracket' => self::arrToString($result['countWinnersPerBracket']),
			'firstTicketId' => $result['firstTicketId']->toString(),
			'firstTicketIdNextLottery' => $result['firstTicketIdNextLottery']->toString(),
			'amountCollectedInRewardToken' => $result['amountCollectedInRewardToken']->toString(),
			'finalNumber' => $result['finalNumber']->toString(),
		];
	}
	
	public static function MIN_DISCOUNT_DIVISOR(){
		$contract = self::lottery();
			
		$result = null;
		$contract->call('MIN_DISCOUNT_DIVISOR', function ($err, $lottery) use(&$result) {
			if ($err !== null) {
				// 에러 처리
				return;
			}
			
			$result = $lottery;
		});
		
		if($result == null){
			return false;
		}
		
		return $result;
	}
}