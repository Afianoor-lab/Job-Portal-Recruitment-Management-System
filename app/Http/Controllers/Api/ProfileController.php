<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function updateAvatar(Request $request)
    {
        // Support both 'avatar' and 'image' field names
        $field = $request->hasFile('avatar') ? 'avatar' : ($request->hasFile('image') ? 'image' : null);

        if (!$field) {
            return response()->json([
                'status' => false,
                'message' => 'The avatar (or image) field is required.',
            ], 422);
        }

        $request->validate([
            $field => 'required|image|mimes:jpg,jpeg,png,webp|max:2048', // Max 2MB
        ]);

        $user = $request->user();

        // Delete old avatar if it exists
        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        // Store new image in storage/app/public/avatars
        $path = $request->file($field)->store('avatars', 'public');

        $user->avatar = $path;
        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'Profile image updated successfully.',
            'data' => [
                'avatar_path' => $path,
                'avatar_url' => asset('storage/' . $path),
            ],
        ], 200);
    }


    public function show(Request $request)
    {
        $user = $request->user();

        if (!$user->avatar ) {
        return response()->json([
            'status' => false,
            'message' => 'No Profile image found.',
        ], 404);
    }
   
        abort_unless(Storage::disk('public')->exists($user->avatar), 404);
        return Storage::disk('public')->download($user->avatar);
      
    }
}