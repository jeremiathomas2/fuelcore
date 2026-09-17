<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpenseCategory extends Model
{
    protected $guarded = [];

    public const DEFAULTS = ['Utilities', 'Salaries', 'Maintenance', 'Transport', 'Security', 'Cleaning', 'Supplies', 'Fuel Loss', 'Rent', 'Other'];

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'category_id');
    }
}