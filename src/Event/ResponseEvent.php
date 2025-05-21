<?php
declare(strict_types=1);

namespace MonkeysLegion\Events\Event;

use Psr\Http\Message\ResponseInterface;

final readonly class ResponseEvent
{
    public function __construct(public ResponseInterface $response) {}
}