<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Product::active();

            // Search by keyword
            if ($request->has('search')) {
                $query->search($request->search);
            }

            // Filter by price range
            if ($request->has('min_price') && $request->has('max_price')) {
                $query->filterByPrice($request->min_price, $request->max_price);
            }

            $products = $query->paginate(12);

            return response()->json([
                'success' => true,
                'message' => 'Daftar produk berhasil diambil',
                'data' => ProductResource::collection($products),
                'pagination' => [
                    'total' => $products->total(),
                    'per_page' => $products->perPage(),
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan',
                'errors' => ['error' => $e->getMessage()],
            ], 500);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $product = Product::active()->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Detail produk berhasil diambil',
                'data' => new ProductResource($product),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Produk tidak ditemukan',
                'errors' => [],
            ], 404);
        }
    }

    public function store(CreateProductRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $data['seller_id'] = $request->user()->id;
            $data['status'] = $data['status'] ?? 'active';

            $product = Product::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Produk berhasil dibuat',
                'data' => new ProductResource($product),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan',
                'errors' => ['error' => $e->getMessage()],
            ], 500);
        }
    }

    public function update(UpdateProductRequest $request, $id): JsonResponse
    {
        try {
            $product = Product::findOrFail($id);

            // Check if user is the seller
            if ($product->seller_id !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki izin untuk mengubah produk ini',
                    'errors' => [],
                ], 403);
            }

            $product->update($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Produk berhasil diperbarui',
                'data' => new ProductResource($product),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Produk tidak ditemukan',
                'errors' => [],
            ], 404);
        }
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        try {
            $product = Product::findOrFail($id);

            // Check if user is the seller
            if ($product->seller_id !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki izin untuk menghapus produk ini',
                    'errors' => [],
                ], 403);
            }

            $product->delete();

            return response()->json([
                'success' => true,
                'message' => 'Produk berhasil dihapus',
                'data' => null,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Produk tidak ditemukan',
                'errors' => [],
            ], 404);
        }
    }

    public function sellerProducts(Request $request): JsonResponse
    {
        try {
            $query = Product::where('seller_id', $request->user()->id);

            // Search by keyword
            if ($request->has('search')) {
                $query->search($request->search);
            }

            $products = $query->paginate(12);

            return response()->json([
                'success' => true,
                'message' => 'Daftar produk seller berhasil diambil',
                'data' => ProductResource::collection($products),
                'pagination' => [
                    'total' => $products->total(),
                    'per_page' => $products->perPage(),
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan',
                'errors' => ['error' => $e->getMessage()],
            ], 500);
        }
    }
}
