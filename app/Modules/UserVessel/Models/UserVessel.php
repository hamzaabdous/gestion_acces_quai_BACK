<?php

namespace App\Modules\UserVessel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Modules\UserVesselHistories\Models\UserVesselHistories;
class UserVessel extends Model
{
    use HasFactory;

    protected $fillable = [
        'matricule',
        'first_name',
        'last_name',
        'function',
        'company',
        'shift',
        'workarea',
    ];

    public function histories()
    {
        return $this->hasMany(UserVesselHistories::class);
    }
}
