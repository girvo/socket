#!/usr/bin/env php
<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use Icicle\Coroutine\Coroutine;
use Icicle\Loop;
use Icicle\Socket\Datagram\Datagram;
use Icicle\Socket\Datagram\DefaultDatagramFactory;

// Connect using `nc -u localhost 60000`.

$datagram = (new DefaultDatagramFactory())->create('127.0.0.1', 60000);

$generator = function (Datagram $datagram) {
    echo "Echo datagram running on {$datagram->getAddress()}:{$datagram->getPort()}\n";
    
    try {
        while ($datagram->isOpen()) {
            list($address, $port, $data) = yield from $datagram->receive();
            $data = trim($data, "\n");
            yield from $datagram->send($address, $port, "Echo: {$data}\n");
        }
    } catch (Exception $e) {
        echo "Error: {$e->getMessage()}\n";
        $datagram->close();
    }
};

$coroutine = new Coroutine($generator($datagram));

Loop\run();
