<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'activity_logs';

    /**
     * The primary key for the model.
     */
    protected $primaryKey = 'activity_id';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'user_name',
        'action',
        'category',
        'product_name',
        'product_unique_code',
        'mode_of_payment',
        'amount',
        'reference_table',
        'reference_id',
        'description',
        'logged_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'amount'     => 'decimal:2',
        'logged_at'  => 'datetime',
        'created_at' => 'datetime',
    ];

    // -------------------------------------------------------
    // Relationships
    // -------------------------------------------------------

    /**
     * Get the user (account) that performed this activity.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // -------------------------------------------------------
    // Scopes — matches the UI tab filters
    // -------------------------------------------------------

    public function scopeOrders($query)
    {
        return $query->where('category', 'orders');
    }

    public function scopeStock($query)
    {
        return $query->where('category', 'stock');
    }

    public function scopeAccount($query)
    {
        return $query->where('category', 'account');
    }

    public function scopeBlogs($query)
    {
        return $query->where('category', 'blogs');
    }

    public function scopePayments($query)
    {
        return $query->where('category', 'payments');
    }

    public function scopeBackups($query)
    {
        return $query->where('category', 'backups');
    }

    /**
     * Scope: filter by date (for grouped date display in UI).
     */
    public function scopeOnDate($query, $date)
    {
        return $query->whereDate('logged_at', $date);
    }

    /**
     * Scope: search across name, action, product, code, description.
     */
    public function scopeSearch($query, string $keyword)
    {
        return $query->where(function ($q) use ($keyword) {
            $q->where('user_name', 'like', "%{$keyword}%")
              ->orWhere('action', 'like', "%{$keyword}%")
              ->orWhere('product_name', 'like', "%{$keyword}%")
              ->orWhere('product_unique_code', 'like', "%{$keyword}%")
              ->orWhere('description', 'like', "%{$keyword}%");
        });
    }

    // -------------------------------------------------------
    // Static log() helper — use anywhere in your app
    // -------------------------------------------------------

    /**
     * Quick log helper.
     *
     * Basic usage:
     *   ActivityLog::log($user, 'Placed order', 'orders');
     *
     * With full details:
     *   ActivityLog::log($user, 'Placed order', 'orders', [
     *       'product_name'        => 'Organic Barley',
     *       'product_unique_code' => 'ORDER-001',
     *       'mode_of_payment'     => 'GCash',
     *       'amount'              => 100.00,
     *       'reference_table'     => 'checkouts',
     *       'reference_id'        => 12,
     *       'description'         => 'Organic Barley x3 Office Supplies x1',
     *   ]);
     *
     * Blog example:
     *   ActivityLog::log($user, 'Published blog post', 'blogs', [
     *       'reference_table' => 'blogs',
     *       'reference_id'    => $blog->id,
     *       'description'     => $blog->title,
     *   ]);
     *
     * Backup example:
     *   ActivityLog::log($user, 'Created backup', 'backups', [
     *       'reference_table' => 'admin_backups',
     *       'reference_id'    => $backup->id,
     *   ]);
     *
     * Account example:
     *   ActivityLog::log($user, 'Updated profile', 'account', [
     *       'reference_table' => 'accounts',
     *       'reference_id'    => $user->id,
     *   ]);
     */
    public static function log($user, string $action, string $category = 'other', array $extra = []): self
    {
        return self::create(array_merge([
            'user_id'   => $user->id,
            'user_name' => $user->name,
            'action'    => $action,
            'category'  => $category,
            'logged_at' => now(),
        ], $extra));
    }
}