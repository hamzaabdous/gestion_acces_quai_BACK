<?php

namespace App\Modules\UserVesselHistories\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Modules\UserVessel\Models\UserVessel;
use App\Modules\UserVesselBadges\Models\UserVesselBadges;
use App\Modules\WorkareaVesselProfile\Models\WorkareaVesselProfile;
class UserVesselHistories extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_vessel_id',
        'shift',
        'work_date',
        'work_date',
        'overtime',
        'workarea',
    ];

    // Relation to UserVessel
    public function userVessel()
    {
        return $this->belongsTo(UserVessel::class);
    }
    public function badges()
    {
        return $this->hasMany(UserVesselBadges::class, 'user_vessel_history_id');
    }
    public function profile()
    {
        return $this->belongsTo(WorkareaVesselProfile::class, 'profile_id');
    }
}
