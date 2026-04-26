<?php
namespace App\Models\App;

use Illuminate\Database\Eloquent\Model;

class AppRole extends Model
{
    protected $fillable = ['app_id', 'name'];

    public function users() { return $this->hasMany(AppUser::class, 'app_role_id'); }
}