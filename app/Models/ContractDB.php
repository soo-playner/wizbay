<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use DateTimeInterface;

class ContractDB extends Model
{
    use HasFactory;
	
    protected $table = 'contractdb';
	protected $primaryKey = 'table_name';
	protected $casts = [
		'table_name' => 'string',
	];
	
	protected $fillable = [
        'table_name',
        'table_id',
    ];
	

}
