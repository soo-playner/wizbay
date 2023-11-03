<?php

namespace App\Http\Middleware;


use Illuminate\Support\Facades\Auth;

use Closure;

use Ethereum\EcRecover;
use Ethereum\KlayRecover;

use App\Models\Profile;

class AuthCheck
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    public function handle($request, Closure $next)
    {
		if(empty($request->auth_signature) == true){
			return response()->json([
				"error" => [
					"message" => "서명 메세지가 누락되었습니다"
				]
			],200);
		}else if(empty($request->auth_timestamp) == true){
			return response()->json([
				"error" => [
					"message" => "서명 메세지 인증 시간이 누락되었습니다"
				]
			],200);
		}else if(empty($request->auth_address) == true){
			return response()->json([
				"error" => [
					"message" => "인증에 사용된 주소가 누락되었습니다"
				]
			],200);
		}
		
		$message = "timestamp:" . $request->auth_timestamp;
		if(envDB('BASE_MAINNET') == 'ETH'){
			$recoveredAddress = EcRecover::personalEcRecover($message, $request->auth_signature);
		}else if(envDB('BASE_MAINNET') == 'KLAY'){
			$recoveredAddress = KlayRecover::personalEcRecover($message, $request->auth_signature);
		}
		/*
		if($recoveredAddress != $request->auth_address){
			return response()->json([
				"error" => [
					"message" => "인증 실패"
				]
			],401);
		}else if(($request->auth_timestamp + 300) < time()){
			return response()->json([
				"error" => [
					"message" => "인증 시간을 초과하였습니다"
				]
			],401);
		}
		*/
		$profile = Profile::where('address',$request->auth_address)->first();
		if(empty($profile) == true){
			$profile = new Profile();
			$profile->address = $request->auth_address;
			$profile->save();
		}
		
        return $next($request);
    }
}
