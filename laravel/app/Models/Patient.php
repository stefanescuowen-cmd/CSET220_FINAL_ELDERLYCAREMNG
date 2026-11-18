<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read User|null $user
 * @property-read PatientGroup|null $group
 * @property-read \Illuminate\Support\Collection<int, Appointment>|null $appointments
 */
class Patient extends Model
{
    protected $fillable = ['user_id', 'group_id', 'admission_date', 'emergency_contact_name', 'emergency_contact_relationship', 'emergency_contact_phone'];
    protected function casts(): array { return ['admission_date' => 'date']; }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function group(): BelongsTo { return $this->belongsTo(PatientGroup::class, 'group_id'); }
    public function appointments(): HasMany { return $this->hasMany(Appointment::class); }
}
