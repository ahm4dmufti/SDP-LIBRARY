<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class CategoryController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return CategoryResource::collection(Category::with('books')->get());
    }

    public function store(StoreCategoryRequest $request): CategoryResource
    {
        $category = Category::create($request->validated());

        return new CategoryResource($category->load('books'));
    }

    public function show(Category $category): CategoryResource
    {
        return new CategoryResource($category->load('books'));
    }

    public function update(UpdateCategoryRequest $request, Category $category): CategoryResource
    {
        $category->update($request->validated());

        return new CategoryResource($category->load('books'));
    }

    /**
     * A category that still has books is not deleted (409), so books are never left without a category.
     */
    public function destroy(Category $category): Response|JsonResponse
    {
        if ($category->books()->exists()) {
            return response()->json([
                'message' => 'Category has books. Delete or move them first.',
            ], 409);
        }

        $category->delete();

        return response()->noContent();
    }
}
