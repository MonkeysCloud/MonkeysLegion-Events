<?php
declare(strict_types=1);

namespace MonkeysLegion\Events;

use Psr\EventDispatcher\ListenerProviderInterface;

/**
 * ListenerProvider with priority and one-shot support.
 *
 *  • `add()`      – register a listener with optional priority.
 *  • `once()`     – listener is invoked at most once, then removed.
 *  • `remove()`   – drop a previously registered listener.
 *  • `clear()`    – wipe everything (useful for tests).
 *
 *  Listeners are returned **stable-sorted**:
 *      higher priority → first
 *      equal priority  → FIFO (registration order)
 */
final class ListenerProvider implements ListenerProviderInterface
{
    /** @var array<string, array<int, array<int, callable>>>  event ⇒ prio ⇒ queue */
    private array $map = [];

    /** @var array<int, array{string,int}>  listenerId ⇒ [event, prio] */
    private array $index = [];

    /* ------------------------------------------------------------------ */
    /*  Register                                                           */
    /* ------------------------------------------------------------------ */

    public function add(string $eventClass, callable $listener, int $priority = 0): int
    {
        $id = spl_object_id((object) $listener);        // unique per closure
        $this->map[$eventClass][$priority][] = $listener;
        $this->index[$id] = [$eventClass, $priority];
        return $id;
    }

    public function once(string $eventClass, callable $listener, int $priority = 0): int
    {
        // wrapper that removes itself after first run
        $provider = $this;
        $wrapper  = function (object $e) use (&$wrapper, $provider, $listener, &$id) {
            $provider->remove($id);
            $listener($e);
        };
        $id = $this->add($eventClass, $wrapper, $priority);
        return $id;
    }

    /* ------------------------------------------------------------------ */
    /*  Remove / clear                                                     */
    /* ------------------------------------------------------------------ */

    public function remove(int $listenerId): void
    {
        if (!isset($this->index[$listenerId])) {
            return;
        }
        [$event, $prio] = $this->index[$listenerId];
        unset($this->index[$listenerId]);

        foreach ($this->map[$event][$prio] as $k => $cb) {
            if (spl_object_id((object) $cb) === $listenerId) {
                unset($this->map[$event][$prio][$k]);
                break;
            }
        }
        // tidy empty arrays
        if ($this->map[$event][$prio] === []) {
            unset($this->map[$event][$prio]);
        }
        if ($this->map[$event] === []) {
            unset($this->map[$event]);
        }
    }

    public function clear(): void
    {
        $this->map   = [];
        $this->index = [];
    }

    /* ------------------------------------------------------------------ */
    /*  PSR-14                                                             */
    /* ------------------------------------------------------------------ */

    public function getListenersForEvent(object $event): iterable
    {
        $type = $event::class;

        // gather listeners for exact class + parents / interfaces
        $buckets = [];

        foreach ($this->map as $fqcn => $prioMap) {
            if ($fqcn === $type || is_a($type, $fqcn, true)) {
                foreach ($prioMap as $prio => $listeners) {
                    $buckets[$prio] ??= [];
                    $buckets[$prio]  = array_merge($buckets[$prio], $listeners);
                }
            }
        }

        if ($buckets === []) {
            return [];                // early exit – nothing registered
        }

        // krsort = higher numeric priority first (10 before 0 before -10…)
        krsort($buckets);
        foreach ($buckets as $queue) {
            // preserve FIFO inside same-priority bucket
            foreach ($queue as $cb) {
                yield $cb;
            }
        }
    }
}