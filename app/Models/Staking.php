<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Staking extends Model
{
    use HasFactory;
	
    protected $table = 'staking';

	protected $fillable = [
        'contract_address',
        'staked_token',
        'reward_token',
        'reward_per_block',
        'start_block',
        'end_block',
        'poolLimitPerUser',
        'project_url',
        'is_end',
    ];





}
