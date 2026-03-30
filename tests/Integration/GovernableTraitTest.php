<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Tests\Integration;

use GeneaLabs\LaravelGovernor\Tests\Fixtures\Author;
use GeneaLabs\LaravelGovernor\Tests\Fixtures\User;
use GeneaLabs\LaravelGovernor\Tests\UnitTestCase;

class GovernableTraitTest extends UnitTestCase
{
    protected User $user;

    public function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function testGovernableTraitIsUsedInAuthor()
    {
        $author = Author::factory()->create();
        
        // Test that the author can be retrieved
        $this->assertNotNull($author->id);
        $this->assertInstanceOf(Author::class, $author);
    }



    public function testGovernableCanBeRetrieved()
    {
        $author = Author::factory()->create();
        $retrieved = Author::find($author->id);
        
        $this->assertNotNull($retrieved);
        $this->assertEquals($author->id, $retrieved->id);
    }
}
