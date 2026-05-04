<?php

namespace App\Http\Controllers;
use App\Models\Product;
use App\Models\Category;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class AdminProductController extends Controller
{
    // ── SHOW all products (server-side paginated + filtered) ──────────────────
    // GET /api/admin/products?page=1&per_page=20&search=&category_id=&sort=asc
    public function showAllProducts(Request $request)
    {
        try {
            $perPage    = min((int) ($request->query('per_page', 20)), 100); // cap at 100
            $search     = $request->query('search', '');
            $categoryId = $request->query('category_id', '');
            $sort       = $request->query('sort', 'asc'); // asc | desc

            $query = Product::with(['category', 'images']);

            // Search filter
            if ($search !== '') {
                $query->where('product_name', 'like', '%' . $search . '%');
            }

            // Category filter
            if ($categoryId !== '') {
                $query->where('category_id', $categoryId);
            }

            // Sort
            $query->orderBy('product_name', $sort === 'desc' ? 'desc' : 'asc');

            $paginated = $query->paginate($perPage);

            return response()->json([
                'success'      => true,
                'data'         => $paginated->items(),
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch products',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    // ── SHOW single product ───────────────────────────────────────────────────
    public function showProduct($id)
    {
        try {
            $product = Product::with(['category', 'images'])->find($id);

            if (!$product) {
                return response()->json(['success' => false, 'message' => 'Product not found'], 404);
            }

            return response()->json(['success' => true, 'product' => $product], 200);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to fetch product', 'error' => $e->getMessage()], 500);
        }
    }

    // ── CREATE Product ────────────────────────────────────────────────────────
    public function addProduct(Request $request)
    {
        try {
            $request->validate([
                'product_name'   => 'required|string',
                'category_id'    => 'required|integer|exists:categories,category_id',
                'description'    => 'nullable|string',
                'price'          => 'required|numeric',
                'isSale'         => 'boolean',
                'acquired_price' => 'nullable|numeric|min:0',
                'unit'           => 'nullable|string|max:255',
                'size'           => 'nullable|string|max:255',
                'color'          => 'nullable|string|max:255',
                'images'         => 'sometimes|array',
                'images.*'       => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:5120',
                'status'         => 'nullable|string|in:in_stock,pre_order',
            ]);

            $product = Product::create([
                'product_name'   => $request->product_name,
                'category_id'    => $request->category_id,
                'description'    => $request->description,
                'status'         => $request->status ?? 'in_stock',
                'price'          => $request->price,
                'isSale'         => $request->isSale ?? false,
                'acquired_price' => $request->acquired_price ?? 0,
                'unit'           => $request->unit ?? null,
                'size'           => $request->size ?? null,
                'color'          => $request->color ?? null,
            ]);

            if ($request->hasFile('images')) {
                $uploadPath = public_path('storage/products');
                if (!file_exists($uploadPath)) mkdir($uploadPath, 0777, true);

                foreach ($request->file('images') as $index => $image) {
                    if (!$image->isValid()) continue;
                    $filename = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                    $image->move($uploadPath, $filename);
                    ProductImage::create([
                        'product_id' => $product->product_id,
                        'image_path' => 'products/' . $filename,
                        'is_primary' => $index === 0,
                    ]);
                }
            }

            $product->load(['category', 'images']);

            DB::table('notifications')->insert([
                'user_id'    => Auth::id(),
                'type'       => 'product',
                'title'      => 'New Product Added',
                'message'    => "\"{$product->product_name}\" was added to the catalog.",
                'is_read'    => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            Cache::forget('dashboard.notifications');

            return response()->json(['success' => true, 'message' => 'Product created successfully', 'product' => $product], 201);

        } catch (\Exception $e) {
            Log::error('Add product failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to create product', 'error' => $e->getMessage()], 500);
        }
    }

    // ── UPDATE Product ────────────────────────────────────────────────────────
    public function updateProduct(Request $request, $id)
    {
        try {
            if ($request->has('remove_images')) {
                $remove = $request->input('remove_images');
                if (is_string($remove)) {
                    $decoded = json_decode($remove, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $request->merge(['remove_images' => array_values($decoded)]);
                    } elseif (strpos($remove, ',') !== false) {
                        $parts = array_filter(array_map('trim', explode(',', $remove)), fn($v) => $v !== '');
                        $request->merge(['remove_images' => array_values($parts)]);
                    } else {
                        $request->merge(['remove_images' => [$remove]]);
                    }
                } elseif (!is_array($remove)) {
                    $request->merge(['remove_images' => [$remove]]);
                }
                $request->merge(['remove_images' => array_map(fn($v) => is_numeric($v) ? (int)$v : $v, $request->input('remove_images'))]);
            }

            $request->validate([
                'product_name'      => 'sometimes|required|string',
                'category_id'       => 'sometimes|required|integer|exists:categories,category_id',
                'description'       => 'nullable|string',
                'status'            => 'nullable|string|in:in_stock,pre_order',
                'price'             => 'sometimes|required|numeric',
                'isSale'            => 'boolean',
                'acquired_price'    => 'nullable|numeric|min:0',
                'unit'              => 'nullable|string|max:255',
                'size'              => 'nullable|string|max:255',
                'color'             => 'nullable|string|max:255',
                'images'            => 'sometimes|array',
                'images.*'          => 'image|mimes:jpeg,png,jpg,gif|max:5120',
                'remove_images'     => 'sometimes|array',
                'remove_images.*'   => 'integer|exists:product_images,id',
                'set_primary_image' => 'sometimes|integer|exists:product_images,id',
            ]);

            $product = Product::find($id);
            if (!$product) {
                return response()->json(['success' => false, 'message' => 'Product not found'], 404);
            }

            $product->update($request->only(['product_name','category_id','description','price','isSale','acquired_price','unit','size','color','status']));

            if ($request->has('remove_images')) {
                $imagesToRemove = ProductImage::whereIn('id', $request->remove_images)
                    ->where('product_id', $product->product_id)->get();
                foreach ($imagesToRemove as $image) {
                    $filePath = public_path('storage/' . $image->image_path);
                    if (file_exists($filePath)) unlink($filePath);
                    $image->delete();
                }
            }

            if ($request->hasFile('images')) {
                $uploadPath = public_path('storage/products');
                if (!file_exists($uploadPath)) mkdir($uploadPath, 0777, true);
                foreach ($request->file('images') as $image) {
                    $filename = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                    $image->move($uploadPath, $filename);
                    ProductImage::create(['product_id' => $product->product_id, 'image_path' => 'products/' . $filename, 'is_primary' => false]);
                }
            }

            if ($request->has('set_primary_image')) {
                $product->images()->update(['is_primary' => false]);
                ProductImage::where('id', $request->set_primary_image)->where('product_id', $product->product_id)->update(['is_primary' => true]);
            }

            if ($product->images()->where('is_primary', true)->count() === 0 && $product->images()->count() > 0) {
                $product->images()->first()->update(['is_primary' => true]);
            }

            return response()->json(['success' => true, 'message' => 'Product updated successfully', 'product' => $product->load(['category','images'])], 200);

        } catch (\Exception $e) {
            Log::error('Update product failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update product', 'error' => $e->getMessage()], 500);
        }
    }

    // ── DELETE Product ────────────────────────────────────────────────────────
    public function deleteProduct($id)
    {
        try {
            $product = Product::find($id);
            if (!$product) {
                return response()->json(['success' => false, 'message' => 'Product not found'], 404);
            }

            foreach ($product->images as $image) {
                $filePath = public_path('storage/' . $image->image_path);
                if (file_exists($filePath)) unlink($filePath);
            }

            $product->delete();
            return response()->json(['success' => true, 'message' => 'Product deleted successfully'], 200);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete product', 'error' => $e->getMessage()], 500);
        }
    }

    // ── STORAGE INFO (debug) ──────────────────────────────────────────────────
    public function storageInfo()
    {
        $storagePath  = public_path('storage');
        $productsPath = public_path('storage/products');
        $files = is_dir($productsPath) ? array_values(array_diff(scandir($productsPath), ['.', '..'])) : [];
        return response()->json([
            'storage_link_exists' => file_exists($storagePath),
            'products_directory'  => ['path' => $productsPath, 'exists' => is_dir($productsPath), 'writable' => is_writable($productsPath), 'files' => $files],
        ]);
    }
}