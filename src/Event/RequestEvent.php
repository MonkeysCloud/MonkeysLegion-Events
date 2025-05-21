<?php
declare(strict_types=1);

namespace MonkeysLegion\Events\Event;

use Psr\Http\Message\ServerRequestInterface;

final readonly class RequestEvent
{
    public function __construct(public ServerRequestInterface $request) {}
}