<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $users = User::query()
            ->withCount(['budgets', 'expenses'])
            ->orderBy('name')
            ->get();

        return Inertia::render('Users/Index', [
            'users' => $users,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user): Response
    {
        $user->loadCount(['budgets', 'expenses']);
        
        $user->load([
            'budgets' => function ($query) {
                $query->with('categories')->latest('id');
            },
            'expenses' => function ($query) {
                $query->with(['category', 'store'])->latest('id');
            }
        ]);

        return Inertia::render('Users/Show', [
            'user' => $user,
        ]);
    }
}
