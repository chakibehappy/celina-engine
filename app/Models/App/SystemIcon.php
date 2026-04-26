<?php

namespace App\Models\App;

use Illuminate\Database\Eloquent\Model;

class SystemIcon extends Model
{
    /**
     * The attributes that are mass assignable.
     * Updated to match your multi-stack visual dictionary.
     */
    protected $fillable = [
        'tag',
        'type',
        'url',
        'kotlin_value',
        'flutter_value',
        'expo_value'
    ];

    /**
     * Relationship: Icons used in Sub Modules
     */
    public function subModules()
    {
        return $this->hasMany(AppSubModule::class, 'system_icon_id');
    }

    /**
     * Relationship: Icons used in Bottom Navigation
     */
    public function navigations()
    {
        return $this->hasMany(AppNavigation::class, 'system_icon_id');
    }
}