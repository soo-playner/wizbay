<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoinInfo extends Model
{
    use HasFactory;

    protected $table = 'coinInfo';
    protected $primaryKey = 'id';

    protected $casts = [
        'id' => 'string',
    ];

    protected $fillable = [
        'id',
        'image',
        'contract_address',
        'ethereum',
        'binance',
        'polygon'
    ];

    public $timestamps = false;


}
