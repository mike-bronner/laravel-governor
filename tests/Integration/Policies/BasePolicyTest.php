<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Tests\Integration\Policies;

use GeneaLabs\LaravelGovernor\Permission;
use GeneaLabs\LaravelGovernor\Team;
use GeneaLabs\LaravelGovernor\Tests\Fixtures\Author;
use GeneaLabs\LaravelGovernor\Tests\Fixtures\User;
use GeneaLabs\LaravelGovernor\Tests\UnitTestCase;

class BasePolicyTest extends UnitTestCase
{
    protected $author;
    protected $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->userWithoutRoles = User::factory()->create();
        $this->userWithoutRoles->roles()->sync([]);
        $this->userWithoutRoles->teams()->sync([]);
        $this->otherUser = User::factory()->create();
        $this->otherUser->roles()->attach("SuperAdmin");
        $this->actingAs($this->otherUser);
        $this->otherTeam = (new Team)->create([
            "name" => "Test Team",
            "description" => "bla bla bla",
        ]);
        $this->otherAuthor = Author::factory()->create();
        $this->otherAuthor->teams()->attach($this->otherTeam);

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        $this->team = (new Team)->create([
            "name" => "Test Team",
            "description" => "bla bla bla",
        ]);
        $this->author = Author::factory()->create();
        $this->author->teams()->attach($this->team);
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

    protected function deletePermission(string $action)
    {
        (new Permission)
            ->where("entity_name", "author")
            ->where("action_name", $action)
            ->delete();
    }

    public function testUserWithoutRolesOrTeams()
    {
        (new Permission)->firstOrCreate([
            "role_name" => "Member",
            "entity_name" => "author",
            "action_name" => "create",
            "ownership_name" => "any",
        ]);
        (new Permission)->firstOrCreate([
            "role_name" => "Member",
            "entity_name" => "author",
            "action_name" => "viewAny",
            "ownership_name" => "any",
        ]);
        (new Permission)->firstOrCreate([
            "role_name" => "Member",
            "entity_name" => "author",
            "action_name" => "update",
            "ownership_name" => "any",
        ]);
        (new Permission)->firstOrCreate([
            "role_name" => "Member",
            "entity_name" => "author",
            "action_name" => "delete",
            "ownership_name" => "any",
        ]);
        (new Permission)->firstOrCreate([
            "role_name" => "Member",
            "entity_name" => "author",
            "action_name" => "restore",
            "ownership_name" => "any",
        ]);
        (new Permission)->firstOrCreate([
            "role_name" => "Member",
            "entity_name" => "author",
            "action_name" => "view",
            "ownership_name" => "any",
        ]);
        (new Permission)->firstOrCreate([
            "role_name" => "Member",
            "entity_name" => "author",
            "action_name" => "forceDelete",
            "ownership_name" => "any",
        ]);

        $this->assertFalse($this->userWithoutRoles->can("create", Author::class));
        $this->assertFalse($this->userWithoutRoles->can("viewAny", Author::class));
        $this->assertFalse($this->userWithoutRoles->can("update", $this->author));
        $this->assertFalse($this->userWithoutRoles->can("delete", $this->author));
        $this->assertFalse($this->userWithoutRoles->can("restore", $this->author));
        $this->assertFalse($this->userWithoutRoles->can("view", $this->author));
        $this->assertFalse($this->userWithoutRoles->can("forceDelete", $this->author));
    }

    public function testCannotCreateWithoutPolicy()
    {
        $this->deletePermission("create");

        $this->assertFalse($this->user->can("create", Author::class));
    }

    public function testCanCreateWithoutPolicyAsSuperAdmin()
    {
        $this->deletePermission("create");

        $this->assertTrue($this->otherUser->can("create", Author::class));
    }

    public function testCannotCreateWithPolicy()
    {
        $this->updatePermission("Member", "create", "no");

        $this->assertFalse($this->user->can("create", Author::class));
    }

    public function testCanCreateWithNegativePolicyAsSuperAdmin()
    {
        $this->updatePermission("SuperAdmin", "create", "no");

        $this->assertTrue($this->otherUser->can("create", Author::class));
    }

    public function testCanCreateWithPolicy()
    {
        $this->updatePermission("Member", "create", "any");

        $this->assertTrue($this->user->can("create", Author::class));
    }

    public function testCannotViewAnyWithoutPolicy()
    {
        $this->deletePermission("viewAny");

        $this->assertFalse($this->user->can("viewAny", Author::class));
    }

    public function testCanViewAnyWithoutPolicyAsSuperAdmin()
    {
        $this->deletePermission("viewAny");

        $this->assertTrue($this->otherUser->can("viewAny", Author::class));
    }

    public function testCannotViewAnyWithPolicy()
    {
        $this->updatePermission("Member", "viewAny", "no");

        $this->assertFalse($this->user->can("viewAny", Author::class));
    }

    public function testCanViewAnyWithNegativePolicyAsSuperAdmin()
    {
        $this->updatePermission("SuperAdmin", "viewAny", "no");

        $this->assertTrue($this->otherUser->can("viewAny", Author::class));
    }

    public function testCanViewAnyWithPolicy()
    {
        $this->updatePermission("Member", "viewAny", "any");

        $this->assertTrue($this->user->can("viewAny", Author::class));
    }

    public function testCannotUpdateWithoutPolicy()
    {
        $this->deletePermission("update");

        $this->assertFalse($this->user->can("update", $this->author));
    }

    public function testCanUpdateWithoutPolicyAsSuperAdmin()
    {
        $this->deletePermission("update");

        $this->assertTrue($this->otherUser->can("update", $this->author));
    }

    public function testCannotUpdateWithPolicy()
    {
        $this->updatePermission("Member", "update", "no");

        $this->assertFalse($this->user->can("update", $this->author));
    }

    public function testCanUpdateWithNegativePolicyAsSuperAdmin()
    {
        $this->updatePermission("SuperAdmin", "update", "no");

        $this->assertTrue($this->otherUser->can("update", $this->author));
    }

    public function testCanUpdateAnyWithPolicy()
    {
        $this->updatePermission("Member", "update", "any");

        $this->assertTrue($this->user->can("update", $this->author));
    }

    public function testCannotUpdateOwnWithPolicy()
    {
        $this->updatePermission("Member", "viewAny", "own");

        $this->assertFalse($this->user->can("update", $this->otherAuthor));
    }

    public function testCanUpdateOwnWithPolicy()
    {
        $this->updatePermission("Member", "update", "own");

        $this->assertTrue($this->user->can("update", $this->author));
    }

    public function testCannotViewWithoutPolicy()
    {
        $this->deletePermission("view");

        $this->assertFalse($this->user->can("view", $this->author));
    }

    public function testCanViewWithoutPolicyAsSuperAdmin()
    {
        $this->deletePermission("view");

        $this->assertTrue($this->otherUser->can("view", $this->author));
    }

    public function testCannotViewWithPolicy()
    {
        $this->updatePermission("Member", "view", "no");

        $this->assertFalse($this->user->can("view", $this->author));
    }

    public function testCanViewWithNegativePolicyAsSuperAdmin()
    {
        $this->updatePermission("SuperAdmin", "view", "no");

        $this->assertTrue($this->otherUser->can("view", $this->author));
    }

    public function testCanViewWithPolicy()
    {
        $this->updatePermission("Member", "view", "any");

        $this->assertTrue($this->user->can("view", $this->author));
    }

    public function testCannotViewOwnWithPolicy()
    {
        $this->updatePermission("Member", "view", "own");

        $this->assertFalse($this->user->can("view", $this->otherAuthor));
    }

    public function testCanViewOwnWithPolicy()
    {
        $this->updatePermission("Member", "view", "own");

        $this->assertTrue($this->user->can("view", $this->author));
    }

    public function testCannotDeleteWithoutPolicy()
    {
        $this->deletePermission("delete");

        $this->assertFalse($this->user->can("delete", $this->author));
    }

    public function testCanDeleteWithoutPolicyAsSuperAdmin()
    {
        $this->deletePermission("delete");

        $this->assertTrue($this->otherUser->can("delete", $this->author));
    }

    public function testCannotDeleteWithPolicy()
    {
        $this->updatePermission("Member", "delete", "no");

        $this->assertFalse($this->user->can("delete", $this->author));
    }

    public function testCanDeleteWithNegativePolicyAsSuperAdmin()
    {
        $this->updatePermission("SuperAdmin", "delete", "no");

        $this->assertTrue($this->otherUser->can("delete", $this->author));
    }

    public function testCanDeleteWithPolicy()
    {
        $this->updatePermission("Member", "delete", "any");

        $this->assertTrue($this->user->can("delete", $this->author));
    }

    public function testCannotDeleteOwnWithPolicy()
    {
        $this->updatePermission("Member", "delete", "own");

        $this->assertFalse($this->user->can("delete", $this->otherAuthor));
    }

    public function testCanDeleteOwnWithPolicy()
    {
        $this->updatePermission("Member", "delete", "own");

        $this->assertTrue($this->user->can("delete", $this->author));
    }

    public function testCannotRestoreWithoutPolicy()
    {
        $this->deletePermission("restore");

        $this->assertFalse($this->user->can("restore", $this->author));
    }

    public function testCanRestoreWithoutPolicyAsSuperAdmin()
    {
        $this->deletePermission("restore");

        $this->assertTrue($this->otherUser->can("restore", $this->author));
    }

    public function testCannotRestoreWithPolicy()
    {
        $this->updatePermission("Member", "restore", "no");

        $this->assertFalse($this->user->can("restore", $this->author));
    }

    public function testCanRestoreWithNegativePolicyAsSuperAdmin()
    {
        $this->updatePermission("SuperAdmin", "restore", "no");

        $this->assertTrue($this->otherUser->can("restore", $this->author));
    }

    public function testCanRestoreWithPolicy()
    {
        $this->updatePermission("Member", "restore", "any");

        $this->assertTrue($this->user->can("restore", $this->author));
    }

    public function testCannotRestoreOwnWithPolicy()
    {
        $this->updatePermission("Member", "restore", "own");

        $this->assertFalse($this->user->can("restore", $this->otherAuthor));
    }

    public function testCanRestoreOwnWithPolicy()
    {
        $this->updatePermission("Member", "restore", "own");

        $this->assertTrue($this->user->can("restore", $this->author));
    }

    public function testCannotForceDeleteWithoutPolicy()
    {
        $this->deletePermission("forceDelete");

        $this->assertFalse($this->user->can("forceDelete", $this->author));
    }

    public function testCanForceDeleteWithoutPolicyAsSuperAdmin()
    {
        $this->deletePermission("forceDelete");

        $this->assertTrue($this->otherUser->can("forceDelete", $this->author));
    }

    public function testCannotForceDeleteWithPolicy()
    {
        $this->updatePermission("Member", "forceDelete", "no");

        $this->assertFalse($this->user->can("forceDelete", $this->author));
    }

    public function testCanForceDeleteWithNegativePolicyAsSuperAdmin()
    {
        $this->updatePermission("SuperAdmin", "forceDelete", "no");

        $this->assertTrue($this->otherUser->can("forceDelete", $this->author));
    }

    public function testCanForceDeleteWithPolicy()
    {
        $this->updatePermission("Member", "forceDelete", "any");

        $this->assertTrue($this->user->can("forceDelete", $this->author));
    }

    public function testCannotForceDeleteOwnWithPolicy()
    {
        $this->updatePermission("Member", "forceDelete", "own");

        $this->assertFalse($this->user->can("forceDelete", $this->otherAuthor));
    }

    public function testCanForceDeleteOwnWithPolicy()
    {
        $this->updatePermission("Member", "forceDelete", "own");

        $this->assertTrue($this->user->can("forceDelete", $this->author));
    }
}
