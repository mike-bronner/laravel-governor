<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Tests\Integration;

use GeneaLabs\LaravelGovernor\Action;
use GeneaLabs\LaravelGovernor\Tests\UnitTestCase;

class ActionTest extends UnitTestCase
{
    protected Action $action;

    public function setUp(): void
    {
        parent::setUp();
        $this->action = new Action();
    }

    public function testCanBeInstantiated()
    {
        $this->assertInstanceOf(Action::class, $this->action);
    }

    public function testHasCorrectTableName()
    {
        $this->assertEquals('governor_actions', $this->action->getTable());
    }

    public function testCanCreateAction()
    {
        $uniqueName = 'test_create_' . uniqid();
        $action = Action::create(['name' => $uniqueName]);
        
        $this->assertDatabaseHas('governor_actions', ['name' => $uniqueName]);
        $this->assertEquals($uniqueName, $action->name);
    }

    public function testCanUpdateAction()
    {
        $originalName = 'test_view_' . uniqid();
        $newName = 'test_edit_' . uniqid();
        
        $action = Action::create(['name' => $originalName]);
        $action->update(['name' => $newName]);
        
        $this->assertDatabaseHas('governor_actions', ['name' => $newName]);
    }

    public function testCanDeleteAction()
    {
        $uniqueName = 'test_delete_' . uniqid();
        $action = Action::create(['name' => $uniqueName]);
        $action->delete();
        
        $this->assertDatabaseMissing('governor_actions', ['name' => $uniqueName]);
    }

    public function testCanRetrieveActionByName()
    {
        $uniqueName = 'test_publish_' . uniqid();
        Action::create(['name' => $uniqueName]);
        
        $action = Action::where('name', $uniqueName)->first();
        
        $this->assertNotNull($action);
        $this->assertEquals($uniqueName, $action->name);
    }

    public function testActionHasTimestamps()
    {
        $uniqueName = 'test_archive_' . uniqid();
        $action = Action::create(['name' => $uniqueName]);
        
        $this->assertNotNull($action->created_at);
        $this->assertNotNull($action->updated_at);
    }
}
