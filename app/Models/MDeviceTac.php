<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MDeviceTac extends Model
{
    use HasFactory;

    protected $table = 'device_tac';

    protected $fillable = [
        'tac',
        'brand',
        'model',
        'storage',
        'ram',
        'kategori',
    ];
}
