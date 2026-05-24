<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Get the authenticated user profile.
     */
    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }
}
