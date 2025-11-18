<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Api\RegisterController;
use App\Http\Controllers\Api\UserController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_with_valid_data(): void
    {
        $payload = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ];

        $request = Request::create('/api/register', 'POST', $payload);
        $controller = app(RegisterController::class);

        $response = $controller->store($request);

        $this->assertEquals(201, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertEquals('Registration successful', $data['message'] ?? null);
        $this->assertEquals('test@example.com', $data['user']['email'] ?? null);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
        ]);
    }

    public function test_registration_requires_unique_email(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $payload = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ];

        $request = Request::create('/api/register', 'POST', $payload);
        $controller = app(RegisterController::class);

        $this->expectException(ValidationException::class);
        $controller->store($request);
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $request = Request::create('/api/login', 'POST', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $session = app('session')->driver();
        $session->start();
        $request->setLaravelSession($session);

        $controller = app(LoginController::class);
        $response = $controller->login($request);

        $this->assertEquals(200, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertEquals('Login successful', $data['message'] ?? null);
        $this->assertEquals($user->email, $data['user']['email'] ?? null);
        $this->assertTrue(Auth::check());
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $request = Request::create('/api/login', 'POST', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $controller = app(LoginController::class);
        $response = $controller->login($request);

        $this->assertEquals(401, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertEquals('Invalid email or password', $data['message'] ?? null);
        $this->assertFalse(Auth::check());
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();

        Auth::login($user);

        $request = Request::create('/api/logout', 'POST');
        $controller = app(LoginController::class);
        $response = $controller->logout($request);

        $this->assertEquals(200, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertEquals('Logout successful', $data['message'] ?? null);
        $this->assertFalse(Auth::check());
    }

    public function test_can_list_users_via_get_users_endpoint(): void
    {
        $users = User::factory()->count(3)->create();

        $controller = app(UserController::class);
        $response = $controller->index();

        $this->assertEquals(200, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertIsArray($data);
        $this->assertCount(3, $data);

        foreach ($users as $user) {
            $this->assertTrue(
                collect($data)->contains(fn (array $item) => $item['email'] === $user->email)
            );
        }
    }
}
