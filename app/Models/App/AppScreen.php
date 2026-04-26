<?php

namespace App\Models\App;

use Illuminate\Database\Eloquent\Model;

class AppScreen extends Model
{
    // Allow these fields to be filled via AppScreen::create() or $screen->update()
    protected $fillable = [
        'app_id', 
        'route', 
        'title', 
        'type', 
        'content_data'
    ];

    /**
     * Relationship for screens that contain multiple menus (e.g., a dashboard)
     */
    public function menus()
    {
        return $this->belongsToMany(AppMenu::class, 'screen_menus', 'screen_id', 'menu_id')
                    ->withPivot('order')
                    ->orderBy('pivot_order');
    }
}