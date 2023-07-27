<?php


namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Recipe;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class RecipeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $searchQuery = $request->query('search');

        // Query builder with search condition
        $recipes = Recipe::with('category')
            ->when($searchQuery, function ($query) use ($searchQuery) {
                $query->where('title', 'like', '%' . $searchQuery . '%');
            })
            ->latest()
            ->paginate(10);

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

        \Log::debug('Received Data:', $request->all());
        try {
            $request->validate([
                'title' => 'required',
                'body' => 'required',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'image_url' => 'nullable|url',
                'category_id' => 'required|exists:categories,id',
                'ingredients' => 'required|array',
            ]);

            $imagePath = null;
            // Handle image upload
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imagePath = 'storage/' . $image->store('recipe_images', 'public');
            } elseif ($request->has('image_url')) {
                $imageUrl = $request->input('image_url');
                // Extract the image file from the data URI
                $data = explode(',', $imageUrl);
                $imageData = base64_decode($data[1]);
                $extension = Str::afterLast($data[0], '/');
                $fileName = Str::random(40) . '.' . $extension;
                // Store the image file
                Storage::disk('public')->put('recipe_images/' . $fileName, $imageData);
                $imagePath = 'storage/recipe_images/' . $fileName;
            }

            // Convert ingredients to have actual line breaks (\n)
            $ingredients = $request->input('ingredients');

            $ingredientsWithLineBreaks = str_replace("\n* ", "\n", $ingredients);

            $recipe = Recipe::create([
                'title' => $request->input('title'),
                'category_id' => $request->input('category_id'),
                'body' => $request->input('body'),
                'ingredients' => $ingredientsWithLineBreaks,
                'image' => $imagePath,
            ]);

            // Retrieve the full image URL
            $imageUrl = $imagePath ? url(Storage::url($imagePath)) : null;

            // Return the response with CORS headers
            return response()->json([
                'recipe' => $recipe,
                'image_url' => $imageUrl,
            ], 201)->header('Access-Control-Allow-Origin', '*');
        } catch (\Exception $e) {
            \Log::error('Error: ' . $e->getMessage());
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
