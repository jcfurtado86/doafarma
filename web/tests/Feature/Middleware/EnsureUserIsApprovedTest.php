<?php

declare(strict_types = 1);

use App\Models\User;

describe('EnsureUserIsApproved Middleware', function (): void {
    it('allows approved users to access protected routes', function (): void {
        $user = User::factory()->receptor()->create();

        $this->actingAs($user)
            ->getJson(route('api.v1.drugs.list'))
            ->assertStatus(200);
    });

    it('blocks pending users from accessing protected routes', function (): void {
        $user = User::factory()->receptor()->pending()->create();

        $this->actingAs($user)
            ->getJson(route('api.v1.drugs.list'))
            ->assertStatus(403)
            ->assertJson([
                'message' => 'Seu cadastro está aguardando aprovação.',
                'status'  => 'pending',
            ]);
    });

    it('blocks rejected users from accessing protected routes', function (): void {
        $user = User::factory()->receptor()->rejected()->create();

        $this->actingAs($user)
            ->getJson(route('api.v1.drugs.list'))
            ->assertStatus(403)
            ->assertJson([
                'message' => 'Seu cadastro foi rejeitado. Entre em contato com o suporte.',
                'status'  => 'rejected',
            ]);
    });

    it('returns 401 for unauthenticated requests', function (): void {
        $this->getJson(route('api.v1.drugs.list'))
            ->assertStatus(401);
    });
});
