<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EditorActivity extends Model
{
    protected $fillable = [
        'project_id', 
        'current_data', 
        'last_open_page', 
        'is_active'
    ];
    protected $casts = ['current_data' => 'array'];
}