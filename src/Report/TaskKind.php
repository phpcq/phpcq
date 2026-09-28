<?php

declare(strict_types=1);

namespace Phpcq\Runner\Report;

/**
 * The kind of task a task report belongs to.
 */
enum TaskKind: string
{
    case Diagnostic = 'diagnostic';
    case Fix        = 'fix';
}
