<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FalloutData extends Model
{
    protected $table = 'fallout_data';

    protected $primaryKey = 'row_id';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'batch_id',
        'order_id',
        'deskripsi',
        'sto',
        'tanggal',
        'pic',
        'resolved_eskalasi',
        'status',
        'ket',
        'uploaded_by',
        'uploaded_at',
        'tiket_eskalasi',
];

    protected $casts = [
        'tanggal'    => 'date',
        'uploaded_at' => 'datetime',
    ];

    public function batch()
    {
        return $this->belongsTo(
            UploadBatch::class,
            'batch_id',
            'batch_id'
        );
    }

    public function uploader()
    {
        return $this->belongsTo(
            User::class,
            'uploaded_by',
            'id'
        );
    }

    public function scopeForWitel($query, string $witel)
    {
        return $query->whereHas(
            'batch.branch',
            function ($q) use ($witel) {
                $q->where(
                    'kode_cabang',
                    $witel
                );
            }
        );
    }
}
