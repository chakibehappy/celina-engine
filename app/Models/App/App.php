<?php
namespace App\Models\App;

use Illuminate\Database\Eloquent\Model;

class App extends Model
{
    protected $fillable = ['name', 'slug', 'database_name', 'starting_screen', 'login_type', 'navigation_type'];

    public function users() { return $this->hasMany(AppUser::class); }
    public function screens() { return $this->hasMany(AppScreen::class); }
    public function navigations() { return $this->hasMany(AppNavigation::class); }
}