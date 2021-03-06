#!/usr/bin/env php
<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use Icicle\Coroutine;
use Icicle\Loop;
use Icicle\Socket\Server\Server;
use Icicle\Socket\Server\DefaultServerFactory;
use Icicle\Socket\Socket;

// Connect using `nc localhost 60000`.

$coroutine = Coroutine\create(function (Server $server) {
    $sockets = new SplObjectStorage();
    
    $handler = Coroutine\wrap(function (Socket $socket) use (&$sockets) {
        $sockets->attach($socket);
        $name = $socket->getRemoteAddress() . ':' . $socket->getRemotePort();

        try {
            foreach ($sockets as $stream) {
                if ($socket !== $stream) {
                    yield from $stream->write("{$name} connected.\n");
                }
            }

            yield from $socket->write("Welcome {$name}!\n");
            
            while ($socket->isReadable()) {
                $data = trim(yield from $socket->read());
                
                if ("/exit" === $data) {
                    yield from $socket->end("Goodbye!\n");
                } elseif ('' !== $data) {
                    $message = "{$name}: {$data}\n";
                    foreach ($sockets as $stream) {
                        if ($socket !== $stream) {
                            yield from $stream->write($message);
                        }
                    }
                }
            }
        } catch (Exception $exception) {
            $socket->close();
        }

        $sockets->detach($socket);
        foreach ($sockets as $stream) {
            yield from $stream->write("{$name} disconnected.\n");
        }
    });
    
    while ($server->isOpen()) {
        $handler(yield from $server->accept());
    }
}, (new DefaultServerFactory())->create('127.0.0.1', 60000));

$coroutine->done();

Loop\run();

