<?php

declare(strict_types=1);

namespace LaravelMise\Enums;

enum CopyResultEnum: string
{
    case Created = 'created';
    case Overwritten = 'overwritten';
    case Skipped = 'skipped';
}
