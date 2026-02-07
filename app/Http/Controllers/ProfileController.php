<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Http\JsonResponse;

class ProfileController extends Controller
{
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->update($request->only(['name', 'email']));

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $request->user()->update([
            'password' => $request->input('password'),
        ]);

        return response()->json([
            'success' => true,
        ]);
    }
}
