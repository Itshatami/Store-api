<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductResource;
use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductImage;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $products = Product::paginate(10);
        return $this->sResponse([
            'products' => ProductResource::collection($products->load('images')),
            'links' => ProductResource::collection($products)->response()->getData()->links,
            'meta' => ProductResource::collection($products)->response()->getData()->links
        ], 'success', 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'brand_id' => 'required|integer',
            'category_id' => 'required|integer',
            'primary_image' => 'required|image',
            'price' => 'integer',
            'quantity' => 'integer',
            'delivery_amount' => 'integer',
            'description' => 'required',
            'images.*' => 'nullable|image'
        ]);
        if ($validator->fails()) {
            return $this->eResponse($validator->messages(), 400);
        }

        if ($request->has('primary_image')) {
            $primaryImageName = Carbon::now()->microsecond . '.' . $request->primary_image->extension();
            $request->primary_image->storeAs('images/products', $primaryImageName, 'public');
        }


        $product = Product::create([
            'brand_id' => $request->brand_id,
            'category_id' => $request->category_id,
            'name' => $request->name,
            'primary_image' => $primaryImageName,
            'price' => $request->price,
            'quantity' => $request->quantity,
            'delivery_amount' => $request->delivery_amount,
            'description' => $request->description,
        ]);

        if ($request->has('images')) {
            $fileNameImages = [];
            foreach ($request->images as $image) {
                $imageName = Carbon::now()->microsecond . '.' . $image->extension();
                $image->storeAs('images/products', $imageName, 'public');
                array_push($fileNameImages, $imageName);
            }
        }

        if ($request->has('images')) {
            foreach ($fileNameImages as $fileNameImage) {
                ProductImage::create([
                    'product_id' => $product->id,
                    'image' => $fileNameImage
                ]);
            }
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        if (!$product) {
            return $this->eResponse('not found product', 400);
        }
        return $this->sResponse(new ProductResource($product->load('images')));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        if (!$product) {
            return $this->eResponse('not found', 404);
        }
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'brand_id' => 'required|integer',
            'category_id' => 'required|integer',
            'primary_image' => 'nullable|image',
            'price' => 'integer',
            'quantity' => 'integer',
            'delivery_amount' => 'integer',
            'description' => 'required',
            'images.*' => 'nullable|image'
        ]);
        if ($validator->fails()) {
            return $this->eResponse($validator->messages(), 200);
        }

        try {
            DB::beginTransaction();

            if ($request->has('primary_image')) {
                $primaryImageName = Carbon::now()->microsecond . '.' . $request->primary_image->extension();
                $request->primary_image->storeAs('images/products', $primaryImageName, 'public');
            }
            if ($request->has('images')) {
                $fileNameImages = [];
                foreach ($request->images as $image) {
                    $imageName = Carbon::now()->microsecond . '.' . $image->extension();
                    $image->storeAs('images/products', $imageName, 'public');
                    array_push($fileNameImages, $imageName);
                }
            }

            $product->update([
                'brand_id' => $request->brand_id,
                'category_id' => $request->category_id,
                'name' => $request->name,
                'primary_image' => $request->has('primary_image') ? $primaryImageName : $product->primary_image,
                'price' => $request->price,
                'quantity' => $request->quantity,
                'delivery_amount' => $request->delivery_amount,
                'description' => $request->description,
            ]);

            if ($request->has('images')) {
                foreach ($product->images as $productImage) {
                    $productImage->delete();
                }
                foreach ($fileNameImages as $fileNameImage) {
                    ProductImage::create([
                        'product_id' => $product->id,
                        'image' => $fileNameImage
                    ]);
                }
            }

            DB::commit();

            return $this->sResponse(new ProductResource($product->load('images')));
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->eResponse($th->getMessage(), 200);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        if (!$product) {
            return $this->eResponse('not found', 404);
        }
        try {
            DB::beginTransaction();
            $product->delete();
            DB::commit();

            return $this->sResponse(new ProductResource($product), 'deleted');
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->eResponse($th->getMessage(), 200);
        }
    }
}
