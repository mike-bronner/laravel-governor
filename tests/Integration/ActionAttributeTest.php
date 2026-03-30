<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Tests\Integration;

use GeneaLabs\LaravelGovernor\Action;
use GeneaLabs\LaravelGovernor\Tests\UnitTestCase;

class ActionAttributeTest extends UnitTestCase
{
    public function testEntityAttributeWithModelClass()
    {
        $name = 'App\\Models\\User:create_' . uniqid();
        $action = Action::create(['name' => $name]);
        
        $this->assertEquals('User', $action->entity);
    }

    public function testEntityAttributeWithoutModelClass()
    {
        $name = 'viewAny_' . uniqid();
        $action = Action::create(['name' => $name]);
        
        $this->assertEquals('', $action->entity);
    }

    public function testModelClassAttributeWithColon()
    {
        $name = 'App\\Models\\User:create_' . uniqid();
        $action = Action::create(['name' => $name]);
        
        $this->assertEquals('App\\Models\\User', $action->model_class);
    }

    public function testModelClassAttributeWithoutColon()
    {
        $name = 'viewAny_' . uniqid();
        $action = Action::create(['name' => $name]);
        
        $this->assertEquals('', $action->model_class);
    }

    public function testActionAttributeWithColon()
    {
        $name = 'App\\Models\\User:create_' . uniqid();
        $action = Action::create(['name' => $name]);
        
        $this->assertEquals('create_' . substr($name, strrpos($name, '_') + 1), $action->action);
    }

    public function testActionAttributeWithoutColon()
    {
        $name = 'viewAny_' . uniqid();
        $action = Action::create(['name' => $name]);
        
        $this->assertEquals($name, $action->action);
    }

    public function testPermissionsRelationship()
    {
        $action = Action::create(['name' => 'test_action_rel_' . uniqid()]);
        
        $this->assertCount(0, $action->permissions);
    }

    public function testEntityAttributeWithMultiWordModelClass()
    {
        $name = 'App\\Models\\BlogPost:update_' . uniqid();
        $action = Action::create(['name' => $name]);
        
        $this->assertEquals('Blog Post', $action->entity);
    }
}
