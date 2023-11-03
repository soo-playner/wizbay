<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CacheData extends Model
{
    use HasFactory;
	
    protected $table = 'cache_data';
	protected $casts = [
		'id' => 'string',
	];

	protected $fillable = [
        'id',
        'data',
    ];
}
