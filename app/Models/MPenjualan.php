<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MPenjualan extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'penjualan';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id_penjualan';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * Enable timestamps
     *
     * @var bool
     */
    public $timestamps = false; // Set to false if you don't want to use timestamps

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'tanggal', 
        'id_stock', 
        'id_sales', 
        'harga_jual',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'tanggal' => 'date',
    ];

    /**
     * Define relationships with other models.
     */
    public function stokHp()
    {
        return $this->belongsTo(MStock::class, 'id_stock');
    }

    public function users()
    {
        return $this->belongsTo(MUsers::class,'id_sales', 'id_user');
    }
}

