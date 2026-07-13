<?php

namespace App\Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;

class BillPayment extends Model
{
    protected $guarded = [];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function studentBill()
    {
        return $this->belongsTo(StudentBill::class);
    }
}
