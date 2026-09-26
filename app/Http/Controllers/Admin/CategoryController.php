<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Requests\Admin\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Category::class);

        return Inertia::render('Admin/Categories/Index', [
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Category::class);

        return Inertia::render('Admin/Categories/Create');
    }

    public function store(StoreCategoryRequest $request)
    {
        Category::create($request->validated());

        return to_route('admin.categories.index');
    }

    public function edit(Category $category): Response
    {
        $this->authorize('update', $category);

        return Inertia::render('Admin/Categories/Edit', [
            'category' => $category,
        ]);
    }

    public function update(UpdateCategoryRequest $request, Category $category)
    {
        $category->update($request->validated());

        return to_route('admin.categories.index');
    }

    public function destroy(Category $category)
    {
        $this->authorize('delete', $category);

        if ($category->tickets()->exists()) {
            throw ValidationException::withMessages([
                'category' => 'No se puede eliminar: ya tiene tickets registrados con esta categoría.',
            ]);
        }

        $category->delete();

        return to_route('admin.categories.index');
    }
}
