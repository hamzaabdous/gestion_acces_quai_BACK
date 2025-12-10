<?php

namespace App\Modules\WorkareaVesselProfile\Models;

use Illuminate\Database\Eloquent\Model;
use App\Modules\UserVesselHistories\Models\UserVesselHistories;
class WorkareaVesselProfile extends Model
{
    protected $fillable = ['workarea', 'vessel_name', 'device'];

    public function histories()
    {
        return $this->hasMany(UserVesselHistories::class, 'profile_id');
    }
}
