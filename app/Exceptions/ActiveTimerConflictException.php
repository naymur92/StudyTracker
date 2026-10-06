<?php

namespace App\Exceptions;

use App\Models\StudyBlockSession;
use RuntimeException;

/** Starting a block while another block's timer is active (409). */
class ActiveTimerConflictException extends RuntimeException
{
    public function __construct(public readonly ?StudyBlockSession $active)
    {
        $block = $active?->block;
        $name = $block
            ? $block->slotLabel().' on '.$block->block_date->format('D j M')
            : 'another block';

        parent::__construct("Another block's timer is active: {$name}. Stop or discard it first.");
    }
}
