<?php

declare(strict_types = 1);

use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

/*
|--------------------------------------------------------------------------
| Rate Limiting Security Tests
|--------------------------------------------------------------------------
|
| Test suite for rate limiting on sensitive endpoints.
| Tests cover: registration, login, refresh, medication offerings, medication requests.
|
*/

beforeEach(function (): void {
    // Clear all rate limiters before each test
    RateLimiter::clear('127.0.0.1');
});

describe('Rate Limiting - Registration Endpoints', function (): void {
    describe('Doctor Registration', function (): void {
        it('should allow up to 10 registration attempts per minute', function (): void {
            // First 10 requests should work (even with validation errors)
            for ($i = 0; $i < 10; $i++) {
                $response = postJson(route('doctor.register'), []);
                expect($response->status())->not->toBe(429);
            }
        });

        it('should return 429 after 10 registration attempts', function (): void {
            // Make 10 requests to hit the limit
            for ($i = 0; $i < 10; $i++) {
                postJson(route('doctor.register'), []);
            }

            // 11th request should be rate limited
            $response = postJson(route('doctor.register'), []);

            $response->assertStatus(429);
        });

        it('should include Retry-After header when rate limited', function (): void {
            // Exhaust rate limit
            for ($i = 0; $i < 10; $i++) {
                postJson(route('doctor.register'), []);
            }

            $response = postJson(route('doctor.register'), []);

            $response->assertStatus(429);
            $response->assertHeader('Retry-After');
        });

        it('should include X-RateLimit headers when rate limited', function (): void {
            // Exhaust rate limit
            for ($i = 0; $i < 10; $i++) {
                postJson(route('doctor.register'), []);
            }

            $response = postJson(route('doctor.register'), []);

            $response->assertStatus(429);
            $response->assertHeader('X-RateLimit-Limit');
            $response->assertHeader('X-RateLimit-Remaining');
        });

        it('should return informative JSON error message when rate limited', function (): void {
            // Exhaust rate limit
            for ($i = 0; $i < 10; $i++) {
                postJson(route('doctor.register'), []);
            }

            $response = postJson(route('doctor.register'), []);

            $response->assertStatus(429);
            $response->assertJson([
                'message' => 'Too many requests. Please try again later.',
            ]);
        });
    });

    describe('Receptor Registration', function (): void {
        it('should allow up to 10 registration attempts per minute', function (): void {
            // First 10 requests should work (even with validation errors)
            for ($i = 0; $i < 10; $i++) {
                $response = postJson(route('receptor.register'), []);
                expect($response->status())->not->toBe(429);
            }
        });

        it('should return 429 after 10 registration attempts', function (): void {
            // Make 10 requests to hit the limit
            for ($i = 0; $i < 10; $i++) {
                postJson(route('receptor.register'), []);
            }

            // 11th request should be rate limited
            $response = postJson(route('receptor.register'), []);

            $response->assertStatus(429);
        });

        it('should include Retry-After header when rate limited', function (): void {
            // Exhaust rate limit
            for ($i = 0; $i < 10; $i++) {
                postJson(route('receptor.register'), []);
            }

            $response = postJson(route('receptor.register'), []);

            $response->assertStatus(429);
            $response->assertHeader('Retry-After');
        });
    });
});

describe('Rate Limiting - Medication Offerings (Authenticated)', function (): void {
    it('should allow up to 60 creation attempts per minute', function (): void {
        $user = User::factory()->doctor()->approved()->create();

        // First 60 requests should work (even with validation errors)
        for ($i = 0; $i < 60; $i++) {
            $response = actingAs($user)->postJson(route('api.v1.medication-offerings.post'), []);
            expect($response->status())->not->toBe(429);
        }
    });

    it('should return 429 after 60 creation attempts', function (): void {
        $user = User::factory()->doctor()->approved()->create();

        // Make 60 requests to hit the limit
        for ($i = 0; $i < 60; $i++) {
            actingAs($user)->postJson(route('api.v1.medication-offerings.post'), []);
        }

        // 61st request should be rate limited
        $response = actingAs($user)->postJson(route('api.v1.medication-offerings.post'), []);

        $response->assertStatus(429);
    });

    it('should include Retry-After header when rate limited', function (): void {
        $user = User::factory()->doctor()->approved()->create();

        // Exhaust rate limit
        for ($i = 0; $i < 60; $i++) {
            actingAs($user)->postJson(route('api.v1.medication-offerings.post'), []);
        }

        $response = actingAs($user)->postJson(route('api.v1.medication-offerings.post'), []);

        $response->assertStatus(429);
        $response->assertHeader('Retry-After');
    });

    it('should return informative JSON error message when rate limited', function (): void {
        $user = User::factory()->doctor()->approved()->create();

        // Exhaust rate limit
        for ($i = 0; $i < 60; $i++) {
            actingAs($user)->postJson(route('api.v1.medication-offerings.post'), []);
        }

        $response = actingAs($user)->postJson(route('api.v1.medication-offerings.post'), []);

        $response->assertStatus(429);
        $response->assertJson([
            'message' => 'Too many requests. Please try again later.',
        ]);
    });

    it('should NOT rate limit GET requests', function (): void {
        $user = User::factory()->doctor()->approved()->create();

        // Make 100 GET requests - should all work
        for ($i = 0; $i < 100; $i++) {
            $response = actingAs($user)->getJson(route('api.v1.medication-offerings.list'));
            expect($response->status())->not->toBe(429);
        }
    });

    it('should have separate rate limits per user', function (): void {
        $user1 = User::factory()->doctor()->approved()->create();
        $user2 = User::factory()->doctor()->approved()->create();

        // User 1 exhausts their limit
        for ($i = 0; $i < 60; $i++) {
            actingAs($user1)->postJson(route('api.v1.medication-offerings.post'), []);
        }

        // User 1 should be rate limited
        $response1 = actingAs($user1)->postJson(route('api.v1.medication-offerings.post'), []);
        $response1->assertStatus(429);

        // User 2 should NOT be rate limited
        $response2 = actingAs($user2)->postJson(route('api.v1.medication-offerings.post'), []);
        expect($response2->status())->not->toBe(429);
    });
});

describe('Rate Limiting - Medication Requests (Authenticated)', function (): void {
    it('should allow up to 60 creation attempts per minute', function (): void {
        $user = User::factory()->receptor()->approved()->create();

        // First 60 requests should work (even with validation errors)
        for ($i = 0; $i < 60; $i++) {
            $response = actingAs($user)->postJson(route('api.v1.medication-requests.store'), []);
            expect($response->status())->not->toBe(429);
        }
    });

    it('should return 429 after 60 creation attempts', function (): void {
        $user = User::factory()->receptor()->approved()->create();

        // Make 60 requests to hit the limit
        for ($i = 0; $i < 60; $i++) {
            actingAs($user)->postJson(route('api.v1.medication-requests.store'), []);
        }

        // 61st request should be rate limited
        $response = actingAs($user)->postJson(route('api.v1.medication-requests.store'), []);

        $response->assertStatus(429);
    });

    it('should include Retry-After header when rate limited', function (): void {
        $user = User::factory()->receptor()->approved()->create();

        // Exhaust rate limit
        for ($i = 0; $i < 60; $i++) {
            actingAs($user)->postJson(route('api.v1.medication-requests.store'), []);
        }

        $response = actingAs($user)->postJson(route('api.v1.medication-requests.store'), []);

        $response->assertStatus(429);
        $response->assertHeader('Retry-After');
    });

    it('should NOT rate limit GET requests', function (): void {
        $user = User::factory()->receptor()->approved()->create();

        // Make 100 GET requests - should all work
        for ($i = 0; $i < 100; $i++) {
            $response = actingAs($user)->getJson(route('api.v1.medication-requests.list'));
            expect($response->status())->not->toBe(429);
        }
    });
});

describe('Rate Limiting - Login Endpoint', function (): void {
    it('should allow up to 5 login attempts per minute', function (): void {
        User::factory()->create([
            'email'    => 'login-limit@example.com',
            'password' => bcrypt('password'),
        ]);

        for ($i = 0; $i < 5; $i++) {
            $response = postJson(route('api.v1.auth.login'), [
                'email'    => 'login-limit@example.com',
                'password' => 'wrong-password',
            ]);
            expect($response->status())->not->toBe(429);
        }
    });

    it('should return 429 after 5 login attempts', function (): void {
        User::factory()->create([
            'email'    => 'login-blocked@example.com',
            'password' => bcrypt('password'),
        ]);

        for ($i = 0; $i < 5; $i++) {
            postJson(route('api.v1.auth.login'), [
                'email'    => 'login-blocked@example.com',
                'password' => 'wrong-password',
            ]);
        }

        $response = postJson(route('api.v1.auth.login'), [
            'email'    => 'login-blocked@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(429);
    });

    it('should include Retry-After and X-RateLimit-Limit headers when rate limited', function (): void {
        User::factory()->create([
            'email'    => 'login-headers@example.com',
            'password' => bcrypt('password'),
        ]);

        for ($i = 0; $i < 5; $i++) {
            postJson(route('api.v1.auth.login'), [
                'email'    => 'login-headers@example.com',
                'password' => 'wrong-password',
            ]);
        }

        $response = postJson(route('api.v1.auth.login'), [
            'email'    => 'login-headers@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(429);
        $response->assertHeader('Retry-After');
        $response->assertHeader('X-RateLimit-Limit');
    });

    it('should return login-specific JSON error message when rate limited', function (): void {
        User::factory()->create([
            'email'    => 'login-msg@example.com',
            'password' => bcrypt('password'),
        ]);

        for ($i = 0; $i < 5; $i++) {
            postJson(route('api.v1.auth.login'), [
                'email'    => 'login-msg@example.com',
                'password' => 'wrong-password',
            ]);
        }

        $response = postJson(route('api.v1.auth.login'), [
            'email'    => 'login-msg@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(429);
        $response->assertJson([
            'message' => 'Too many login attempts. Please try again later.',
            'errors'  => [
                'email' => ['Too many login attempts. Please try again later.'],
            ],
        ]);
    });

    it('should have independent limits per IP', function (): void {
        User::factory()->create([
            'email'    => 'login-ip@example.com',
            'password' => bcrypt('password'),
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->call('POST', route('api.v1.auth.login'), [
                'email'    => 'login-ip@example.com',
                'password' => 'wrong-password',
            ], [], [], ['REMOTE_ADDR' => '10.0.0.1', 'CONTENT_TYPE' => 'application/json']);
        }

        $blockedResponse = $this->call('POST', route('api.v1.auth.login'), [
            'email'    => 'login-ip@example.com',
            'password' => 'wrong-password',
        ], [], [], ['REMOTE_ADDR' => '10.0.0.1', 'CONTENT_TYPE' => 'application/json']);

        expect($blockedResponse->status())->toBe(429);

        $allowedResponse = $this->call('POST', route('api.v1.auth.login'), [
            'email'    => 'login-ip@example.com',
            'password' => 'wrong-password',
        ], [], [], ['REMOTE_ADDR' => '10.0.0.2', 'CONTENT_TYPE' => 'application/json']);

        expect($allowedResponse->status())->not->toBe(429);
    });

    it('should have independent limits per email from the same IP', function (): void {
        User::factory()->create([
            'email'    => 'user-a@example.com',
            'password' => bcrypt('password'),
        ]);
        User::factory()->create([
            'email'    => 'user-b@example.com',
            'password' => bcrypt('password'),
        ]);

        for ($i = 0; $i < 5; $i++) {
            postJson(route('api.v1.auth.login'), [
                'email'    => 'user-a@example.com',
                'password' => 'wrong-password',
            ]);
        }

        $blockedResponse = postJson(route('api.v1.auth.login'), [
            'email'    => 'user-a@example.com',
            'password' => 'wrong-password',
        ]);

        $blockedResponse->assertStatus(429);

        $allowedResponse = postJson(route('api.v1.auth.login'), [
            'email'    => 'user-b@example.com',
            'password' => 'wrong-password',
        ]);

        expect($allowedResponse->status())->not->toBe(429);
    });
});

describe('Rate Limiting - Password Reset Endpoints', function (): void {
    describe('Forgot Password', function (): void {
        it('should allow up to 3 requests per minute', function (): void {
            for ($i = 0; $i < 3; $i++) {
                $response = postJson(route('api.v1.auth.forgot-password'), [
                    'email' => "user{$i}@example.com",
                ]);
                expect($response->status())->not->toBe(429);
            }
        });

        it('should return 429 after 3 requests', function (): void {
            for ($i = 0; $i < 3; $i++) {
                postJson(route('api.v1.auth.forgot-password'), [
                    'email' => "user{$i}@example.com",
                ]);
            }

            $response = postJson(route('api.v1.auth.forgot-password'), [
                'email' => 'another@example.com',
            ]);

            $response->assertStatus(429);
        });

        it('should include Retry-After header when rate limited', function (): void {
            for ($i = 0; $i < 3; $i++) {
                postJson(route('api.v1.auth.forgot-password'), [
                    'email' => "user{$i}@example.com",
                ]);
            }

            $response = postJson(route('api.v1.auth.forgot-password'), [
                'email' => 'another@example.com',
            ]);

            $response->assertStatus(429);
            $response->assertHeader('Retry-After');
        });

        it('should return password-reset-specific JSON error message when rate limited', function (): void {
            for ($i = 0; $i < 3; $i++) {
                postJson(route('api.v1.auth.forgot-password'), [
                    'email' => "user{$i}@example.com",
                ]);
            }

            $response = postJson(route('api.v1.auth.forgot-password'), [
                'email' => 'another@example.com',
            ]);

            $response->assertStatus(429);
            $response->assertJson([
                'message' => 'Too many password reset requests. Please try again later.',
                'errors'  => [
                    'email' => ['Too many password reset requests. Please try again later.'],
                ],
            ]);
        });
    });

    describe('Reset Password', function (): void {
        it('should allow up to 3 requests per minute', function (): void {
            for ($i = 0; $i < 3; $i++) {
                $response = postJson(route('api.v1.auth.reset-password'), [
                    'token'                 => 'fake-token',
                    'email'                 => "user{$i}@example.com",
                    'password'              => 'new-password-123',
                    'password_confirmation' => 'new-password-123',
                ]);
                expect($response->status())->not->toBe(429);
            }
        });

        it('should return 429 after 3 requests', function (): void {
            for ($i = 0; $i < 3; $i++) {
                postJson(route('api.v1.auth.reset-password'), [
                    'token'                 => 'fake-token',
                    'email'                 => "user{$i}@example.com",
                    'password'              => 'new-password-123',
                    'password_confirmation' => 'new-password-123',
                ]);
            }

            $response = postJson(route('api.v1.auth.reset-password'), [
                'token'                 => 'fake-token',
                'email'                 => 'another@example.com',
                'password'              => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ]);

            $response->assertStatus(429);
        });

        it('should include Retry-After header when rate limited', function (): void {
            for ($i = 0; $i < 3; $i++) {
                postJson(route('api.v1.auth.reset-password'), [
                    'token'                 => 'fake-token',
                    'email'                 => "user{$i}@example.com",
                    'password'              => 'new-password-123',
                    'password_confirmation' => 'new-password-123',
                ]);
            }

            $response = postJson(route('api.v1.auth.reset-password'), [
                'token'                 => 'fake-token',
                'email'                 => 'another@example.com',
                'password'              => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ]);

            $response->assertStatus(429);
            $response->assertHeader('Retry-After');
        });

        it('should return password-reset-specific JSON error message when rate limited', function (): void {
            for ($i = 0; $i < 3; $i++) {
                postJson(route('api.v1.auth.reset-password'), [
                    'token'                 => 'fake-token',
                    'email'                 => "user{$i}@example.com",
                    'password'              => 'new-password-123',
                    'password_confirmation' => 'new-password-123',
                ]);
            }

            $response = postJson(route('api.v1.auth.reset-password'), [
                'token'                 => 'fake-token',
                'email'                 => 'another@example.com',
                'password'              => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ]);

            $response->assertStatus(429);
            $response->assertJson([
                'message' => 'Too many password reset requests. Please try again later.',
                'errors'  => [
                    'email' => ['Too many password reset requests. Please try again later.'],
                ],
            ]);
        });
    });
});

describe('Rate Limiting - Refresh Endpoint', function (): void {
    it('should allow up to 10 refresh attempts per minute', function (): void {
        $user = User::factory()->create();

        $loginResponse = postJson(route('api.v1.auth.login'), [
            'email'    => $user->email,
            'password' => 'password',
        ]);

        $refreshToken = $loginResponse->json('data.refresh_token');

        for ($i = 0; $i < 10; $i++) {
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $refreshToken,
            ])->postJson(route('api.v1.auth.refresh'));
            expect($response->status())->not->toBe(429);
        }
    });

    it('should return 429 after 10 refresh attempts', function (): void {
        $user = User::factory()->create();

        $loginResponse = postJson(route('api.v1.auth.login'), [
            'email'    => $user->email,
            'password' => 'password',
        ]);

        $refreshToken = $loginResponse->json('data.refresh_token');

        for ($i = 0; $i < 10; $i++) {
            $this->withHeaders([
                'Authorization' => 'Bearer ' . $refreshToken,
            ])->postJson(route('api.v1.auth.refresh'));
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $refreshToken,
        ])->postJson(route('api.v1.auth.refresh'));

        $response->assertStatus(429);
    });

    it('should include correct headers when rate limited', function (): void {
        $user = User::factory()->create();

        $loginResponse = postJson(route('api.v1.auth.login'), [
            'email'    => $user->email,
            'password' => 'password',
        ]);

        $refreshToken = $loginResponse->json('data.refresh_token');

        for ($i = 0; $i < 10; $i++) {
            $this->withHeaders([
                'Authorization' => 'Bearer ' . $refreshToken,
            ])->postJson(route('api.v1.auth.refresh'));
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $refreshToken,
        ])->postJson(route('api.v1.auth.refresh'));

        $response->assertStatus(429);
        $response->assertHeader('Retry-After');
        $response->assertHeader('X-RateLimit-Limit');
    });
});
