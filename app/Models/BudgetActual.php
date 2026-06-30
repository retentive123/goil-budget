<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class BudgetActual extends Model
{
    use LogsActivity;

    protected $fillable = [
        'budget_line_item_id',
        'budget_period_id',
        'department_id',
        'account_code_id',
        'month',
        'year',
        'amount',
        'description',
        'reference',
        'recorded_by',
        'approved_by',
        'status',
    ];

    protected $casts = [
        'amount' => 'float',
        'month'  => 'integer',
        'year'   => 'integer',
    ];

    const MONTHS = [
        1=>'January',2=>'February',3=>'March',
        4=>'April',5=>'May',6=>'June',
        7=>'July',8=>'August',9=>'September',
        10=>'October',11=>'November',12=>'December',
    ];

    public function lineItem()
    {
        return $this->belongsTo(BudgetLineItem::class, 'budget_line_item_id');
    }

    public function period()
    {
        return $this->belongsTo(BudgetPeriod::class, 'budget_period_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function accountCode()
    {
        return $this->belongsTo(AccountCode::class);
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['amount','month','year','status','reference'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn($e) => "Budget actual {$e}");
    }

    // Quarter this month belongs to
    public function quarter(): int
    {
        return (int) ceil($this->month / 3);
    }

    public static function monthName(int $month): string
    {
        return self::MONTHS[$month] ?? '';
    }
}
