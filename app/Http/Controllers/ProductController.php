<?php

namespace App\Http\Controllers;

use App\Models\Import;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $query = Product::query()->latest();

        if ($search = $request->get('q')) {
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('external_id', 'like', "%{$search}%");
            });
        }

        $products = $query->paginate(50);

        if ($request->wantsJson()) {
            return response()->json($products);
        }

        return view('products.index', compact('products'));
    }

    public function byImport(Import $import, Request $request): View|JsonResponse
    {
        $query = $import->products()->latest();

        if ($search = $request->get('q')) {
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('external_id', 'like', "%{$search}%");
            });
        }

        $products = $query->paginate(50);

        if ($request->wantsJson()) {
            return response()->json($products);
        }

        return view('products.by_import', compact('import', 'products'));
    }
}


