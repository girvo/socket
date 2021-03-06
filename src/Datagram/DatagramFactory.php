<?php

/*
 * This file is part of the socket package for Icicle, a library for writing asynchronous code in PHP.
 *
 * @copyright 2014-2015 Aaron Piotrowski. All rights reserved.
 * @license MIT See the LICENSE file that was distributed with this source code for more information.
 */

namespace Icicle\Socket\Datagram;

interface DatagramFactory
{
    /**
     * @param string $host
     * @param int|null $port
     * @param mixed[] $options
     *
     * @return \Icicle\Socket\Datagram\Datagram
     *
     * @throws \Icicle\Socket\Exception\FailureException If creating the datagram fails.
     */
    public function create(string $host, int $port = null, array $options = []): Datagram;
}
