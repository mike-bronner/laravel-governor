<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Tests\Integration;

use GeneaLabs\LaravelGovernor\Entity;
use GeneaLabs\LaravelGovernor\Tests\UnitTestCase;

class EntityAliasTest extends UnitTestCase
{
    public function testDisplayNameReturnsAliasWhenConfigured()
    {
        $entity = new Entity();
        $entity->name = 'User';

        config(['genealabs-laravel-governor.entity-aliases' => [
            'User' => 'Team Member',
        ]]);

        $this->assertSame('Team Member', $entity->displayName());
    }

    public function testDisplayNameReturnsOriginalNameWhenNoAliasConfigured()
    {
        $entity = new Entity();
        $entity->name = 'Author';

        config(['genealabs-laravel-governor.entity-aliases' => [
            'User' => 'Team Member',
        ]]);

        $this->assertSame('Author', $entity->displayName());
    }

    public function testDisplayNameReturnsOriginalNameWhenAliasConfigIsEmpty()
    {
        $entity = new Entity();
        $entity->name = 'Post';

        config(['genealabs-laravel-governor.entity-aliases' => []]);

        $this->assertSame('Post', $entity->displayName());
    }

    public function testDisplayNameReturnsOriginalNameWhenAliasConfigIsMissing()
    {
        $entity = new Entity();
        $entity->name = 'Comment';

        config(['genealabs-laravel-governor.entity-aliases' => null]);

        $this->assertSame('Comment', $entity->displayName());
    }

    public function testDisplayNameHandlesPackageEntityNames()
    {
        $entity = new Entity();
        $entity->name = 'Widget (Package Name)';

        config(['genealabs-laravel-governor.entity-aliases' => [
            'Widget (Package Name)' => 'Custom Widget',
        ]]);

        $this->assertSame('Custom Widget', $entity->displayName());
    }

    public function testDefaultConfigHasEmptyEntityAliases()
    {
        $aliases = config('genealabs-laravel-governor.entity-aliases');

        $this->assertIsArray($aliases);
        $this->assertEmpty($aliases);
    }

    public function testMultipleAliasesCanBeConfigured()
    {
        config(['genealabs-laravel-governor.entity-aliases' => [
            'User' => 'Team Member',
            'Author' => 'Content Creator',
            'Post' => 'Article',
        ]]);

        $user = new Entity();
        $user->name = 'User';

        $author = new Entity();
        $author->name = 'Author';

        $post = new Entity();
        $post->name = 'Post';

        $comment = new Entity();
        $comment->name = 'Comment';

        $this->assertSame('Team Member', $user->displayName());
        $this->assertSame('Content Creator', $author->displayName());
        $this->assertSame('Article', $post->displayName());
        $this->assertSame('Comment', $comment->displayName());
    }
}
