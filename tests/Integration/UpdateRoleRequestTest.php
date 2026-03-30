<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Tests\Integration;

use GeneaLabs\LaravelGovernor\Http\Requests\UpdateRoleRequest;
use GeneaLabs\LaravelGovernor\Tests\UnitTestCase;
use Illuminate\Support\Facades\Validator;

class UpdateRoleRequestTest extends UnitTestCase
{
    public function testCreatePermissionAcceptsNo(): void
    {
        $request = new UpdateRoleRequest();
        $rules = $request->rules();

        $validator = Validator::make([
            'name' => 'TestRole',
            'permissions' => [
                'Ungrouped' => [
                    'author' => [
                        'create' => 'no',
                    ],
                ],
            ],
        ], $rules);

        $this->assertFalse($validator->fails());
    }

    public function testCreatePermissionAcceptsAny(): void
    {
        $request = new UpdateRoleRequest();
        $rules = $request->rules();

        $validator = Validator::make([
            'name' => 'TestRole',
            'permissions' => [
                'Ungrouped' => [
                    'author' => [
                        'create' => 'any',
                    ],
                ],
            ],
        ], $rules);

        $this->assertFalse($validator->fails());
    }

    public function testCreatePermissionRejectsOwn(): void
    {
        $request = new UpdateRoleRequest();
        $rules = $request->rules();

        $validator = Validator::make([
            'name' => 'TestRole',
            'permissions' => [
                'Ungrouped' => [
                    'author' => [
                        'create' => 'own',
                    ],
                ],
            ],
        ], $rules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('permissions.Ungrouped.author.create', $validator->errors()->toArray());
    }

    public function testCreatePermissionRejectsOther(): void
    {
        $request = new UpdateRoleRequest();
        $rules = $request->rules();

        $validator = Validator::make([
            'name' => 'TestRole',
            'permissions' => [
                'Ungrouped' => [
                    'author' => [
                        'create' => 'other',
                    ],
                ],
            ],
        ], $rules);

        $this->assertTrue($validator->fails());
    }

    public function testNonCreatePermissionsAcceptAllOwnerships(): void
    {
        $request = new UpdateRoleRequest();
        $rules = $request->rules();

        $validator = Validator::make([
            'name' => 'TestRole',
            'permissions' => [
                'Ungrouped' => [
                    'author' => [
                        'view' => 'own',
                        'update' => 'other',
                        'delete' => 'any',
                    ],
                ],
            ],
        ], $rules);

        $this->assertFalse($validator->fails());
    }
}
