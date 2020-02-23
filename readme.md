## PosixRPC for PHP by _[hisorange](https://hisorange.me)_

[![Latest Stable Version](https://poser.pugx.org/hisorange/posix-rpc/v/stable)](https://packagist.org/packages/hisorange/posix-rpc)
[![Build Status](https://travis-ci.org/hisorange/posix-rpc.svg?branch=stable)](https://travis-ci.org/hisorange/posix-rpc)
[![Coverage Status](https://coveralls.io/repos/github/hisorange/posix-rpc/badge.svg)](https://coveralls.io/github/hisorange/posix-rpc)
[![Total Downloads](https://poser.pugx.org/hisorange/posix-rpc/downloads)](https://packagist.org/packages/hisorange/posix-rpc)
[![License](https://poser.pugx.org/hisorange/posix-rpc/license)](https://packagist.org/packages/hisorange/posix-rpc)

Easy to use library to handle the **IPC** (Inter Process Communication) for You!
It provides a messaging interface for publish / subscribe pattern, and also supports safe **RPC** (Remote Procedure Calls).
That is not all! With an intuitive syntax You can call handlers **synchronously** or **asynchronously** with a non-blocking transport layer.

But wait, isn't Posix queues are small and slow?!
Nope! This library can push gigabyte sized messages through the queue, and can easily reach 30k messages per second ^.^

Let's get started!

### How to install

---

```sh
composer require hisorange/posix-rpc
```

Yep, it's ready to be used by You! ^.^

### How to use:

---

No need for big setup, just initialize a node with a unique name:

```php
use hisorange\PosixRPC\Node;
use hisorange\PosixRPC\Contract\IMessage;

$main = new Node('main');
$worker = new Node('worker');

// Do the "hard" job here ^.^
$worker->respond->sum(function(IMessage $message) {
  return array_sum($message->getPayload());
});

// Yep, You can scale your app this easily!
$twelve = $main->request->sum([10, 2]);

// Also, You can simply publish messages which does not expect an answear!
$main->publish('log.error', 'Database connection refused!');

// And listen on them in Your lazy process
$worker->subscribe('log.error', function(IMessage $message) {
  take_action($message->getPayload());
});
```

### Async / Sync calls

---

By default every message sent async, and You could pool the transport for messages.
But if You wana sequence a logic and Your process can block execution while waiting for a message,
then You can use the await call, this will pool the transport until the response is arrived.

```php
$worker->respond('sum', function($request) {
  return my_array_sum($request);
});


// Will pool the transport until the response is arrived.
$response = $main->request('sum', [10, 2]);
```

Async syntax

```php
$worker->respond('sum', 'array_sum');

$main->request('sum', [10, 2], function($response) {
  assert($response, 12);
});


// Call the pool, when Your process is free to receive messages.
$main->tick();
```

### Fluent calls

To make the usage more programer friendly, the package supports fluent syntax.

```php
// Would be funny, right?
$worker->respond->calculator->sum('array_sum');

// Other machine.
$six = $main->request->calculator->sum([1, 2, 3]);
```
