<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RegistrationResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Response;

class SettingController extends Controller
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
        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('public');
        return  $disk->download($user->avatar);     
    }

    public function remove(Request $request)
    {
        $user = $request->user();

        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }
        $user->avatar = null;
        $user->save();

        return response()->json([
            'status' => false,
            'message' => 'Profile image Removed.',
        ], 200);
    }

    public function changePassword(Request $request)
    {
        $validated = $request->validate([
            'old_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'different:old_password', 
            Password::min(8)
             ->mixedCase()      // Requires at least one uppercase and one lowercase letter
            ->numbers()        // Requires at least one number
            ->symbols() ]       // Requires at least one special character],
        ]);

        $user = $request->user();

        if (! $user || ! Hash::check($validated['old_password'], $user->password)) {
            
            return Response::error(
            'Old password is incorrect.',
            422,
        );
        }

        $user->update([
            'password' => Hash::make($validated['new_password']),
        ]);

        return Response::success(
        'Password changed successful.',[
        'user' => new  RegistrationResource($user)],
            200,
        ); 
    }

}

