<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Employee extends Model
{
    protected $fillable = ['user_id', 'employee_number', 'salary', 'hire_date'];
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
