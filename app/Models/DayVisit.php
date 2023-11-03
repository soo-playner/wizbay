<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DayVisit extends Model
{
    use HasFactory;

    protected $table = "dayVisits";

    protected $fillable = [
        'date',
        'cnt'
    ];

    public $timestamps = false;
}
