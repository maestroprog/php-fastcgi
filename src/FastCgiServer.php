<?php

namespace Maestroprog\PhpFpm;

use Esockets\Base\Configurator;
use Esockets\Client;
use Esockets\Socket\Ipv4Address;
use Protocol\FCGI;
use Protocol\FCGI\Record;

class FastCgiServer
{
    protected $server;
    protected $requestHandler;

    protected $dispatchSignals;
    protected $work;

    /** @var Client[] */
    private $clients = [];
    /** @var Record[][] */
    private $records = [];

    public function __construct(Configurator $configurator, RequestHandlerInterface $requestHandler)
    {
        $this->server = $configurator->makeServer();
        $this->requestHandler = $requestHandler;

        $this->dispatchSignals = extension_loaded('pcntl');
        if ($this->dispatchSignals) {
            pcntl_signal(defined('SIGINT') ? SIGINT : 2, [$this, 'dispatch']);
            pcntl_signal(defined('SIGTERM') ? SIGTERM : 15, [$this, 'dispatch']);
        }

        $this->init();
    }

    public function open(Ipv4Address $address): self
    {
        $this->server->connect($address);

        return $this;
    }

    public function run(): self
    {
        $this->work = true;

        if (pcntl_fork()>0){
            pcntl_fork();
        }

        while ($this->work) {
            $this->server->find();
            if ($this->dispatchSignals) {
                pcntl_signal_dispatch();
            }

            $this->handleRequests();
        }

        return $this;
    }

    public function stop(): self
    {
        $this->server->disconnectAll();
        $this->server->disconnect();

        return $this;
    }

    protected function dispatch(int $signal): void
    {
        $this->work = false;
    }

    protected function init(): void
    {
        $this
            ->server
            ->onFound(function (Client $client): void {
                $id = $client->getConnectionResource()->getId();
                $this->clients[$id] = $client;

                $client
                    ->onReceive(function ($records) use ($id): void {

                        $this->records[$id] = array_merge($this->records[$id] ?? [], $records);
                    });

                $client->onDisconnect(function () use ($id): void {

                    unset($this->records[$id]);
                    unset($this->clients[$id]);
                });
            });
    }

    private function handleRequests(): void
    {
        $requests = [];

        foreach ($this->records as $clientId => $recordList) {
            foreach ($recordList as $record) {
                $requestId = $record->getRequestId();
                $requests[$requestId][] = $record;

                if ($record instanceof Record\Stdin && $record->getContentLength() === 0) {
                    // Последним фреймом в запросе является пустой пакет Stdin.
                    $response = $this->handleRequest(...$requests[$requestId]);

                    $this->handleResponse($clientId, $response);

                    unset($requests[$requestId]);
                }
            }
        }
    }

    private function handleRequest(Record ...$records): FastCgiResponse
    {
        $request = new FastCgiRequest(...$records);

        return $this->requestHandler->handle($request);
    }

    private function handleResponse(int $clientId, FastCgiResponse $response): void
    {
        if (!isset($this->clients[$clientId])) {
            throw new \InvalidArgumentException(sprintf('Invalid response for client "%d", unknown client.', $clientId));
        }

        $this->clients[$clientId]->send($response);

        if (($response->getRequest()->getRequest()->getFlags() & FCGI::KEEP_CONN) === 0) {
            $this->clients[$clientId]->disconnect();
        }
    }
}
