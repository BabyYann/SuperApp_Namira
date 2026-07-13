<?php

namespace App\Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Modules\Academic\Models\Student;

class StudentBill extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'billing_date' => 'date',
        'due_date' => 'date',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function financeType()
    {
        return $this->belongsTo(FinanceType::class);
    }

    public function transactions()
    {
        return $this->belongsToMany(Transaction::class, 'bill_payments')->withPivot('amount')->withTimestamps();
    }
}
