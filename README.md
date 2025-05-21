# MonkeysLegion • Event Dispatcher (PSR-14)

A **zero-dependency**, PSR-14-compatible event-bus for MonkeysLegion
projects.  
It ships with:

| Package                                  | Purpose                                       |
|------------------------------------------|-----------------------------------------------|
| `MonkeysLegion\Events\ListenerProvider`  | Keeps listeners, supports **priority & once** |
| `MonkeysLegion\Events\EventDispatcher`   | Tiny dispatcher that just calls the listeners |

Because the code is only a few hundred lines you can drop it in, skim it,
and _understand_ every line – no black-box magic ✨.

---

## 📦 Installation

```bash
composer require monkeyscloud/monkeyslegion-events
```

(If you’re developing inside the monkeyslegion-components mono-repo,
the namespace is already autoloaded.)

## 🚀 Quick-start

```php
use MonkeysLegion\Events\{EventDispatcher, ListenerProvider};

// (1) A dumb event DTO
final class RequestEvent
{
    public function __construct(
        public readonly \Psr\Http\Message\ServerRequestInterface $request
    ) {}
}

// (2) Register listeners
$provider = new ListenerProvider();
$provider->add(
    RequestEvent::class,
    fn (RequestEvent $e) => $logger->info(
        $e->request->getMethod().' '.$e->request->getUri()
    ),
    priority: 50                // higher = earlier
);

// (3) Dispatch somewhere in a middleware / controller
$dispatcher = new EventDispatcher($provider);
$dispatcher->dispatch(new RequestEvent($request));
```

## 🛠 API cheatsheet
```php
$provider = new ListenerProvider();

/* register ------------------------------------------------------------ */
$provider->add(OrderPlaced::class, $listener, priority: 10);
$provider->once(UserCreated::class, $listener);          // removed after 1st call

/* remove / clear ------------------------------------------------------ */
$id = $provider->add(SomeEvent::class, $cb);
$provider->remove($id);
$provider->clear();                                      // tests & hot-reload

/* dispatch ------------------------------------------------------------ */
(new EventDispatcher($provider))->dispatch($event);
```

### The provider respects:
- Priority – higher numeric value runs first (20 before 0 before -10)
- FIFO inside the same priority bucket (registration order)
- Inheritance – listeners registered for FooInterface kick in when FooImpl is dispatched (is_a() check)
- One-shot listeners via once()

## ♻️ Integration with the DI container
```php
use Psr\EventDispatcher\EventDispatcherInterface;
use MonkeysLegion\Events\{ListenerProvider, EventDispatcher};

return [
    ListenerProvider::class        => fn () => new ListenerProvider(),
    EventDispatcherInterface::class => fn ($c) => new EventDispatcher(
        $c->get(ListenerProvider::class)
    ),

    // register a listener at wiring-time
    App\Listener\AuditLogger::class => function ($c) {
        $cb = [$c->get(Psr\Log\LoggerInterface::class), 'info'];
        $c->get(ListenerProvider::class)
           ->add(App\Events\UserDeleted::class, $cb, 5);
        return new App\Listener\AuditLogger();   // if you need an object
    },
];
```

Now any service can simply type-hint EventDispatcherInterface and call dispatch() – swapping the implementation is one line in the container.

## 🧑‍💻 Contributing
- Fork & clone
- composer install
- Run the test-suite: composer test
- Send a PR – thanks! ❤️