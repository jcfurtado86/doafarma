<?php

declare(strict_types = 1);

use App\Enums\UserStatus;
use App\Models\User;

beforeEach(function (): void {
    $this->admin    = User::factory()->admin()->create();
    $this->doctor   = User::factory()->doctor()->create();
    $this->receptor = User::factory()->receptor()->create();
});

describe('Admin Access Control', function (): void {
    it('returns 401 for unauthenticated requests', function (): void {
        $this->getJson(route('api.v1.admin.users.pending'))
            ->assertStatus(401);
    });

    it('returns 403 for non-admin users', function (): void {
        $this->actingAs($this->doctor)
            ->getJson(route('api.v1.admin.users.pending'))
            ->assertStatus(403)
            ->assertJson(['message' => 'Acesso não autorizado. Apenas administradores podem acessar este recurso.']);
    });

    it('allows admin to access admin routes', function (): void {
        $this->actingAs($this->admin)
            ->getJson(route('api.v1.admin.users.pending'))
            ->assertStatus(200);
    });
});

describe('Pending Users', function (): void {
    it('lists users pending approval', function (): void {
        $pendingDoctor   = User::factory()->doctor()->pending()->create();
        $pendingReceptor = User::factory()->receptor()->pending()->create();

        $response = $this->actingAs($this->admin)
            ->getJson(route('api.v1.admin.users.pending'))
            ->assertStatus(200);

        expect($response->json('data'))->toHaveCount(2);
    });

    it('does not list approved users in pending endpoint', function (): void {
        User::factory()->receptor()->create(); // approved by default

        $response = $this->actingAs($this->admin)
            ->getJson(route('api.v1.admin.users.pending'))
            ->assertStatus(200);

        expect($response->json('data'))->toHaveCount(0);
    });

    it('does not list admin users in pending endpoint', function (): void {
        User::factory()->admin()->pending()->create();

        $response = $this->actingAs($this->admin)
            ->getJson(route('api.v1.admin.users.pending'))
            ->assertStatus(200);

        expect($response->json('data'))->toHaveCount(0);
    });
});

describe('User List', function (): void {
    it('lists all non-admin users', function (): void {
        $response = $this->actingAs($this->admin)
            ->getJson(route('api.v1.admin.users.index'))
            ->assertStatus(200);

        // Should include doctor and receptor, not admin
        expect($response->json('data'))->toHaveCount(2);
    });

    it('filters users by status', function (): void {
        User::factory()->receptor()->pending()->create();

        $response = $this->actingAs($this->admin)
            ->getJson(route('api.v1.admin.users.index', ['status' => 'pending']))
            ->assertStatus(200);

        expect($response->json('data'))->toHaveCount(1);
    });

    it('filters users by role', function (): void {
        $response = $this->actingAs($this->admin)
            ->getJson(route('api.v1.admin.users.index', ['role' => 'doctor']))
            ->assertStatus(200);

        expect($response->json('data'))->toHaveCount(1);
        expect($response->json('data.0.role'))->toBe('doctor');
    });

    it('searches users by name', function (): void {
        User::factory()->receptor()->create(['name' => 'João Silva']);

        $response = $this->actingAs($this->admin)
            ->getJson(route('api.v1.admin.users.index', ['search' => 'João']))
            ->assertStatus(200);

        expect($response->json('data'))->toHaveCount(1);
        expect($response->json('data.0.name'))->toBe('João Silva');
    });

    it('searches users by email', function (): void {
        User::factory()->receptor()->create(['email' => 'unique@example.com']);

        $response = $this->actingAs($this->admin)
            ->getJson(route('api.v1.admin.users.index', ['search' => 'unique@example']))
            ->assertStatus(200);

        expect($response->json('data'))->toHaveCount(1);
    });
});

describe('User Details', function (): void {
    it('shows user details with relationships', function (): void {
        $response = $this->actingAs($this->admin)
            ->getJson(route('api.v1.admin.users.show', $this->doctor))
            ->assertStatus(200);

        expect($response->json('data.id'))->toBe($this->doctor->id);
        expect($response->json('data.doctor'))->not->toBeNull();
    });
});

describe('Approve User', function (): void {
    it('approves a pending user', function (): void {
        $pendingUser = User::factory()->receptor()->pending()->create();

        $response = $this->actingAs($this->admin)
            ->patchJson(route('api.v1.admin.users.approve', $pendingUser))
            ->assertStatus(200)
            ->assertJson(['message' => 'Usuário aprovado com sucesso.']);

        $pendingUser->refresh();
        expect($pendingUser->status)->toBe(UserStatus::Approved);
        expect($pendingUser->status_changed_by)->toBe($this->admin->id);
        expect($pendingUser->status_changed_at)->not->toBeNull();
    });

    it('approves a rejected user', function (): void {
        $rejectedUser = User::factory()->receptor()->rejected()->create();

        $this->actingAs($this->admin)
            ->patchJson(route('api.v1.admin.users.approve', $rejectedUser))
            ->assertStatus(200);

        $rejectedUser->refresh();
        expect($rejectedUser->status)->toBe(UserStatus::Approved);
    });

    it('returns error when approving already approved user', function (): void {
        $this->actingAs($this->admin)
            ->patchJson(route('api.v1.admin.users.approve', $this->receptor))
            ->assertStatus(422)
            ->assertJson(['message' => 'Usuário já está aprovado.']);
    });
});

describe('Reject User', function (): void {
    it('rejects a pending user', function (): void {
        $pendingUser = User::factory()->receptor()->pending()->create();

        $response = $this->actingAs($this->admin)
            ->patchJson(route('api.v1.admin.users.reject', $pendingUser))
            ->assertStatus(200)
            ->assertJson(['message' => 'Usuário rejeitado.']);

        $pendingUser->refresh();
        expect($pendingUser->status)->toBe(UserStatus::Rejected);
        expect($pendingUser->status_changed_by)->toBe($this->admin->id);
    });

    it('rejects an approved user', function (): void {
        $this->actingAs($this->admin)
            ->patchJson(route('api.v1.admin.users.reject', $this->receptor))
            ->assertStatus(200);

        $this->receptor->refresh();
        expect($this->receptor->status)->toBe(UserStatus::Rejected);
    });

    it('returns error when rejecting already rejected user', function (): void {
        $rejectedUser = User::factory()->receptor()->rejected()->create();

        $this->actingAs($this->admin)
            ->patchJson(route('api.v1.admin.users.reject', $rejectedUser))
            ->assertStatus(422)
            ->assertJson(['message' => 'Usuário já está rejeitado.']);
    });
});

describe('User Stats', function (): void {
    it('returns user statistics', function (): void {
        User::factory()->receptor()->pending()->count(3)->create();
        User::factory()->doctor()->pending()->count(2)->create();

        $response = $this->actingAs($this->admin)
            ->getJson(route('api.v1.admin.users.stats'))
            ->assertStatus(200);

        expect($response->json('pending'))->toBe(5);
        expect($response->json('approved'))->toBe(2); // doctor and receptor from beforeEach
        expect($response->json('rejected'))->toBe(0);
        expect($response->json('doctors'))->toBe(3); // 1 from beforeEach + 2 pending
        expect($response->json('receptors'))->toBe(4); // 1 from beforeEach + 3 pending
    });
});
