<?php

namespace Maestroprog\PhpFpm;

class FastCgiResponse
{
    private $request;
    protected $headers;
    protected $body;

    public function __construct(FastCgiRequest $request, array $headers, string $body = null)
    {
        $this->request = $request;
        $this->headers = $headers;
        $this->body = $body;
    }

    public function getRequest(): FastCgiRequest
    {
        return $this->request;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(string $body)
    {
        $this->body = $body;
    }
}
