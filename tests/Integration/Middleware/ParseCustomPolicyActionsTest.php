<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Tests\Integration\Middleware;

use GeneaLabs\LaravelGovernor\Http\Middleware\ParseCustomPolicyActions;
use GeneaLabs\LaravelGovernor\Tests\Fixtures\User;
use GeneaLabs\LaravelGovernor\Tests\UnitTestCase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ParseCustomPolicyActionsTest extends UnitTestCase
{
    public function testMiddlewarePassesRequestThrough()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $middleware = new ParseCustomPolicyActions();
        $request = Request::create('/test', 'GET');

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK');
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }
}
