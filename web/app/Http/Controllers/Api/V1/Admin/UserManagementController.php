<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\Admin\UpdateUserStatusAction;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserManagementController extends Controller
{
    /**
     * List users pending approval.
     */
    public function pending(Request $request): AnonymousResourceCollection
    {
        $users = User::where('status', UserStatus::Pending)
            ->where('role', '!=', UserRole::Admin)
            ->with(['doctor', 'addresses'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return UserResource::collection($users);
    }

    /**
     * List all users (with optional filters).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = User::where('role', '!=', UserRole::Admin)
            ->with(['doctor', 'addresses']);

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('role')) {
            $query->where('role', $request->input('role'));
        }

        if ($request->has('search')) {
            $search = mb_strtolower((string) $request->input('search'));
            $query->where(function ($q) use ($search): void {
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(email) LIKE ?', ["%{$search}%"])
                    ->orWhere('cpf', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(15);

        return UserResource::collection($users);
    }

    /**
     * Get a specific user details.
     */
    public function show(User $user): UserResource
    {
        $user->load(['doctor', 'addresses', 'statusChangedByAdmin']);

        return new UserResource($user);
    }

    /**
     * Approve a user.
     */
    public function approve(User $user, UpdateUserStatusAction $action): JsonResponse
    {
        if ($user->status === UserStatus::Approved) {
            return response()->json([
                'message' => 'Usuário já está aprovado.',
            ], 422);
        }

        $admin = request()->user();
        $action->execute($user, UserStatus::Approved, $admin);

        return response()->json([
            'message' => 'Usuário aprovado com sucesso.',
            'user'    => new UserResource($user->fresh(['doctor', 'addresses'])),
        ]);
    }

    /**
     * Reject a user.
     */
    public function reject(User $user, UpdateUserStatusAction $action): JsonResponse
    {
        if ($user->status === UserStatus::Rejected) {
            return response()->json([
                'message' => 'Usuário já está rejeitado.',
            ], 422);
        }

        $admin = request()->user();
        $action->execute($user, UserStatus::Rejected, $admin);

        return response()->json([
            'message' => 'Usuário rejeitado.',
            'user'    => new UserResource($user->fresh(['doctor', 'addresses'])),
        ]);
    }

    /**
     * Get statistics about users.
     */
    public function stats(): JsonResponse
    {
        $stats = [
            'pending'   => User::where('status', UserStatus::Pending)->where('role', '!=', UserRole::Admin)->count(),
            'approved'  => User::where('status', UserStatus::Approved)->where('role', '!=', UserRole::Admin)->count(),
            'rejected'  => User::where('status', UserStatus::Rejected)->where('role', '!=', UserRole::Admin)->count(),
            'doctors'   => User::where('role', UserRole::Doctor)->count(),
            'receptors' => User::where('role', UserRole::Receptor)->count(),
        ];

        return response()->json($stats);
    }
}
