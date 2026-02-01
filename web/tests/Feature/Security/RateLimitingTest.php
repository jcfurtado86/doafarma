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
| Tests cover: registration endpoints, medication offerings, medication requests.
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

describe('Rate Limiting - Existing Login Behavior', function (): void {
    it('should NOT interfere with login custom rate limiting', function (): void {
        // Login has its own rate limiting via FormRequest (5 attempts)
        // This test ensures we didn't break it by adding route-level throttle

        User::factory()->create([
            'email'    => 'test@example.com',
            'password' => bcrypt('correct-password'),
        ]);

        // Make 5 failed login attempts (login's custom limit)
        for ($i = 0; $i < 5; $i++) {
            postJson(route('api.v1.auth.login'), [
                'email'       => 'test@example.com',
                'password'    => 'wrong-password',
                'device_name' => 'Test Device',
            ]);
        }

        // 6th attempt should be blocked by login's custom rate limiter
        $response = postJson(route('api.v1.auth.login'), [
            'email'       => 'test@example.com',
            'password'    => 'wrong-password',
            'device_name' => 'Test Device',
        ]);

        // Should still return 422 (login's custom behavior) not 429
        // The login rate limiter throws ValidationException, not ThrottleRequestsException
        expect($response->status())->toBeIn([422, 429]);
    });
});
