<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $data = User::orderBy('username');
        if ($request->boolean('withTrashed')) {
            $data->withTrashed();
        }
        return response()->json(UserResource::collection($data->get()));
    }

    public function show(User $user)
    {
        return response()->json(new UserResource($user));
    }

    public function store(Request $request)
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'username' => ['required', 'string', 'max:100', 'unique:users,username'],
            'password' => ['required', 'string', 'min:8', Password::defaults()],
            'is_admin' => ['sometimes', 'boolean'],
            'country'  => ['sometimes', 'nullable', 'string', 'max:100'],
        ], [
            'email.required'    => "L'email è obbligatoria",
            'email.unique'      => "Questa email è già registrata",
            'username.required' => "Lo username è obbligatorio",
            'username.unique'   => "Questo username è già in uso",
            'password.required' => "La password è obbligatoria",
            'password.min'      => "La password deve avere almeno 8 caratteri",
        ]);

        $validated['password'] = bcrypt($validated['password']);

        $user = User::create($validated);

        return response()->json(new UserResource($user), 201);
    }

    public function update(Request $request, User $user)
    {
        $this->authorize('update', $user);

        $validated = $request->validate([
            'username'           => ['sometimes', 'string', 'max:50', Rule::unique('users', 'username')->ignore($user->id)],
            'is_admin'           => ['sometimes', 'boolean'],
            'country'            => ['sometimes', 'nullable', 'string', 'max:100'],
            'preferred_currency' => ['sometimes', 'nullable', 'string', 'max:10'],
            'birth_year'         => ['sometimes', 'nullable', 'integer', 'min:1900', 'max:' . date('Y')],
            'bio'                => ['sometimes', 'nullable', 'string', 'max:1000'],
            'avatar'             => ['sometimes', 'nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ], [
            'username.unique' => "Questo username è già in uso",
        ]);

        if ($request->hasFile('avatar')) {
            // Elimina il vecchio avatar se presente nel disk avatars
            if ($user->avatar_url) {
                $oldPath = ltrim(parse_url($user->avatar_url, PHP_URL_PATH), '/');
                // Il path nel disk avatars non ha il prefisso /storage/avatars/
                $filename = basename($oldPath);
                Storage::disk('avatars')->delete($filename);
            }

            $path = $request->file('avatar')->store('', 'avatars');
            $validated['avatar_url'] = Storage::disk('avatars')->url($path);
        }

        unset($validated['avatar']);
        $user->update($validated);

        return response()->json(new UserResource($user));
    }

    public function destroy(User $user)
    {
        $this->authorize('delete', $user);

        $user->delete();

        return response()->json(['message' => 'Utente eliminato con successo'], 200);
    }

    public function restore($user)
    {
        $this->authorize('restore', User::class);

        $user = User::withTrashed()->findOrFail($user);
        $user->restore();

        return response()->json(new UserResource($user));
    }
}
