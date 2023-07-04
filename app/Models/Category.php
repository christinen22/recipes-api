<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    public function recipes()
    {
        return $this->hasMany(Recipe::class);
    }

    protected $fillable = [
        'name',
    ];

    protected $appends = ['recipes'];

    public function getRecipesAttribute()
    {
        return $this->recipes()->get(['id', 'title']);
    }
}
