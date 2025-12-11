<?php

namespace App\Modules\UserVesselHistories\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Modules\UserVessel\Models\UserVessel;
use App\Modules\UserVesselBadges\Models\UserVesselBadges;
use App\Modules\WorkareaVesselProfile\Models\WorkareaVesselProfile;
use App\Modules\User\Models\User;
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
        "user_id",
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
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
