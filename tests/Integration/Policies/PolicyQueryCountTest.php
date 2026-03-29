<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Tests\Integration\Policies;

use GeneaLabs\LaravelGovernor\Permission;
use GeneaLabs\LaravelGovernor\Team;
use GeneaLabs\LaravelGovernor\Tests\Fixtures\Author;
use GeneaLabs\LaravelGovernor\Tests\Fixtures\User;
use GeneaLabs\LaravelGovernor\Tests\UnitTestCase;
use Illuminate\Support\Facades\DB;

class PolicyQueryCountTest extends UnitTestCase
{
    protected $user;
    protected $team;
    protected $author;

    public function setUp(): void
    {
        parent::setUp();

        // Create a user and team
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        $this->team = (new Team)->create([
            "name" => "Test Team",
            "description" => "Test Description",
        ]);
        $this->user->teams()->attach($this->team->id);

        // Create a permission for role (like the existing tests do)
        $this->updatePermission("Member", "view", "any");

        $this->author = Author::factory()->create();
    }

    protected function updatePermission(string $role, string $action, string $ownership)
    {
        $permission = (new Permission)->firstOrNew([
            "role_name" => $role,
            "entity_name" => "Author (Laravel Governor)",
            "action_name" => $action,
        ]);
        $permission->ownership_name = $ownership;
        $permission->save();
    }

    public function testPolicyViewCheckExecutesMinimalQueries()
    {
        // Give the user the Member role
        $this->user->roles()->sync(["Member"]);

        // Reload user fresh to clear any loaded relationships
        $user = $this->user->fresh();

        DB::flushQueryLog();
        DB::enableQueryLog();

        // Execute the authorization check
        $result = $user->can("view", $this->author);

        $queries = DB::getQueryLog();

        // The policy check should execute exactly 2 queries:
        // 1. Load roles relationship
        // 2. Load teams relationship
        // (Permissions come from the service container singleton, no extra query)
        $this->assertLessThanOrEqual(
            2,
            count($queries),
            "Policy check should use at most 2 queries (roles + teams). Got " . count($queries) . " queries:\n" .
            implode("\n", array_map(fn($q) => $q['query'], $queries))
        );

        // Authorization should pass
        $this->assertTrue($result);
    }

    public function testMultipleResourceAuthorizationCheckIsEfficient()
    {
        // Give the user the Member role
        $this->user->roles()->sync(["Member"]);

        // Reload user fresh
        $user = $this->user->fresh();

        // Create multiple authors
        $authors = Author::factory()->count(3)->create();

        DB::flushQueryLog();
        DB::enableQueryLog();

        // Check authorization for multiple resources
        foreach ($authors as $author) {
            $user->can("view", $author);
        }

        $queries = DB::getQueryLog();

        // For 3 resources, we should still only have 2 queries total (due to loadMissing efficiency)
        // because user relationships don't need to be loaded again
        $this->assertLessThanOrEqual(
            2,
            count($queries),
            "Multiple authorization checks should reuse loaded relationships. Got " . count($queries) . " queries:\n" .
            implode("\n", array_map(fn($q) => $q['query'], $queries))
        );
    }
}
