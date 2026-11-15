<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MPenjualan extends Model
{
    use HasFactory;

    protected $table = 'penjualan';
    protected $primaryKey = 'id_penjualan';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true;

    protected $fillable = [
        'no_invoice',
        'tanggal',
        'id_stock',
        'id_sales',
        'nama_pembeli',
        'no_hp_pembeli',
        'harga_jual',
        'diskon',
        'met_pem',
        'uang_bayar',
        'kembalian',
        'catatan',
    ];

    protected $casts = [
        'tanggal' => 'date:Y-m-d',
        'harga_jual' => 'float',
        'diskon' => 'float',
        'uang_bayar' => 'float',
        'kembalian' => 'float',
        'met_pem' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function stokHp()
    {
        return $this->belongsTo(MStock::class, 'id_stock', 'id_stock');
    }

    public function user()
    {
        return $this->belongsTo(MUsers::class, 'id_sales', 'id_user')->withDefault();
    }

    public function users()
    {
        return $this->user();
    }
}
