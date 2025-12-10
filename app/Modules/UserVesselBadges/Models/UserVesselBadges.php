<?php

namespace App\Modules\UserVesselBadges\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Modules\UserVesselHistories\Models\UserVesselHistories;
class UserVesselBadges extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_vessel_history_id',
        'badge_place',
        'badge_date',
        'action',
        'device_id',
    ];

    public function history()
    {
        return $this->belongsTo(UserVesselHistories::class, 'user_vessel_history_id');
    }
}
