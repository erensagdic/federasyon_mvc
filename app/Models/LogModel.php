<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogModel extends Model
{
    use HasFactory;
    protected $table = "loglar";
    protected $fillable = [
        'kategori_id',
        'logText',
    ];

    public function kategori()
    {
        return $this->hasOne(LogKategoriModel::class, 'id', 'kategori_id');
    }
}
