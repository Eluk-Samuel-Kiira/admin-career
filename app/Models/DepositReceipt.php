<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DepositReceipt extends Model
{
    use HasFactory;

    protected $fillable = [
        'deposit_id',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
        'receipt_number',
        'description',
        'is_primary',
        'uploaded_by',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'file_size' => 'integer',
    ];

    public function deposit()
    {
        return $this->belongsTo(Deposit::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getFileUrlAttribute()
    {
        if (!$this->file_path) {
            return null;
        }
        // Use asset() helper which respects APP_URL
        return asset('storage/' . $this->file_path);
    }

    public function getFormattedSizeAttribute()
    {
        $bytes = $this->file_size;
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } elseif ($bytes > 1) {
            return $bytes . ' bytes';
        } elseif ($bytes == 1) {
            return '1 byte';
        } else {
            return '0 bytes';
        }
    }
}