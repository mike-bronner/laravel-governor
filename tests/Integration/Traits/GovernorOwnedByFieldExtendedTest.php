<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Tests\Integration\Traits;

use GeneaLabs\LaravelGovernor\Tests\Fixtures\Author;
use GeneaLabs\LaravelGovernor\Tests\Fixtures\AuthorWithoutGovernable;
use GeneaLabs\LaravelGovernor\Tests\Fixtures\User;
use GeneaLabs\LaravelGovernor\Tests\UnitTestCase;
use GeneaLabs\LaravelGovernor\Traits\GovernorOwnedByField;

class GovernorOwnedByFieldExtendedTest extends UnitTestCase
{
    use GovernorOwnedByField;

    public function testCreateGovernorOwnedByFieldsReturnsFalseForNonGovernableModel()
    {
        $model = new AuthorWithoutGovernable(['name' => 'Test']);

        $result = $this->createGovernorOwnedByFields($model);

        $this->assertFalse($result);
    }

    public function testCreateGovernorOwnedByFieldsReturnsFalseWhenColumnExists()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $model = new Author(['name' => 'Test']);

        // The column already exists from migrations
        $result = $this->createGovernorOwnedByFields($model);

        $this->assertFalse($result);
    }

    public function testCreateGovernorOwnedByFieldsByPolicyReturnsFalseForUnmappedPolicy()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Create a mock policy that isn't registered
        $policy = new class extends \GeneaLabs\LaravelGovernor\Policies\BasePolicy {
            protected string $entity = 'test';
        };

        $result = $this->createGovernorOwnedByFieldsByPolicy($policy);

        $this->assertFalse($result);
    }

    public function testCreateGovernorOwnedByFieldsByPolicyForRegisteredPolicy()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $policy = new \GeneaLabs\LaravelGovernor\Tests\Fixtures\Policies\Author();

        // Column already exists, so should return false
        $result = $this->createGovernorOwnedByFieldsByPolicy($policy);

        $this->assertFalse($result);
    }
}
