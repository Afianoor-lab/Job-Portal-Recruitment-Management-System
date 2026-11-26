<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ConversationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_conversation_uses_the_users_default_model_when_not_provided(): void
    {
        $user = User::factory()->create(['default_model' => 'openai/gpt-4o-mini']);
        Sanctum::actingAs($user);

        $this->postJson('/api/conversations', ['title' => 'First chat'])
            ->assertCreated()
            ->assertJsonPath('data.user_id', $user->id)
            ->assertJsonPath('data.title', 'First chat')
            ->assertJsonPath('data.model', 'openai/gpt-4o-mini');

        $this->assertDatabaseHas('conversations', [
            'user_id' => $user->id,
            'title' => 'First chat',
            'model' => 'openai/gpt-4o-mini',
        ]);

        $conversation = Conversation::query()->where('title', 'First chat')->firstOrFail();
        $message = $conversation->messages()->create([
            'role' => 'user',
            'content' => 'Hello',
        ]);

        $this->assertSame($conversation->id, $message->conversation->id);
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => 'Hello',
        ]);
    }

    public function test_conversation_requires_a_model_when_no_default_is_saved(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/conversations')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('model');
    }

    public function test_history_lists_only_the_authenticated_users_conversations_with_pagination(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $user->conversations()->create(['title' => 'First chat', 'model' => 'model-a']);
        $user->conversations()->create(['title' => 'Second chat', 'model' => 'model-a']);
        $otherUser->conversations()->create(['title' => 'Private chat', 'model' => 'model-b']);

        Sanctum::actingAs($user);

        $this->getJson('/api/conversations?per_page=1')
            ->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonCount(1, 'data')
            ->assertJsonMissing(['title' => 'Private chat']);
    }

    public function test_history_details_include_messages_in_creation_order(): void
    {
        $user = User::factory()->create();
        $conversation = $user->conversations()->create([
            'title' => 'Chat history',
            'model' => 'model-a',
        ]);
        $conversation->messages()->create(['role' => 'user', 'content' => 'Question']);
        $conversation->messages()->create(['role' => 'assistant', 'content' => 'Answer']);

        Sanctum::actingAs($user);

        $this->getJson("/api/conversations/{$conversation->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $conversation->id)
            ->assertJsonPath('data.messages.0.content', 'Question')
            ->assertJsonPath('data.messages.1.content', 'Answer');
    }

    public function test_users_cannot_view_or_delete_another_users_conversation(): void
    {
        $owner = User::factory()->create();
        $conversation = $owner->conversations()->create([
            'title' => 'Private chat',
            'model' => 'model-a',
        ]);

        Sanctum::actingAs(User::factory()->create());

        $this->getJson("/api/conversations/{$conversation->id}")->assertNotFound();
        $this->deleteJson("/api/conversations/{$conversation->id}")->assertNotFound();

        $this->assertDatabaseHas('conversations', ['id' => $conversation->id]);
    }

    public function test_deleting_a_conversation_also_deletes_its_messages(): void
    {
        $user = User::factory()->create();
        $conversation = $user->conversations()->create([
            'title' => 'Delete me',
            'model' => 'model-a',
        ]);
        $message = $conversation->messages()->create([
            'role' => 'user',
            'content' => 'This message should be deleted too',
        ]);

        Sanctum::actingAs($user);

        $this->deleteJson("/api/conversations/{$conversation->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('conversations', ['id' => $conversation->id]);
        $this->assertDatabaseMissing('messages', ['id' => $message->id]);
    }

    public function test_chat_list_searches_titles_and_messages_and_filters_archived_chats(): void
    {
        $user = User::factory()->create();
        $matching = $user->conversations()->create(['title' => 'A normal chat', 'model' => 'model-a']);
        $matching->messages()->create(['role' => 'user', 'content' => 'needle in message']);
        $user->conversations()->create([
            'title' => 'needle in title',
            'model' => 'model-a',
            'locked' => true,
        ]);
        $user->conversations()->create([
            'title' => 'Archived needle',
            'model' => 'model-a',
            'archived' => true,
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/chats?q=needle&archived=0')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.title', 'A normal chat')
            ->assertJsonMissingPath('data.0.messages');

        $this->getJson('/api/chats?archived=1')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.title', 'Archived needle');
    }

    public function test_chat_can_be_updated_and_cannot_be_assigned_to_another_users_project(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $project = $user->projects()->create(['name' => 'My project']);
        $otherProject = $otherUser->projects()->create(['name' => 'Other project']);
        $conversation = $user->conversations()->create([
            'title' => 'Before update',
            'model' => 'model-a',
        ]);
        Sanctum::actingAs($user);

        $this->patchJson("/api/chats/{$conversation->id}", [
            'title' => 'Updated chat',
            'pinned' => true,
            'archived' => true,
            'model' => 'model-b',
            'persona' => 'concise',
            'project_id' => $project->id,
        ])
            ->assertOk()
            ->assertJsonPath('data.title', 'Updated chat')
            ->assertJsonPath('data.pinned', true)
            ->assertJsonPath('data.archived', true)
            ->assertJsonPath('data.project_id', $project->id);

        $this->patchJson("/api/chats/{$conversation->id}", [
            'project_id' => $otherProject->id,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('project_id');
    }

    public function test_delete_all_chats_only_removes_the_authenticated_users_chats(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $conversation = $user->conversations()->create(['title' => 'Mine', 'model' => 'model-a']);
        $message = $conversation->messages()->create(['role' => 'user', 'content' => 'Delete me']);
        $otherConversation = $otherUser->conversations()->create(['title' => 'Theirs', 'model' => 'model-a']);
        Sanctum::actingAs($user);

        $this->deleteJson('/api/chats')->assertNoContent();

        $this->assertDatabaseMissing('conversations', ['id' => $conversation->id]);
        $this->assertDatabaseMissing('messages', ['id' => $message->id]);
        $this->assertDatabaseHas('conversations', ['id' => $otherConversation->id]);
    }

    public function test_duplicate_copies_messages_but_not_pinned_or_locked_state(): void
    {
        $user = User::factory()->create();
        $project = $user->projects()->create(['name' => 'Project']);
        $conversation = $user->conversations()->create([
            'title' => 'Original',
            'model' => 'model-a',
            'project_id' => $project->id,
            'persona' => 'friendly',
            'pinned' => true,
        ]);
        $conversation->messages()->create([
            'role' => 'user',
            'content' => 'Hello',
            'model' => 'model-a',
        ]);
        Sanctum::actingAs($user);

        $this->postJson("/api/chats/{$conversation->id}/duplicate")
            ->assertCreated()
            ->assertJsonPath('data.title', 'Original (copy)')
            ->assertJsonPath('data.pinned', false)
            ->assertJsonPath('data.locked', false)
            ->assertJsonPath('data.project_id', $project->id)
            ->assertJsonPath('data.messages.0.content', 'Hello');
    }

    public function test_chat_lock_requires_a_passcode_and_unlock_verifies_it(): void
    {
        $user = User::factory()->create();
        $conversation = $user->conversations()->create(['title' => 'Protected', 'model' => 'model-a']);
        Sanctum::actingAs($user);

        $this->postJson("/api/chats/{$conversation->id}/lock")->assertStatus(409);

        $this->putJson('/api/settings/passcode', [
            'passcode' => 'private-code',
            'passcode_confirmation' => 'private-code',
        ])->assertOk();

        $this->postJson("/api/chats/{$conversation->id}/lock")
            ->assertOk()
            ->assertJsonPath('data.locked', true);
        $this->getJson("/api/chats/{$conversation->id}")->assertStatus(423);
        $this->getJson('/api/chats')->assertJsonMissing(['title' => 'Protected']);

        $this->postJson("/api/chats/{$conversation->id}/unlock", ['code' => 'wrong-code'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('code');

        $this->postJson("/api/chats/{$conversation->id}/unlock", ['code' => 'private-code'])
            ->assertOk()
            ->assertJsonPath('data.locked', false);
        $this->getJson("/api/chats/{$conversation->id}")->assertOk();
    }
}
