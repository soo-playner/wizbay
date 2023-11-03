<?php

namespace App\Helper\Web3;


use Illuminate\Http\Request;

use App\Models\Contact;

use Web3\Eth;
use Web3\Contract;
use Web3\Utils;

class Web3
{
	public static function eth(){
		$eth = new Eth('https://main-bsc-rpc.blocksdk.com/NXDBqw4cP93uI9UXscIkO4CcHXG5YlL5wTUusdMb');
		return $eth;
	}
	
	
	public static function blockNumber(){
		$result = null;
		
		self::eth()->blockNumber(function ($err, $blockNumber) use(&$result) {
			if ($err !== null) {
				// 에러 처리
				return;
			}

			$result = $blockNumber->toString();
		});
	
		return $result;
	}
	
}