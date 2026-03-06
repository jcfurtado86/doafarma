<?php

declare(strict_types = 1);

use App\Models\User;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\postJson;
use function PHPUnit\Framework\assertTrue;

/*
|--------------------------------------------------------------------------
| Receptor (Patient) Registration Tests
|--------------------------------------------------------------------------
|
| Test suite for receptor/patient registration endpoint following TDD approach.
| Tests cover: successful registration, validation errors, and security.
|
*/

describe('Receptor Registration - Success Scenarios', function (): void {
    it('should register a receptor successfully and return 201 with token', function (): void {
        $response = postJson(route('receptor.register'), [
            'name'                  => 'Maria Silva',
            'email'                 => 'maria@example.com',
            'cpf'                   => '529.982.247-25', // CPF válido
            'phone_number'          => '(11) 98765-4321',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
            'device_name'           => 'iPhone 15',
            'terms_accepted'        => true,
        ]);

        $response->assertCreated();
        $response->assertJsonStructure([
            'data' => [
                'user' => ['id', 'name', 'email', 'phone_number', 'role'],
                'token',
            ],
        ]);

        // Verify user was created in database
        // Note: CPF is now encrypted, so we check via cpf_hash
        assertDatabaseHas('users', [
            'name'         => 'Maria Silva',
            'email'        => 'maria@example.com',
            'cpf_hash'     => hash('sha256', '52998224725'), // CPF hash for lookup
            'phone_number' => '11987654321',
            'role'         => 'receptor',
        ]);

        assertDatabaseCount('users', 1);
    });

    it('should return a valid Sanctum token upon successful registration', function (): void {
        $response = postJson(route('receptor.register'), [
            'name'                  => 'João Santos',
            'email'                 => 'joao@example.com',
            'cpf'                   => '529.982.247-25',
            'phone_number'          => '(21) 99876-5432',
            'password'              => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'device_name'           => 'Samsung Galaxy',
            'terms_accepted'        => true,
        ]);

        $response->assertCreated();

        $token = $response->json('data.token');
        expect($token)->not->toBeEmpty();

        // Verify token was created in database
        $user = User::whereEmail('joao@example.com')->first();
        expect($user->tokens)->toHaveCount(1);
    });

    it('should hash the password correctly', function (): void {
        postJson(route('receptor.register'), [
            'name'                  => 'Ana Costa',
            'email'                 => 'ana@example.com',
            'cpf'                   => '529.982.247-25',
            'phone_number'          => '(31) 98765-1234',
            'password'              => 'MyPassword123!',
            'password_confirmation' => 'MyPassword123!',
            'device_name'           => 'Test Device',
            'terms_accepted'        => true,
        ])->assertCreated();

        $user = User::whereEmail('ana@example.com')->first();
        assertTrue(password_verify('MyPassword123!', (string) $user->password));
    });

    it('should NOT return password in response', function (): void {
        $response = postJson(route('receptor.register'), [
            'name'                  => 'Pedro Lima',
            'email'                 => 'pedro@example.com',
            'cpf'                   => '529.982.247-25',
            'phone_number'          => '(41) 98765-4321',
            'password'              => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!',
            'device_name'           => 'Pixel 8',
            'terms_accepted'        => true,
        ]);

        $response->assertCreated();

        $userData = $response->json('data.user');
        expect($userData)->not->toHaveKey('password');
        expect($userData)->not->toHaveKey('remember_token');
    });

    it('should set role as receptor', function (): void {
        $response = postJson(route('receptor.register'), [
            'name'                  => 'Lucas Oliveira',
            'email'                 => 'lucas@example.com',
            'cpf'                   => '529.982.247-25',
            'phone_number'          => '(51) 98765-4321',
            'password'              => 'TestPassword123!',
            'password_confirmation' => 'TestPassword123!',
            'device_name'           => 'Test Device',
            'terms_accepted'        => true,
        ]);

        $response->assertCreated();
        expect($response->json('data.user.role'))->toBe('receptor');

        assertDatabaseHas('users', [
            'email' => 'lucas@example.com',
            'role'  => 'receptor',
        ]);
    });
});

describe('Receptor Registration - Validation Errors', function (): void {
    it('should return 422 when name is missing', function (): void {
        $response = postJson(route('receptor.register'), [
            'email'                 => 'test@example.com',
            'cpf'                   => '529.982.247-25',
            'phone_number'          => '(11) 98765-4321',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
            'device_name'           => 'Test Device',
            'terms_accepted'        => true,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['name']);
    });

    it('should return 422 when email is invalid', function (): void {
        $response = postJson(route('receptor.register'), [
            'name'                  => 'Test User',
            'email'                 => 'invalid-email',
            'cpf'                   => '529.982.247-25',
            'phone_number'          => '(11) 98765-4321',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
            'device_name'           => 'Test Device',
            'terms_accepted'        => true,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['email']);
    });

    it('should return 422 when email is already registered', function (): void {
        User::factory()->create(['email' => 'existing@example.com']);

        $response = postJson(route('receptor.register'), [
            'name'                  => 'New User',
            'email'                 => 'existing@example.com',
            'cpf'                   => '529.982.247-25',
            'phone_number'          => '(11) 98765-4321',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
            'device_name'           => 'Test Device',
            'terms_accepted'        => true,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['email']);
    });

    it('should return 422 when CPF is invalid (wrong check digits)', function (): void {
        $response = postJson(route('receptor.register'), [
            'name'                  => 'Test User',
            'email'                 => 'test@example.com',
            'cpf'                   => '123.456.789-00', // Invalid CPF
            'phone_number'          => '(11) 98765-4321',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
            'device_name'           => 'Test Device',
            'terms_accepted'        => true,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['cpf']);
    });

    it('should return 422 when CPF has all same digits', function (): void {
        $response = postJson(route('receptor.register'), [
            'name'                  => 'Test User',
            'email'                 => 'test@example.com',
            'cpf'                   => '111.111.111-11', // All same digits
            'phone_number'          => '(11) 98765-4321',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
            'device_name'           => 'Test Device',
            'terms_accepted'        => true,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['cpf']);
    });

    it('should return 422 when CPF is already registered', function (): void {
        User::factory()->create(['cpf' => '52998224725']);

        $response = postJson(route('receptor.register'), [
            'name'                  => 'New User',
            'email'                 => 'new@example.com',
            'cpf'                   => '529.982.247-25', // Same CPF
            'phone_number'          => '(11) 98765-4321',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
            'device_name'           => 'Test Device',
            'terms_accepted'        => true,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['cpf']);
    });

    it('should return 422 when phone number is already registered', function (): void {
        User::factory()->create(['phone_number' => '11987654321']);

        $response = postJson(route('receptor.register'), [
            'name'                  => 'New User',
            'email'                 => 'new@example.com',
            'cpf'                   => '529.982.247-25',
            'phone_number'          => '(11) 98765-4321', // Same phone number
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
            'device_name'           => 'Test Device',
            'terms_accepted'        => true,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['phone_number']);
    });

    it('should return 422 when phone number is too short', function (): void {
        $response = postJson(route('receptor.register'), [
            'name'                  => 'Test User',
            'email'                 => 'test@example.com',
            'cpf'                   => '529.982.247-25',
            'phone_number'          => '1234567', // Too short
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
            'device_name'           => 'Test Device',
            'terms_accepted'        => true,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['phone_number']);
    });

    it('should return 422 when password is too short', function (): void {
        $response = postJson(route('receptor.register'), [
            'name'                  => 'Test User',
            'email'                 => 'test@example.com',
            'cpf'                   => '529.982.247-25',
            'phone_number'          => '(11) 98765-4321',
            'password'              => '123', // Too short
            'password_confirmation' => '123',
            'device_name'           => 'Test Device',
            'terms_accepted'        => true,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['password']);
    });

    it('should return 422 when password confirmation does not match', function (): void {
        $response = postJson(route('receptor.register'), [
            'name'                  => 'Test User',
            'email'                 => 'test@example.com',
            'cpf'                   => '529.982.247-25',
            'phone_number'          => '(11) 98765-4321',
            'password'              => 'Password123!',
            'password_confirmation' => 'DifferentPassword!',
            'device_name'           => 'Test Device',
            'terms_accepted'        => true,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['password']);
    });

    it('should return 422 when terms are not accepted', function (): void {
        $response = postJson(route('receptor.register'), [
            'name'                  => 'Test User',
            'email'                 => 'test@example.com',
            'cpf'                   => '529.982.247-25',
            'phone_number'          => '(11) 98765-4321',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
            'device_name'           => 'Test Device',
            'terms_accepted'        => false,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['terms_accepted']);
    });

    it('should return 422 when device name is missing', function (): void {
        $response = postJson(route('receptor.register'), [
            'name'                  => 'Test User',
            'email'                 => 'test@example.com',
            'cpf'                   => '529.982.247-25',
            'phone_number'          => '(11) 98765-4321',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
            'terms_accepted'        => true,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['device_name']);
    });
});

describe('Receptor Registration - Security Tests', function (): void {
    it('should NOT allow role injection via request (Mass Assignment Protection)', function (): void {
        $response = postJson(route('receptor.register'), [
            'name'                  => 'Hacker Test',
            'email'                 => 'hacker@example.com',
            'cpf'                   => '529.982.247-25',
            'phone_number'          => '(11) 98765-4321',
            'password'              => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'device_name'           => 'Test Device',
            'terms_accepted'        => true,
            'role'                  => 'doctor', // Attempt to inject role
        ]);

        $response->assertCreated();

        // Role should ALWAYS be 'receptor', never 'doctor'
        assertDatabaseHas('users', [
            'email' => 'hacker@example.com',
            'role'  => 'receptor', // Must be receptor, not doctor
        ]);

        assertDatabaseMissing('users', [
            'email' => 'hacker@example.com',
            'role'  => 'doctor',
        ]);
    });

    it('should sanitize CPF input (remove mask)', function (): void {
        postJson(route('receptor.register'), [
            'name'                  => 'Security Test',
            'email'                 => 'security@example.com',
            'cpf'                   => '529.982.247-25',
            'phone_number'          => '(11) 98765-4321',
            'password'              => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'device_name'           => 'Test Device',
            'terms_accepted'        => true,
        ])->assertCreated();

        // CPF is encrypted but hash is deterministic, verifying mask was removed
        assertDatabaseHas('users', [
            'cpf_hash' => hash('sha256', '52998224725'), // Hash of CPF without mask
        ]);

        // Also verify via model that decrypted CPF is correct
        $user = User::whereEmail('security@example.com')->first();
        expect($user->cpf)->toBe('52998224725');
    });

    it('should sanitize phone number input (remove mask)', function (): void {
        postJson(route('receptor.register'), [
            'name'                  => 'Phone Test',
            'email'                 => 'phone@example.com',
            'cpf'                   => '529.982.247-25',
            'phone_number'          => '(11) 98765-4321',
            'password'              => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'device_name'           => 'Test Device',
            'terms_accepted'        => true,
        ])->assertCreated();

        assertDatabaseHas('users', [
            'phone_number' => '11987654321', // Stored without mask
        ]);
    });

    it('should reject SQL injection attempts in name field', function (): void {
        $response = postJson(route('receptor.register'), [
            'name'                  => "Robert'); DROP TABLE users;--",
            'email'                 => 'injection@example.com',
            'cpf'                   => '529.982.247-25',
            'phone_number'          => '(11) 98765-4321',
            'password'              => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'device_name'           => 'Test Device',
            'terms_accepted'        => true,
        ]);

        // Should either create with sanitized name or reject
        // Laravel's Eloquent uses parameterized queries, so it should be safe
        if ($response->status() === 201) {
            // If created, verify the injection didn't work
            assertDatabaseHas('users', [
                'email' => 'injection@example.com',
            ]);
            assertDatabaseCount('users', 1);
        }
    });

    it('should handle XSS attempts in name field safely', function (): void {
        $xssPayload = '<script>alert("XSS")</script>';

        $response = postJson(route('receptor.register'), [
            'name'                  => $xssPayload,
            'email'                 => 'xss@example.com',
            'cpf'                   => '529.982.247-25',
            'phone_number'          => '(11) 98765-4321',
            'password'              => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'device_name'           => 'Test Device',
            'terms_accepted'        => true,
        ]);

        $response->assertCreated();

        // Name is stored as-is (XSS prevention is done at output/frontend)
        // The important thing is that it doesn't break the system
        assertDatabaseHas('users', [
            'email' => 'xss@example.com',
        ]);
    });

    it('should reject SQL injection attempts in email field', function (): void {
        $response = postJson(route('receptor.register'), [
            'name'                  => 'Test User',
            'email'                 => "test@example.com'; DROP TABLE users;--",
            'cpf'                   => '529.982.247-25',
            'phone_number'          => '(11) 98765-4321',
            'password'              => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'device_name'           => 'Test Device',
            'terms_accepted'        => true,
        ]);

        // Should reject because it's not a valid email format
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['email']);
        assertDatabaseMissing('users', ['name' => 'Test User']);
    });

    it('should convert email to lowercase', function (): void {
        postJson(route('receptor.register'), [
            'name'                  => 'Uppercase Email',
            'email'                 => 'TEST@EXAMPLE.COM',
            'cpf'                   => '529.982.247-25',
            'phone_number'          => '(11) 98765-4321',
            'password'              => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'device_name'           => 'Test Device',
            'terms_accepted'        => true,
        ])->assertCreated();

        assertDatabaseHas('users', [
            'email' => 'test@example.com', // Stored in lowercase
        ]);
    });

    it('should set terms_accepted_at timestamp', function (): void {
        postJson(route('receptor.register'), [
            'name'                  => 'Terms Test',
            'email'                 => 'terms@example.com',
            'cpf'                   => '529.982.247-25',
            'phone_number'          => '(11) 98765-4321',
            'password'              => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'device_name'           => 'Test Device',
            'terms_accepted'        => true,
        ])->assertCreated();

        $user = User::whereEmail('terms@example.com')->first();
        expect($user->terms_accepted_at)->not->toBeNull();
    });
});

describe('Receptor Registration - Auto Approval', function (): void {
    it('should create receptor with status approved', function (): void {
        postJson(route('receptor.register'), [
            'name'                  => 'Auto Approved',
            'email'                 => 'approved@example.com',
            'cpf'                   => '529.982.247-25',
            'phone_number'          => '(11) 98765-4321',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
            'device_name'           => 'Test Device',
            'terms_accepted'        => true,
        ])->assertCreated();

        $user = User::whereEmail('approved@example.com')->first();
        expect($user->status->value)->toBe('approved');

        assertDatabaseHas('users', [
            'email'  => 'approved@example.com',
            'status' => 'approved',
        ]);
    });

    it('should allow receptor to access protected routes immediately after registration', function (): void {
        $response = postJson(route('receptor.register'), [
            'name'                  => 'Immediate Access',
            'email'                 => 'immediate@example.com',
            'cpf'                   => '529.982.247-25',
            'phone_number'          => '(11) 98765-4321',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
            'device_name'           => 'Test Device',
            'terms_accepted'        => true,
        ]);

        $response->assertCreated();

        $token = $response->json('data.token');

        $protectedResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/drugs/search?query=test');

        $protectedResponse->assertOk();
    });
});

describe('Receptor Registration - CPF Validation Algorithm', function (): void {
    it('should accept valid CPF: 529.982.247-25', function (): void {
        postJson(route('receptor.register'), [
            'name'                  => 'Valid CPF 1',
            'email'                 => 'valid1@example.com',
            'cpf'                   => '529.982.247-25',
            'phone_number'          => '(11) 98765-4321',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
            'device_name'           => 'Test Device',
            'terms_accepted'        => true,
        ])->assertCreated();
    });

    it('should accept valid CPF without mask: 52998224725', function (): void {
        postJson(route('receptor.register'), [
            'name'                  => 'Valid CPF 2',
            'email'                 => 'valid2@example.com',
            'cpf'                   => '52998224725',
            'phone_number'          => '(11) 98765-4321',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
            'device_name'           => 'Test Device',
            'terms_accepted'        => true,
        ])->assertCreated();
    });

    it('should accept another valid CPF: 145.382.206-20', function (): void {
        postJson(route('receptor.register'), [
            'name'                  => 'Valid CPF 3',
            'email'                 => 'valid3@example.com',
            'cpf'                   => '145.382.206-20',
            'phone_number'          => '(11) 98765-4321',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
            'device_name'           => 'Test Device',
            'terms_accepted'        => true,
        ])->assertCreated();
    });

    it('should reject CPF with wrong first check digit', function (): void {
        postJson(route('receptor.register'), [
            'name'                  => 'Invalid CPF',
            'email'                 => 'invalid@example.com',
            'cpf'                   => '529.982.247-15', // Wrong check digit
            'phone_number'          => '(11) 98765-4321',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
            'device_name'           => 'Test Device',
            'terms_accepted'        => true,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['cpf']);
    });

    it('should reject CPF with wrong second check digit', function (): void {
        postJson(route('receptor.register'), [
            'name'                  => 'Invalid CPF',
            'email'                 => 'invalid@example.com',
            'cpf'                   => '529.982.247-26', // Wrong check digit
            'phone_number'          => '(11) 98765-4321',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
            'device_name'           => 'Test Device',
            'terms_accepted'        => true,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['cpf']);
    });
});
