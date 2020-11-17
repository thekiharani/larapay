<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MpesaC2BTransactions extends Model
{
    use HasFactory;
    protected $table = 'mpesa_c2b_transactions';
    protected $guarded = [];
}
