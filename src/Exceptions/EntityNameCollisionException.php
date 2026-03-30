<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Exceptions;

use RuntimeException;

class EntityNameCollisionException extends RuntimeException
{
    public function __construct(
        string $entityName,
        string $existingPolicyClass,
        string $newPolicyClass
    ) {
        parent::__construct(
            "Entity name collision detected: \"{$entityName}\" is resolved by both "
            . "\"{$existingPolicyClass}\" and \"{$newPolicyClass}\". "
            . "These policies have the same class name but different fully-qualified class names. "
            . "Rename one of the policy classes to avoid this collision."
        );
    }
}
