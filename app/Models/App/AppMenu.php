<?php

namespace App\Models\App;

use Illuminate\Database\Eloquent\Model;

class AppMenu extends Model
{
    protected $fillable = ['app_id', 'label', 'system_icon_id', 'order'];

    public function subModules() 
    { 
        return $this->hasMany(AppSubModule::class, 'menu_id'); 
    }
}