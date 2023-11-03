<?php
namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;
//use App\Http\Controllers\BlocksdkControBlocksdkControllerller as BlocksdkController;

use BlockSDK;

class WalletController extends Controller
{
//    use BlocksdkController;
    public function __construct()
    {

    }



    public function getBalance(Request $req, $address){
//        dd(envDB('BLOCKSDK_TOKEN'));
//        exit;
        if (empty($req->net) == true){
            return response()->json([
                'error' => 'net data is required'
            ]);
        }
        if (empty($address) == true){
            return response()->json([
                'error' => 'address data is required'
            ]);
        }


        $data = $this->netClient($req->net)->getAddressBalance(['address'  => Str::lower($address) ]);

        if (empty($data['payload']) == true){
            return response()->json([
                'error' => 'getBalance API ERROR'
            ]);
        }

        return response()->json([
            'data'=> $data['payload']
        ]);
    }
	
	public function hash_confirm(Request $req, $tx_hash){
//        dd(envDB('BLOCKSDK_TOKEN'));
//        exit;
        if (empty($req->net) == true){
            return response()->json([
                'error' => 'net data is required'
            ]);
        }
        if (empty($tx_hash) == true){
            return response()->json([
                'error' => 'hash data is required'
            ]);
        }


        $data = $this->netClient($req->net)->getTransaction(['hash'  => Str::lower($tx_hash) ]);
		//print_r($data['payload']['block_height']);
		//exit;


        if (empty($data['payload']) == true){
            return response()->json([
                'error' => 'hash_confirm API ERROR'
            ]);
        }

        return response()->json([
            'data'=> $data['payload']['block_height']
        ]);
    }

}

