<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetLineSubEntry extends Model
{
    protected $fillable = [
        'budget_line_item_id', 'name',
        'quantity', 'rate', 'frequency',
        'm1_amount',  'm2_amount',  'm3_amount',  'm4_amount',
        'm5_amount',  'm6_amount',  'm7_amount',  'm8_amount',
        'm9_amount',  'm10_amount', 'm11_amount', 'm12_amount',
        'total_amount', 'sort_order',
    ];

    protected $casts = [
        'm1_amount'  => 'float', 'm2_amount'  => 'float', 'm3_amount'  => 'float',
        'm4_amount'  => 'float', 'm5_amount'  => 'float', 'm6_amount'  => 'float',
        'm7_amount'  => 'float', 'm8_amount'  => 'float', 'm9_amount'  => 'float',
        'm10_amount' => 'float', 'm11_amount' => 'float', 'm12_amount' => 'float',
        'total_amount' => 'float',
        'quantity'     => 'float',
        'rate'         => 'float',
        'frequency'    => 'float',
    ];

    public function lineItem()
    {
        return $this->belongsTo(BudgetLineItem::class, 'budget_line_item_id');
    }
}
