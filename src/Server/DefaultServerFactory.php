<?php

/*
 * This file is part of the socket package for Icicle, a library for writing asynchronous code in PHP.
 *
 * @copyright 2014-2015 Aaron Piotrowski. All rights reserved.
 * @license MIT See the LICENSE file that was distributed with this source code for more information.
 */

namespace Icicle\Socket\Server;

use Icicle\Exception\InvalidArgumentError;
use Icicle\Socket;
use Icicle\Socket\Exception\FailureException;

class DefaultServerFactory implements ServerFactory
{
    const DEFAULT_BACKLOG = SOMAXCONN;

    // Verify peer should normally be off on the server side.
    const DEFAULT_VERIFY_PEER = false;
    const DEFAULT_ALLOW_SELF_SIGNED = false;
    const DEFAULT_VERIFY_DEPTH = 10;

    /**
     * {@inheritdoc}
     */
    public function create(string $host, int $port = null, array $options = []): Server
    {
        $protocol = (string) $options['protocol'] ?? (null === $port ? 'unix' : 'tcp');
        $queue = (int) $options['backlog'] ?? self::DEFAULT_BACKLOG;
        $pem = (string) $options['pem'] ?? null;
        $passphrase = (string) $options['passphrase'] ?? null;
        $name = (string) $options['name'] ?? null;

        $verify = (string) $options['verify_peer'] ?? self::DEFAULT_VERIFY_PEER;
        $allowSelfSigned = (bool) $options['allow_self_signed'] ?? self::DEFAULT_ALLOW_SELF_SIGNED;
        $verifyDepth = (int) $options['verify_depth'] ?? self::DEFAULT_VERIFY_DEPTH;

        $context = [];
        
        $context['socket'] = [];
        $context['socket']['bindto'] = Socket\makeName($host, $port);
        $context['socket']['backlog'] = $queue;

        $context['socket']['so_reuseaddr'] = (bool) $options['reuseaddr'] ?? false;
        $context['socket']['so_reuseport'] = (bool) $options['reuseport'] ?? false;
        
        if (null !== $pem) {
            if (!file_exists($pem)) {
                throw new InvalidArgumentError('No file found at given PEM path.');
            }
            
            $context['ssl'] = [];

            $context['ssl']['verify_peer'] = $verify;
            $context['ssl']['verify_peer_name'] = $verify;
            $context['ssl']['allow_self_signed'] = $allowSelfSigned;
            $context['ssl']['verify_depth'] = $verifyDepth;

            $context['ssl']['local_cert'] = $pem;
            $context['ssl']['disable_compression'] = true;

            $context['ssl']['SNI_enabled'] = true;
            $context['ssl']['SNI_server_name'] = $name;
            $context['ssl']['peer_name'] = $name;
            
            if (null !== $passphrase) {
                $context['ssl']['passphrase'] = $passphrase;
            }
        }
        
        $context = stream_context_create($context);
        
        $uri = Socket\makeUri($protocol, $host, $port);
        // Error reporting suppressed since stream_socket_server() emits an E_WARNING on failure (checked below).
        $socket = @stream_socket_server($uri, $errno, $errstr, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN, $context);
        
        if (!$socket || $errno) {
            throw new FailureException(sprintf('Could not create server %s: Errno: %d; %s', $uri, $errno, $errstr));
        }
        
        return new BasicServer($socket);
    }
}
