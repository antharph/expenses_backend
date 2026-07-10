<?php

namespace Tests\Unit;

use App\Contracts\DeletesFirebaseUsers;
use App\Models\User;
use App\Services\DeleteUserAccountService;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class DeleteUserAccountServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_deletes_firebase_user_for_google_accounts(): void
    {
        $this->expectNotToPerformAssertions();

        $firebaseDeleter = Mockery::mock(DeletesFirebaseUsers::class);
        $firebaseDeleter
            ->shouldReceive('delete')
            ->once()
            ->with('firebase-uid-123');

        $tokensRelation = Mockery::mock();
        $tokensRelation->shouldReceive('delete')->once();

        /** @var User&MockInterface $user */
        $user = Mockery::mock(User::class)->makePartial();
        $user->id = 7;
        $user->email = 'google@example.com';
        $user->firebase_uid = 'firebase-uid-123';
        $user->shouldReceive('tokens')->once()->andReturn($tokensRelation);
        $user->shouldReceive('forceFill')
            ->once()
            ->with(Mockery::on(function (array $attributes): bool {
                return $attributes['email'] === 'deleted-7-google@example.com'
                    && $attributes['firebase_uid'] === null
                    && $attributes['password_auth_enabled'] === false
                    && $attributes['name'] === 'Deleted User'
                    && is_string($attributes['password']);
            }))
            ->andReturnSelf();
        $user->shouldReceive('save')->once();

        (new DeleteUserAccountService($firebaseDeleter))->delete($user);
    }

    public function test_skips_firebase_delete_for_apple_prefixed_uid(): void
    {
        $this->expectNotToPerformAssertions();

        $firebaseDeleter = Mockery::mock(DeletesFirebaseUsers::class);
        $firebaseDeleter->shouldNotReceive('delete');

        $tokensRelation = Mockery::mock();
        $tokensRelation->shouldReceive('delete')->once();

        /** @var User&MockInterface $user */
        $user = Mockery::mock(User::class)->makePartial();
        $user->id = 3;
        $user->email = 'apple@example.com';
        $user->firebase_uid = 'apple:001234';
        $user->shouldReceive('tokens')->once()->andReturn($tokensRelation);
        $user->shouldReceive('forceFill')
            ->once()
            ->with(Mockery::on(fn (array $attributes): bool => $attributes['email'] === 'deleted-3-apple@example.com'))
            ->andReturnSelf();
        $user->shouldReceive('save')->once();

        (new DeleteUserAccountService($firebaseDeleter))->delete($user);
    }
}
