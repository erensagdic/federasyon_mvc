<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class dersPlaniModel extends Model
{
    use HasFactory;
    protected $table = "ders_plani";
    protected $fillable = [
        'kurum_id',
        'ders_id',
        'sinif',
        'ogrenci_sayisi',
        'sure',
        'konu',
        'kazanimlar',
        'arac_gerec',
        'dersin_islenisi',
    ];
    public function ders()
    {
        return $this->hasOne(kurumDersModel::class, 'id', 'ders_id');
    }
    public function ogretmen()
    {
        return $this->hasOne(ogretmenDersModel::class, 'ders_id', 'ders_id');
    }
}
