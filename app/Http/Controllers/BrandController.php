<?php

namespace App\Http\Controllers;

use App\Http\Resources\BrandResource;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BrandController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $brands = Brand::paginate(5);
        return $this->sResponse($brands, 'all brands', 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'display_name' => 'required|string|unique:brands'
        ]);
        if ($validator->fails()) {
            return $this->eResponse($validator->messages(), 422);
        }

        $brand = Brand::create([
            'name' => $request->name,
            'display_name' => $request->display_name
        ]);
        if ($brand) {
            return $this->sResponse($brand, 'brand created', 202);
        } else {
            return $this->eResponse('could not created', 400);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Brand $brand)
    {
        if (!$brand) {
            return $this->eResponse('brand not found', 400);
        }
        return $this->sResponse($brand, 'show one', 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Brand $brand)
    {
        if (!$brand) {
            return $this->eResponse('brand with given id does not exist', 400);
        }
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'display_name' => 'required|string|unique:brands,display_name'
        ]);
        if ($validator->fails()) {
            return $this->eResponse($validator->errors(), 400);
        }
        $brand->update([
            'name' => $request->name,
            'display_name' => $request->display_name
        ]);
        return $this->sResponse($brand, 'updated', 200);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Brand $brand)
    {
        if ($brand) {
            $brand->delete();
            return $this->sResponse($brand, 'successfully deleted', 200);
        } else {
            return $this->eResponse("Brand Doesn't exist", 404);
        }
    }

    public function products(Brand $brand)
    {
        if (!$brand) {
            return $this->eResponse('brand not found', 400);
        }
        // return 1;
        return $this->sResponse(new BrandResource($brand->load('products')), 'done');
    }
}
