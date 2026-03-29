<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Relations;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use LogicException;

class TeamMembersRelation extends BelongsToMany
{
    public function detach($ids = null, $touch = true)
    {
        $team = $this->getParent();
        $ownerId = $team->governor_owned_by;

        if ($ids === null) {
            if ($this->newPivotQuery()->where('user_id', $ownerId)->exists()) {
                throw new LogicException(
                    "The team owner cannot be removed from their own team."
                );
            }
        } else {
            $ids = $this->parseIds($ids);

            foreach ($ids as $id) {
                if ($id == $ownerId) {
                    throw new LogicException(
                        "The team owner cannot be removed from their own team."
                    );
                }
            }
        }

        return parent::detach($ids, $touch);
    }
}
