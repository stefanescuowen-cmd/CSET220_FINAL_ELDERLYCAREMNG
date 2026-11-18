<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PatientGroup extends Model
{
    protected $fillable = ['name', 'description'];

    public function patients(): HasMany
    {
        return $this->hasMany(Patient::class, 'group_id');
    }
}
