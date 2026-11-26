<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_profile_returns_key_status_without_exposing_the_key(): void
    {
        $user = User::factory()->create([
            'openrouter_api_key' => 'sk-or-private',
            'default_model' => 'openai/gpt-4o-mini',
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('data.name', $user->name)
            ->assertJsonPath('data.email', $user->email)
            ->assertJsonPath('data.api_key_configured', true)
            ->assertJsonPath('data.default_model', 'openai/gpt-4o-mini')
            ->assertJsonMissingPath('data.openrouter_api_key');
    }

    public function test_profile_can_be_updated(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->putJson('/api/user/profile', [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated Name')
            ->assertJsonPath('data.email', 'updated@example.com');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ]);
    }

    public function test_settings_store_the_api_key_encrypted_and_return_its_status(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->putJson('/api/user/settings', [
            'openrouter_api_key' => 'sk-or-secret-value',
            'default_model' => 'openai/gpt-4o-mini',
        ])
            ->assertOk()
            ->assertJsonPath('data.api_key_configured', true)
            ->assertJsonPath('data.default_model', 'openai/gpt-4o-mini')
            ->assertJsonMissingPath('data.openrouter_api_key');

        $encryptedKey = DB::table('users')->where('id', $user->id)->value('openrouter_api_key');

        $this->assertNotSame('sk-or-secret-value', $encryptedKey);
        $this->assertSame('sk-or-secret-value', $user->fresh()->openrouter_api_key);
    }

    public function test_api_key_can_be_removed_without_exposing_it(): void
    {
        $user = User::factory()->create(['openrouter_api_key' => 'sk-or-secret-value']);
        Sanctum::actingAs($user);

        $this->deleteJson('/api/settings/api-key')
            ->assertOk()
            ->assertJsonPath('data.api_key_configured', false)
            ->assertJsonMissingPath('data.openrouter_api_key');

        $this->assertNull($user->fresh()->openrouter_api_key);
    }

    public function test_passcode_is_hashed_and_required_to_change_after_initial_setup(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->putJson('/api/settings/passcode', [
            'passcode' => 'secret-code',
            'passcode_confirmation' => 'secret-code',
        ])->assertOk();

        $storedPasscode = DB::table('users')->where('id', $user->id)->value('passcode');
        $this->assertNotSame('secret-code', $storedPasscode);
        $this->assertTrue(Hash::check('secret-code', $user->fresh()->passcode));

        $this->putJson('/api/settings/passcode', [
            'passcode' => 'new-secret-code',
            'passcode_confirmation' => 'new-secret-code',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('current_passcode');

        $this->putJson('/api/settings/passcode', [
            'current_passcode' => 'secret-code',
            'passcode' => 'new-secret-code',
            'passcode_confirmation' => 'new-secret-code',
        ])->assertOk();

        $this->assertTrue(Hash::check('new-secret-code', $user->fresh()->passcode));
    }

    public function test_profile_password_update_hashes_the_new_password(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->putJson('/api/user/profile', [
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ])->assertOk();

        $this->assertTrue(Hash::check('new-secure-password', $user->fresh()->password));
    }

    public function test_model_list_uses_the_users_openrouter_key(): void
    {
        Http::fake([
            'https://openrouter.ai/api/v1/models' => Http::response([
                'data' => [[
                    'id' => 'openai/gpt-4o-mini',
                    'architecture' => [
                        'modality' => 'text+image',
                        'input_modalities' => ['text', 'image'],
                        'output_modalities' => ['text'],
                    ],
                    'supported_parameters' => ['reasoning'],
                    'pricing' => ['prompt' => '0.00000015', 'completion' => '0.0000006'],
                ]],
            ]),
        ]);

        $user = User::factory()->create(['openrouter_api_key' => 'sk-or-test']);
        Sanctum::actingAs($user);

        $this->getJson('/api/models')
            ->assertOk()
            ->assertJsonPath('data.0.id', 'openai/gpt-4o-mini')
            ->assertJsonPath('data.0.vision', true)
            ->assertJsonPath('data.0.reasoning', true)
            ->assertJsonPath('data.0.price.prompt', '0.00000015')
            ->assertJsonPath('data.0.input_modalities.1', 'image')
            ->assertJsonPath('data.0.output_modalities.0', 'text');

        Http::assertSent(fn (ClientRequest $request): bool => $request->hasHeader(
            'Authorization',
            'Bearer sk-or-test',
        ));
    }

    public function test_model_list_requires_a_saved_openrouter_key(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/models')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('openrouter_api_key');
    }

    public function test_user_endpoints_require_authentication(): void
    {
        $this->getJson('/api/user')->assertUnauthorized();
        $this->putJson('/api/user/profile', ['name' => 'Unauthenticated'])->assertUnauthorized();
    }
}
