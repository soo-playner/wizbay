<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Visit extends Model
{
    use HasFactory;

    protected $table = "Visits";

    protected $fillable = [
        'ipAddress',
        'date'
    ];

    protected $hidden = [];

    public $timestamps = false;
}
