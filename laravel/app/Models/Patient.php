<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    protected $fillable = ['user_id', 'group_id', 'admission_date', 'emergency_contact_name', 'emergency_contact_relationship', 'emergency_contact_phone'];
    protected function casts(): array { return ['admission_date' => 'date']; }
    public function user() { return $this->belongsTo(User::class); }
    public function group() { return $this->belongsTo(PatientGroup::class, 'group_id'); }
    public function appointments() { return $this->hasMany(Appointment::class); }
}
