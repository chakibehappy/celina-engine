<?php
namespace App\Models\App;

use Illuminate\Database\Eloquent\Model;

class App extends Model
{
    protected $fillable = ['name'];

    public function users() { return $this->hasMany(AppUser::class); }
    public function screens() { return $this->hasMany(AppScreen::class); }
    public function navigations() { return $this->hasMany(AppNavigation::class); }
}