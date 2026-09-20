<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BosPenerimaan extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'bos_penerimaans';

    protected $fillable = [
        'funding_source',
        'year',
        'category',
        'transaction_date',
        'reference_number',
        'description',
        'amount',
        'notes',
        'receipt_file',
        'created_by',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'decimal:2',
        'year' => 'integer',
    ];

    public const CATEGORIES = [
        'salur_tahap_1' => 'Penyaluran BOSP Tahap 1',
        'salur_tahap_2' => 'Penyaluran BOSP Tahap 2',
        'jasa_giro' => 'Pendapatan Bunga Bank (Jasa Giro)',
        'silpa' => 'Sisa SILPA Tahun Lalu',
        'lainnya' => 'Penerimaan Lainnya',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? ucfirst(str_replace('_', ' ', $this->category));
    }

    public function getFormattedAmountAttribute(): string
    {
        return 'Rp '.number_format($this->amount, 0, ',', '.');
    }
}
