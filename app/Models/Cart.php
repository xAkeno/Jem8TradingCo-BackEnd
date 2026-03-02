<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;

    protected $primaryKey = 'cart_id';  // your PK
    protected $table = 'cart';          // optional if table name matches

    protected $fillable = [
        'user_id',
        'product_id',
        'quantity',
        'total',
        'status',
    ];

    // A cart item belongs to a product
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }

    // A cart item belongs to a user
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}