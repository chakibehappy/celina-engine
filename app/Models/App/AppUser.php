<?php
namespace App\Models\App;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class AppUser extends Authenticatable
{
    use HasApiTokens;

    protected $fillable = ['app_id', 'app_role_id', 'name', 'email', 'password'];
    protected $hidden = ['password'];

    public function app() { return $this->belongsTo(App::class); }
    public function role() { return $this->belongsTo(AppRole::class, 'app_role_id'); }
}