<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $users = User::query()
            ->when($request->search, fn($q, $s) =>
            $q->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%"))
            ->when($request->role, fn($q, $r) => $q->where('role', $r))
            ->when($request->filled('is_active'), fn($q) => $q->where('is_active', $request->boolean('is_active')))
            ->withCount(['saleTransactions'])
            ->latest()
            ->paginate($request->per_page ?? 10);

        return response()->json([
            'status' => true,
            'data'   => $users->items(),
            'meta'   => ['total' => $users->total(), 'last_page' => $users->lastPage(), 'current_page' => $users->currentPage()],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role'     => 'required|in:admin,petugas',
            'phone'    => 'nullable|string|max:20',
        ]);

        $user = User::create([...$data, 'password' => Hash::make($data['password'])]);

        ActivityLog::record('CREATE', "User '{$user->name}' (role: {$user->role}) ditambahkan oleh admin.", 'User', $user->id);

        return response()->json(['status' => true, 'message' => 'User berhasil ditambahkan.', 'data' => $user], 201);
    }

    public function show(User $user): JsonResponse
    {
        return response()->json(['status' => true, 'data' => $user->loadCount('saleTransactions')]);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'name'      => 'sometimes|string|max:255',
            'role'      => 'sometimes|in:admin,petugas',
            'phone'     => 'nullable|string|max:20',
            'is_active' => 'sometimes|boolean',
            'password'  => 'nullable|string|min:8|confirmed',
        ]);

        $oldValues = $user->toArray();

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        ActivityLog::record('UPDATE', "User '{$user->name}' diperbarui.", 'User', $user->id, $oldValues, $user->fresh()->toArray());

        return response()->json(['status' => true, 'message' => 'User berhasil diperbarui.', 'data' => $user]);
    }

    public function destroy(User $user): JsonResponse
    {
        if ($user->id === auth()->id()) {
            return response()->json(['status' => false, 'message' => 'Tidak dapat menghapus akun sendiri.'], 422);
        }

        ActivityLog::record('DELETE', "User '{$user->name}' dihapus.", 'User', $user->id, $user->toArray());
        $user->delete();

        return response()->json(['status' => true, 'message' => 'User berhasil dihapus.']);
    }
}
