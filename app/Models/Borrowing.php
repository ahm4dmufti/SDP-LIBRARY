<?php

namespace App\Models;

use Database\Factories\BorrowingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Borrowing extends Model
{
    /** @use HasFactory<BorrowingFactory> */
    use HasFactory;

    protected $fillable =
        [
            'customer_id',
            'book_id',
            'borrowing_date',
            'return_date',
            'is_returned',
        ];

    protected $casts =
        [
            'is_returned' => 'boolean',
        ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}
