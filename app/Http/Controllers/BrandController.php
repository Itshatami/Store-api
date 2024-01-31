<?php

namespace App\Http\Controllers;

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
        $brands = Brand::all();
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
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
