<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Nova\Cards;

use Laravel\Nova\Card;

class TeamSwitcher extends Card
{
    public $width = '1/3';

    public function component(): string
    {
        return 'team-switcher';
    }
}
