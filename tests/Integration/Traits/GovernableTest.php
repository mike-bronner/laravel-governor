<?php namespace GeneaLabs\LaravelGovernor\Tests\Integration\Traits;

use GeneaLabs\LaravelGovernor\Permission;
use GeneaLabs\LaravelGovernor\Team;
use GeneaLabs\LaravelGovernor\Tests\Fixtures\Author;
use GeneaLabs\LaravelGovernor\Tests\Fixtures\AuthorWithoutPolicy;
use GeneaLabs\LaravelGovernor\Tests\Fixtures\User;
use GeneaLabs\LaravelGovernor\Tests\UnitTestCase;

class GovernableTest extends UnitTestCase
{
    protected $author;
    protected $otherAuthor;
    protected $otherTeam;
    protected $otherUser;
    protected $team;
    protected $user;

    public function setUp() : void
    {
        parent::setUp();

        $this->otherUser = User::factory()->create();
        $this->actingAs($this->otherUser);
        $this->otherTeam = (new Team)->create([
            "name" => "Test Team",
            "description" => "bla bla bla",
        ]);
        $this->otherAuthor = Author::factory()->create();
        $this->otherAuthor->teams()->attach($this->team);

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        $this->team = (new Team)->create([
            "name" => "Test Team",
            "description" => "bla bla bla",
        ]);
        $this->author = Author::factory()->create();
        $this->author->teams()->attach($this->team);
    }

    public function testOwnedByRelationship()
    {
        $this->assertEquals($this->user->id, $this->author->ownedBy->id);
        $this->assertEquals($this->user->id, $this->author->governor_owned_by);
    }

    public function testTeamsRelationship()
    {
        $this->assertTrue($this->user->teams->contains($this->team));
        $this->assertTrue($this->author->teams->contains($this->team));
    }

    public function testScopeDeletableWithoutPermissions()
    {
        $results = (new Author)
            ->deletable()
            ->get();

        $this->assertEmpty($results);
    }

    public function testScopeDeletableWithAnyPermission()
    {
        (new Permission)->create([
            "role_name" => "Member",
            "entity_name" => "Author (Laravel Governor)",
            "action_name" => "delete",
            "ownership_name" => "any"
        ]);
        $results = (new Author)
            ->deletable()
            ->get();

        $this->assertTrue($results->isNotEmpty());
        $this->assertTrue($results->contains($this->author));
        $this->assertTrue($results->contains($this->otherAuthor));
    }

    public function testScopeDeletableWithOwnPermission()
    {
        $permission = (new Permission)->firstOrNew([
            "role_name" => "Member",
            "entity_name" => "Author (Laravel Governor)",
            "action_name" => "delete",
        ]);
        $permission->ownership_name = "own";
        $permission->save();
        $results = (new Author)
            ->deletable()
            ->get();

        $this->assertTrue($results->isNotEmpty());
        $this->assertTrue($results->contains($this->author));
        $this->assertFalse($results->contains($this->otherAuthor));
    }

    public function testScopeUpdatableWithoutPermissions()
    {
        $results = (new Author)
            ->updatable()
            ->get();

        $this->assertEmpty($results);
    }

    public function testScopeUpdatableWithAnyPermission()
    {
        (new Permission)->create([
            "role_name" => "Member",
            "entity_name" => "Author (Laravel Governor)",
            "action_name" => "update",
            "ownership_name" => "any"
        ]);
        $results = (new Author)
            ->updatable()
            ->get();

        $this->assertTrue($results->isNotEmpty());
        $this->assertTrue($results->contains($this->author));
        $this->assertTrue($results->contains($this->otherAuthor));
    }

    public function testScopeUpdatableWithOwnPermission()
    {
        $permission = (new Permission)->firstOrNew([
            "role_name" => "Member",
            "entity_name" => "Author (Laravel Governor)",
            "action_name" => "update",
        ]);
        $permission->ownership_name = "own";
        $permission->save();
        $results = (new Author)
            ->updatable()
            ->get();

        $this->assertTrue($results->isNotEmpty());
        $this->assertTrue($results->contains($this->author));
        $this->assertFalse($results->contains($this->otherAuthor));
    }

    public function testScopeViewableWithoutPermissions()
    {
        $results = (new Author)
            ->viewable()
            ->get();

        $this->assertEmpty($results);
    }

    public function testScopeViewableWithAnyPermission()
    {
        (new Permission)->create([
            "role_name" => "Member",
            "entity_name" => "Author (Laravel Governor)",
            "action_name" => "view",
            "ownership_name" => "any"
        ]);
        $results = (new Author)
            ->viewable()
            ->get();

        $this->assertTrue($results->isNotEmpty());
        $this->assertTrue($results->contains($this->author));
        $this->assertTrue($results->contains($this->otherAuthor));
    }

    public function testScopeViewableWithOwnPermission()
    {
        (new Permission)->firstOrCreate([
            "role_name" => "Member",
            "entity_name" => "User (Laravel Governor)",
            "action_name" => "view",
            "ownership_name" => "own"
        ]);

        $permission = (new Permission)->firstOrNew([
            "role_name" => "Member",
            "entity_name" => "Author (Laravel Governor)",
            "action_name" => "view",
        ]);
        $permission->ownership_name = "own";
        $permission->save();
        $permissions = (new Permission)
            ->toBase()
            ->get();
        app()->instance("governor-permissions", $permissions);
        $authorResults = (new Author)
            ->viewable()
            ->get();
        $userClass = app(config('genealabs-laravel-governor.models.auth'));
        $userResults = (new $userClass)
            ->viewable()
            ->get();

        $this->assertTrue($authorResults->isNotEmpty());
        $this->assertTrue($authorResults->contains($this->author));
        $this->assertFalse($authorResults->contains($this->otherAuthor));
        $this->assertTrue($userResults->isNotEmpty());
        $this->assertTrue($userResults->contains($this->user));
        $this->assertFalse($userResults->contains($this->otherUser));
    }

    public function testScopeViewAnyableWithoutPermissions()
    {
        $results = (new Author)
            ->viewAnyable()
            ->get();

        $this->assertEmpty($results);
    }

    public function testScopeViewAnyableWithAnyPermission()
    {
        (new Permission)->create([
            "role_name" => "Member",
            "entity_name" => "Author (Laravel Governor)",
            "action_name" => "viewAny",
            "ownership_name" => "any"
        ]);
        $results = (new Author)
            ->viewAnyable()
            ->get();

        $this->assertTrue($results->isNotEmpty());
        $this->assertTrue($results->contains($this->author));
        $this->assertTrue($results->contains($this->otherAuthor));
    }

    public function testScopeViewAnyableWithOwnPermission()
    {
        $permission = (new Permission)->firstOrNew([
            "role_name" => "Member",
            "entity_name" => "Author (Laravel Governor)",
            "action_name" => "viewAny",
        ]);
        $permission->ownership_name = "own";
        $permission->save();
        $results = (new Author)
            ->viewAnyable()
            ->get();

        $this->assertTrue($results->isNotEmpty());
        $this->assertTrue($results->contains($this->author));
        $this->assertFalse($results->contains($this->otherAuthor));
    }

    public function testScopeRestorableWithoutPermissions()
    {
        $results = (new Author)
            ->restorable()
            ->get();

        $this->assertEmpty($results);
    }

    public function testScopeRestorableWithAnyPermission()
    {
        (new Permission)->create([
            "role_name" => "Member",
            "entity_name" => "Author (Laravel Governor)",
            "action_name" => "restore",
            "ownership_name" => "any"
        ]);
        $results = (new Author)
            ->restorable()
            ->get();

        $this->assertTrue($results->isNotEmpty());
        $this->assertTrue($results->contains($this->author));
        $this->assertTrue($results->contains($this->otherAuthor));
    }

    public function testScopeRestorableWithOwnPermission()
    {
        $permission = (new Permission)->firstOrNew([
            "role_name" => "Member",
            "entity_name" => "Author (Laravel Governor)",
            "action_name" => "restore",
        ]);
        $permission->ownership_name = "own";
        $permission->save();
        $results = (new Author)
            ->restorable()
            ->get();

        $this->assertTrue($results->isNotEmpty());
        $this->assertTrue($results->contains($this->author));
        $this->assertFalse($results->contains($this->otherAuthor));
    }

    public function testScopeForceDeletableWithoutPermissions()
    {
        $results = (new Author)
            ->forceDeletable()
            ->get();

        $this->assertEmpty($results);
    }

    public function testScopeForceDeletableWithAnyPermission()
    {
        (new Permission)->create([
            "role_name" => "Member",
            "entity_name" => "Author (Laravel Governor)",
            "action_name" => "forceDelete",
            "ownership_name" => "any"
        ]);
        $results = (new Author)
            ->forceDeletable()
            ->get();

        $this->assertTrue($results->isNotEmpty());
        $this->assertTrue($results->contains($this->author));
        $this->assertTrue($results->contains($this->otherAuthor));
    }

    public function testScopeForceDeletableWithOwnPermission()
    {
        $permission = (new Permission)->firstOrNew([
            "role_name" => "Member",
            "entity_name" => "Author (Laravel Governor)",
            "action_name" => "forceDelete",
        ]);
        $permission->ownership_name = "own";
        $permission->save();
        $results = (new Author)
            ->forceDeletable()
            ->get();

        $this->assertTrue($results->isNotEmpty());
        $this->assertTrue($results->contains($this->author));
        $this->assertFalse($results->contains($this->otherAuthor));
    }

    public function testAuthorWithoutPolicyFailsPermissions()
    {
        $results = (new AuthorWithoutPolicy)
            ->viewable()
            ->get();

        $this->assertTrue($results->isEmpty());
    }
}
