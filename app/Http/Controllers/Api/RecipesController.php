<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Recipe;
// use App\Models\RecipeIngredients;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RecipesController extends Controller
{
    public function index() {
        // $recipes = Recipe::where('category_id',3)
        //                         ->get();
        $recipes = Recipe::all();
        return $recipes;
    }

    public function show($recipe_id) {

        // $ingre = RecipeIngredients::with('ingredient')
        //     ->where('recipe_id', $recipe_id)
        //     ->get();

        $recipe = Recipe::with('ingredients')->findOrFail($recipe_id);
        // dd($recipe[0]->pivot->measure);

        // $measures = RecipeIngredients::pluck('measure');
                     

        return [
            'recipe' => $recipe,
            // 'ingredients'=> $ingre->pluck('ingredient'),
            // 'measure'=>$measures
            
        ];
    }

    public function search(Request $request)
    {
        $search = $request->query('search');

        $recipes = Recipe::where('instruction', 'like', "%{$search}%")
            ->with('ingredients')
            ->get();

        return $recipes;
    }

    public function display()
    {
        $trending = Recipe::limit(10)->get();
        return ($trending);
    }



    public function findByIngredients(Request $request) {
        try {
            $ingredientIdsString = $request->query('ingredients');
            if ($ingredientIdsString) {
                $ingredientIds = explode(',', $ingredientIdsString);
                $recipes = Recipe::whereHas('ingredients', function($q) use($ingredientIds) {
                    $q->whereIn('ingredients.id', $ingredientIds);
                })->get();
                $maxIngredientsCount = 0;
                $recipeWithMostIngredients = null;
                foreach ($recipes as $recipe) {
                    $matchingIngredientsCount = $recipe->ingredients()->whereIn('ingredients.id', $ingredientIds)->count();
                    if ($matchingIngredientsCount > $maxIngredientsCount) {
                        $maxIngredientsCount = $matchingIngredientsCount;
                        $recipeWithMostIngredients = $recipe->id;
                    }
                }
                if ($recipeWithMostIngredients !== null) {
                    $recipe = Recipe::with('ingredients')->find($recipeWithMostIngredients);
                    return response()->json($recipe);
                } else {
                    return response()->json(['message' => 'No recipes found with the given ingredients'], 404);
                }
            } else {
                return response()->json(['message' => 'No ingredient IDs provided'], 400);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

}