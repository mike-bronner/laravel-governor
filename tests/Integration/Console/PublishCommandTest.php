<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Tests\Integration\Console;

use GeneaLabs\LaravelGovernor\Tests\UnitTestCase;

class PublishCommandTest extends UnitTestCase
{
    public function testPublishConfig()
    {
        $this->artisan('governor:publish', ['--config' => true])
            ->assertExitCode(0);
    }

    public function testPublishViews()
    {
        $this->artisan('governor:publish', ['--views' => true])
            ->assertExitCode(0);
    }

    public function testPublishMigrations()
    {
        $this->artisan('governor:publish', ['--migrations' => true])
            ->assertExitCode(0);
    }

    public function testPublishAssets()
    {
        $this->artisan('governor:publish', ['--assets' => true])
            ->assertExitCode(0);
    }

    public function testPublishWithNoOptions()
    {
        $this->artisan('governor:publish')
            ->assertExitCode(0);
    }
}
