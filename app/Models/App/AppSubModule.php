<?php

namespace App\Models\App;

use Illuminate\Database\Eloquent\Model;

class AppSubModule extends Model 
{
    protected $fillable = ['menu_id', 'label', 'description', 'icon_id', 'table_name'];

    public function menu() 
    { 
        return $this->belongsTo(AppMenu::class, 'menu_id'); 
    }
}