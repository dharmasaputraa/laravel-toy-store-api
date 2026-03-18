<?php

namespace Tests\Feature\Api\V1\User;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Api\V1\Auth\AuthTestCase;

class UploadAvatarTest extends AuthTestCase
{
    protected string $baseUrl = '/api/v1/profile';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('s3');
    }

    protected function fakeS3(): \Illuminate\Filesystem\FilesystemAdapter
    {
        /** @var \Illuminate\Filesystem\FilesystemAdapter */
        $disk = Storage::disk('s3');

        return $disk;
    }

    public function test_upload_avatar_success(): void
    {
        $user = $this->createUser();
        $file = UploadedFile::fake()->image('avatar.jpg', 200, 200);

        $response = $this->actingAsUser($user)
            ->postJson($this->url() . '/avatar', [
                'avatar' => $file,
            ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'email',
                    'avatar',
                ]
            ]);

        // Verify avatar was stored
        $user->refresh();
        $this->assertNotNull($user->avatar);
        $this->fakeS3()->assertExists($user->avatar);
    }

    public function test_upload_avatar_with_png(): void
    {
        $user = $this->createUser();
        $file = UploadedFile::fake()->image('avatar.png', 200, 200);

        $response = $this->actingAsUser($user)
            ->postJson($this->url() . '/avatar', [
                'avatar' => $file,
            ]);

        $response->assertOk();

        $user->refresh();
        $this->assertNotNull($user->avatar);
        $this->fakeS3()->assertExists($user->avatar);
    }

    public function test_upload_avatar_with_webp(): void
    {
        $user = $this->createUser();
        $file = UploadedFile::fake()->image('avatar.webp', 200, 200);

        $response = $this->actingAsUser($user)
            ->postJson($this->url() . '/avatar', [
                'avatar' => $file,
            ]);

        $response->assertOk();

        $user->refresh();
        $this->assertNotNull($user->avatar);
        $this->fakeS3()->assertExists($user->avatar);
    }

    public function test_upload_avatar_requires_auth(): void
    {
        $file = UploadedFile::fake()->image('avatar.jpg', 200, 200);

        $this->postJson($this->url() . '/avatar', [
            'avatar' => $file,
        ])->assertUnauthorized();
    }

    public function test_upload_avatar_requires_file(): void
    {
        $user = $this->createUser();

        $response = $this->actingAsUser($user)
            ->postJson($this->url() . '/avatar', []);

        $response->assertUnprocessable();
    }

    public function test_upload_avatar_must_be_image(): void
    {
        $user = $this->createUser();
        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->actingAsUser($user)
            ->postJson($this->url() . '/avatar', [
                'avatar' => $file,
            ]);

        $response->assertUnprocessable();
    }

    public function test_upload_avatar_must_be_valid_mime_type(): void
    {
        $user = $this->createUser();
        $file = UploadedFile::fake()->create('avatar.gif', 100);

        $response = $this->actingAsUser($user)
            ->postJson($this->url() . '/avatar', [
                'avatar' => $file,
            ]);

        $response->assertUnprocessable();
    }

    public function test_upload_avatar_must_not_exceed_2mb(): void
    {
        $user = $this->createUser();
        $file = UploadedFile::fake()->image('avatar.jpg', 200, 200)->size(3000); // 3MB

        $response = $this->actingAsUser($user)
            ->postJson($this->url() . '/avatar', [
                'avatar' => $file,
            ]);

        $response->assertUnprocessable();
    }

    public function test_upload_avatar_replaces_existing_avatar(): void
    {
        $user = $this->createUser(['avatar' => 'avatars/old-avatar.jpg']);
        $this->fakeS3()->put('avatars/old-avatar.jpg', 'old content');

        $file = UploadedFile::fake()->image('new-avatar.jpg', 200, 200);

        $response = $this->actingAsUser($user)
            ->postJson($this->url() . '/avatar', [
                'avatar' => $file,
            ]);

        $response->assertOk();

        $user->refresh();
        $this->assertNotEquals('avatars/old-avatar.jpg', $user->avatar);
        $this->fakeS3()->assertExists($user->avatar);
    }
}
