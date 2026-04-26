<?php

namespace App\Models\App;

use Illuminate\Database\Eloquent\Model;
use App\Models\App\AppScreen; // <--- Add this!

class AppNavigation extends Model
{
    protected $fillable = ['app_id', 'screen_id', 'label', 'system_icon_id', 'order'];

    public function screen() 
    { 
        return $this->belongsTo(AppScreen::class); 
    }

    public function icon()
    {
        return $this->belongsTo(SystemIcon::class, 'system_icon_id');
    }

}