<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'code',
    'name',
    'contact_person',
    'email',
    'phone',
    'address',
    'city',
])]
class Customer extends Model
{
    use HasFactory, SoftDeletes;

    public function goodsIssues(): HasMany
    {
        return $this->hasMany(GoodsIssue::class);
    }
}
