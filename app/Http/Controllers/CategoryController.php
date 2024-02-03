<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = Category::paginate(10);
        if (!$categories) {
            return $this->eResponse('there is no category', 400);
        }
        return $this->sResponse($categories, 'all the categories', 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'parent_id' => 'required|integer',
            'name' => 'required|unique:categories,name'
        ]);
        if ($validator->fails()) {
            return $this->eResponse($validator->messages(), 400);
        }
        $category = Category::create([
            'parent_id' => $request->parent_id,
            'name' => $request->name
        ]);
        if ($category) {
            return $this->sResponse($category, 'created', 200);
        } else {
            return $this->eResponse('cold not save', 400);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category)
    {
        if (!$category) {
            return $this->eResponse('category not found', 400);
        }
        return $this->sResponse($category, 'here it is', 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Category $category)
    {
        if (!$category) {
            return $this->eResponse('category not found', 400);
        }
        $validator = Validator::make($request->all(), [
            'parent_id' => 'required|integer',
            'name' => 'required|unique:categories,name'
        ]);
        if (!$validator->fails()) {
            return $this->eResponse($validator->messages(), 400);
        }
        $category->update([
            'parent_id' => $request->parent_id,
            'name' => $request->name
        ]);
        return $this->sResponse($category, 'updated', 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {
        if (!$category) {
            return $this->eResponse('category not found', 400);
        }
        $category->delete();
        $this->sResponse($category, 'deleted', 200);
    }
}
