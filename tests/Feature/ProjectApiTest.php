<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProjectApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_list_update_and_delete_projects(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $createResponse = $this->postJson('/api/projects', [
            'name' => 'Research',
            'description' => 'AI experiments',
        ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Research')
            ->assertJsonPath('data.description', 'AI experiments');

        $projectId = $createResponse->json('data.id');
        $user->conversations()->create([
            'title' => 'Research chat',
            'model' => 'model-a',
            'project_id' => $projectId,
        ]);

        $this->getJson('/api/projects')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Research')
            ->assertJsonPath('data.0.conversations_count', 1);

        $this->patchJson("/api/projects/{$projectId}", ['name' => 'Updated research'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated research');

        $this->deleteJson("/api/projects/{$projectId}")->assertNoContent();

        $this->assertDatabaseMissing('projects', ['id' => $projectId]);
        $this->assertDatabaseHas('conversations', [
            'title' => 'Research chat',
            'project_id' => null,
        ]);
    }

    public function test_users_cannot_read_or_modify_another_users_project(): void
    {
        $owner = User::factory()->create();
        $project = $owner->projects()->create(['name' => 'Private project']);
        Sanctum::actingAs(User::factory()->create());

        $this->patchJson("/api/projects/{$project->id}", ['name' => 'Stolen'])
            ->assertNotFound();
        $this->deleteJson("/api/projects/{$project->id}")->assertNotFound();

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Private project',
        ]);
    }
}
