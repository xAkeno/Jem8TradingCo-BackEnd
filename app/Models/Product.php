<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $primaryKey = 'product_id';  // your PK
    protected $table = 'products';          // optional if table name matches

    protected $fillable = [
        'product_name',
        'category_id',
        'product_stocks',
        'description',
        'price',
        'isSale',
        'reviews_id',
    ];

    // A product can have many images
    public function images()
    {
        return $this->hasMany(ProductImage::class, 'product_id', 'product_id');
    }

    // A product can have many cart items
    public function carts()
    {
        return $this->hasMany(Cart::class, 'product_id', 'product_id');
    }
}