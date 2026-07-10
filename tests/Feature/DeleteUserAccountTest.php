<?php

namespace Tests\Feature;

use App\Contracts\DeletesFirebaseUsers;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DeleteUserAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_user_can_delete_account_with_password(): void
    {
        $this->app->instance(DeletesFirebaseUsers::class, new class implements DeletesFirebaseUsers
        {
            public function delete(string $firebaseUid): void {}
        });

        $user = User::factory()->create([
            'email' => 'delete-me@example.com',
            'password' => Hash::make('correct-password'),
            'password_auth_enabled' => true,
            'auth_provider' => 'email',
        ]);

        Sanctum::actingAs($user);
        $token = $user->createToken('mobile')->plainTextToken;

        $response = $this->deleteJson('/api/v1/user/account', [
            'password' => 'correct-password',
        ], [
            'Authorization' => 'Bearer '.$token,
        ]);

        $response->assertOk();
        $response->assertJson([
            'message' => 'Account deleted.',
        ]);

        $user->refresh();
        $this->assertSame('deleted-'.$user->id.'-delete-me@example.com', $user->email);
        $this->assertNull($user->firebase_uid);
        $this->assertFalse($user->password_auth_enabled);
        $this->assertSame('Deleted User', $user->name);
        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_email_user_requires_password_to_delete_account(): void
    {
        $user = User::factory()->create([
            'password_auth_enabled' => true,
            'auth_provider' => 'email',
        ]);

        Sanctum::actingAs($user);
        $token = $user->createToken('mobile')->plainTextToken;

        $response = $this->deleteJson('/api/v1/user/account', [], [
            'Authorization' => 'Bearer '.$token,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['password']);
    }

    public function test_social_user_can_delete_account_without_password(): void
    {
        $firebaseUid = 'firebase-social-uid';

        $this->app->instance(DeletesFirebaseUsers::class, new class($firebaseUid) implements DeletesFirebaseUsers
        {
            public function __construct(private string $expectedUid) {}

            public function delete(string $firebaseUid): void
            {
                if ($firebaseUid !== $this->expectedUid) {
                    throw new \RuntimeException('Unexpected Firebase UID.');
                }
            }
        });

        $user = User::factory()->create([
            'email' => 'social@example.com',
            'firebase_uid' => $firebaseUid,
            'password_auth_enabled' => false,
            'auth_provider' => 'google',
        ]);

        Sanctum::actingAs($user);
        $token = $user->createToken('mobile')->plainTextToken;

        $response = $this->deleteJson('/api/v1/user/account', [], [
            'Authorization' => 'Bearer '.$token,
        ]);

        $response->assertOk();

        $user->refresh();
        $this->assertTrue($user->isDeletedAccount());
        $this->assertNull($user->firebase_uid);
    }

    public function test_deleted_account_cannot_log_in_with_email_and_password(): void
    {
        $user = User::factory()->create([
            'email' => 'deleted-1-former@example.com',
            'password' => Hash::make('correct-password'),
            'password_auth_enabled' => false,
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'correct-password',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['email']);
    }
}
