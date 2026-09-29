<?php

declare(strict_types=1);

namespace DvsaLogger\Contract;

interface LoggerInterface
{
    public function debug(string $message, array $context = []): self;

    public function info(string $message, array $context = []): self;

    public function notice(string $message, array $context = []): self;

    public function warn(string $message, array $context = []): self;

    public function error(string $message, array $context = []): self;

    public function crit(string $message, array $context = []): self;

    public function alert(string $message, array $context = []): self;

    public function emerg(string $message, array $context = []): self;
}
