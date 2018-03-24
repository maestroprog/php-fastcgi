<?php

use Esockets\Base\Configurator;
use Esockets\Socket\SocketFactory;
use Maestroprog\PhpFpm\FastCgiProtocol;

return [
    Configurator::CONNECTION_TYPE => Configurator::CONNECTION_TYPE_SOCKET,
    Configurator::CONNECTION_CONFIG => [
        SocketFactory::SOCKET_DOMAIN => AF_INET,
        SocketFactory::SOCKET_PROTOCOL => SOL_TCP,
        SocketFactory::WAIT_INTERVAL => 1000000,
    ],
    Configurator::PROTOCOL_CLASS => FastCgiProtocol::class,
];
