<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Recipe;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\File;

class RecipeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $query = Recipe::query();

        if (request()->has('search')) {
            $query->where('title', 'like', '%' . request('search') . '%');
        }

        // Paginated results
        $recipes = $query->with('category')->latest()->paginate(
            request()->has('per_page') ? intval(request('per_page')) : 10
        )->appends(
            request()->query()
        );

        if (empty(request('search'))) {
            $allRecipes = Recipe::with('category')->get();
            $recipes = new \Illuminate\Pagination\LengthAwarePaginator(
                $allRecipes,
                $allRecipes->count(),
                $recipes->perPage(),
                $recipes->currentPage()
            );
        }

        return [
            "status" => 1,
            "data" => $recipes
        ];
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'title' => 'required',
                'body' => 'required',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Validation rules for the image file
                'image_url' => 'nullable|url', // Validation rule for the image URL
                'category_id' => 'required|exists:categories,id',

            ]);
            $imagePath = null;

            // Handle image upload
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imagePath = 'storage/' . $image->store('recipe_images', 'public');
            }

            // Use the image URL if provided and no image upload
            if (!$imagePath && $request->has('image_url')) {
                $imagePath = $request->input('image_url');
            }

            // Validate and parse the 'ingredients' field
            $ingredients = $request->input('ingredients');
            $decodedIngredients = json_decode($ingredients);

            if (json_last_error() !== JSON_ERROR_NONE) {
                // Handle the case where 'ingredients' is not a valid JSON string
                return response()->json(['message' => 'Invalid JSON format for ingredients.'], 400);
            }

            $recipe = Recipe::create([
                'title' => $request->input('title'),
                'category' => $request->input('category'),
                'body' => $request->input('body'),
                'ingredients' => $request->input('ingredients'),
                'image' => $imagePath,
                'category_id' => $request->input('category_id'),
            ]);

            // Retrieve the full image URL
            //$imageUrl = $imagePath ? url(Storage::url($imagePath)) : null;

            $imagePath = $request->file('image')->store('recipe_images', 'public');
            $imageUrl = url(Storage::url($imagePath));

            // Return the response with the recipe and image URL
            return response()->json([
                'recipe' => $recipe,
                'image_url' => $imageUrl,
            ], 201);

            return response()->json($recipe, 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Recipe  $recipe
     * @return \Illuminate\Http\Response
     */
    public function show(Recipe $recipe)
    {
        return [
            "status" => 1,
            "data" => $recipe
        ];
    }

    public function getRecipeImage($filename)
    {
        $path = 'recipe_images/' . $filename;

        if (Storage::disk('public')->exists($path)) {
            $fileContents = Storage::disk('public')->get($path);

            $fileExtension = File::extension($path); //method to retrieve the file extension
            $contentType = File::mimeType($path); //mimeType since I dont know what image type the user might upload

            return Response::make($fileContents, 200, [
                'Content-Type' => $contentType,
            ]);
        }

        abort(404);
    }
    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Recipe  $recipe
     * @return \Illuminate\Http\Response
     */
    public function edit(Recipe $recipe)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Recipe  $recipe
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Recipe $recipe)
    {
        $request->validate([
            'title' => 'required',
            'body' => 'required',
        ]);

        $recipe->update($request->all());

        return [
            "status" => 1,
            "data" => $recipe,
            "msg" => "Recipe updated successfully"
        ];
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Recipe  $recipe
     * @return \Illuminate\Http\Response
     */
    public function destroy(Recipe $recipe)
    {
        $recipe->delete();
        return [
            "status" => 1,
            "data" => $recipe,
            "msg" => "Recipe deleted successfully"
        ];
    }
}
