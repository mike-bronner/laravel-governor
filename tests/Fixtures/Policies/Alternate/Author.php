<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Tests\Fixtures\Policies\Alternate;

use GeneaLabs\LaravelGovernor\Policies\BasePolicy;

/**
 * A second Author policy in a different namespace, used to test
 * entity name collision detection. Both this and the primary
 * Author policy resolve to the same entity name "Author".
 */
class Author extends BasePolicy
{
    //
}
