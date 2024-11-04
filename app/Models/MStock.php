<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MStock extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'stok_hp';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id_stock';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * The "type" of the auto-incrementing ID.
     *
     * @var string
     */
    protected $keyType = 'int';

    /**
     * Enable timestamps.
     *
     * @var bool
     */
    public $timestamps = false; // Disable timestamps as the table has 'created_at' and 'updated_at'

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'tanggal',
        'imei',
        'brand',
        'nama',
        'warna',
        'harga_masuk',
        'harga_jual',
        'id_sales',
        'status'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'tanggal' => 'date',
        'harga_masuk' => 'float',
        'harga_jual' => 'float',
        'id_sales' => 'integer',
        'status' => 'string',
    ];

    /**
     * Define the relationship with the user (salesperson).
     */
    public function sales()
    {
        return $this->belongsTo(MUsers::class, 'id_sales', 'id_user')->withDefault();
    }
}
