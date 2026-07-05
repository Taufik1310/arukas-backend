<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt(['email' => $request->email, 'password' => $request->password, 'is_active' => true])) {
            return response()->json(['status' => false, 'message' => 'Email atau password salah, atau akun nonaktif.'], 401);
        }

        $user  = Auth::user();
        $token = $user->createToken('auth_token')->plainTextToken;

        ActivityLog::record('LOGIN', "User {$user->name} berhasil login.");

        return response()->json([
            'status'  => true,
            'message' => 'Login berhasil.',
            'data'    => $this->userResource($user),
            'token'   => $token,
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'                  => 'required|string|max:255',
            'email'                 => 'required|email|unique:users',
            'password'              => 'required|string|min:8|confirmed',
            'phone'                 => 'nullable|string|max:20',
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'phone'    => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'role'     => 'petugas',
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        ActivityLog::create([
            'user_id'     => $user->id,
            'action'      => 'REGISTER',
            'description' => "User baru {$user->name} terdaftar.",
            'new_values'  => ['name' => $user->name, 'email' => $user->email, 'role' => $user->role],
            'ip_address'  => $request->ip(),
            'user_agent'  => $request->userAgent(),
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Registrasi berhasil.',
            'data'    => $this->userResource($user),
            'token'   => $token,
        ], 201);
    }

    public function googleLogin(Request $request): JsonResponse
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            $user = User::updateOrCreate(
                ['email' => $googleUser->getEmail()],
                [
                    'name'      => $googleUser->getName(),
                    'google_id' => $googleUser->getId(),
                    'avatar'    => $googleUser->getAvatar(),
                    'password'  => null,
                    'is_active' => true,
                ]
            );

            $token = $user->createToken('auth_token')->plainTextToken;

            ActivityLog::create([
                'user_id'     => $user->id,
                'action'      => 'GOOGLE_LOGIN',
                'description' => "User {$user->name} login via Google.",
                'ip_address'  => $request->ip(),
                'user_agent'  => $request->userAgent(),
            ]);

            return response()->json([
                'status' => true,
                'data'   => $this->userResource($user),
                'token'  => $token,
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Google login gagal: ' . $e->getMessage()], 400);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        ActivityLog::record('LOGOUT', "User {$request->user()->name} logout.");
        $request->user()->currentAccessToken()->delete();
        return response()->json(['status' => true, 'message' => 'Logout berhasil.']);
    }

    public function user(Request $request): JsonResponse
    {
        return response()->json(['status' => true, 'data' => $this->userResource($request->user())]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'name'  => 'sometimes|string|max:255',
            'phone' => 'sometimes|nullable|string|max:20',
            'avatar' => 'sometimes|image|max:1024',
        ]);

        if ($request->hasFile('avatar')) {
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $user->update($data);
        return response()->json(['status' => true, 'data' => $this->userResource($user)]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'password'         => 'required|string|min:8|confirmed',
        ]);

        if (!Hash::check($request->current_password, $request->user()->password)) {
            return response()->json(['status' => false, 'message' => 'Password lama salah.'], 400);
        }

        $request->user()->update(['password' => Hash::make($request->password)]);
        return response()->json(['status' => true, 'message' => 'Password berhasil diperbarui.']);
    }

    private function userResource(User $user): array
    {
        return [
            'id'         => $user->id,
            'name'       => $user->name,
            'email'      => $user->email,
            'role'       => $user->role,
            'phone'      => $user->phone,
            'avatar'     => $user->avatar ? asset('storage/' . $user->avatar) : null,
            'is_active'  => $user->is_active,
            'created_at' => $user->created_at?->toDateTimeString(),
        ];
    }


    public function googleRedirect(): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    public function googleCallback(Request $request): \Illuminate\Http\RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            $user = User::updateOrCreate(
                ['email' => $googleUser->getEmail()],
                [
                    'name'      => $googleUser->getName(),
                    'google_id' => $googleUser->getId(),
                    'avatar'    => $googleUser->getAvatar(),
                    'password'  => null,
                    'is_active' => true,
                ]
            );

            if (!$user->is_active) {
                $frontendUrl = config('app.frontend_url', 'http://localhost:3000');
                return redirect("{$frontendUrl}/google/callback?error=account_disabled");
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            ActivityLog::create([
                'user_id'     => $user->id,
                'action'      => 'GOOGLE_LOGIN',
                'description' => "User {$user->name} login via Google OAuth.",
                'ip_address'  => $request->ip(),
                'user_agent'  => $request->userAgent(),
            ]);

            $frontendUrl = config('app.frontend_url', 'http://localhost:3000');
            return redirect("{$frontendUrl}/google/callback?token={$token}");
        } catch (\Exception $e) {
            $frontendUrl = config('app.frontend_url', 'http://localhost:3000');
            return redirect("{$frontendUrl}/google/callback?error=oauth_failed");
        }
    }
}
