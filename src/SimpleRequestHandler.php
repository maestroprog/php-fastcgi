<?php

namespace Maestroprog\PhpFpm;

class SimpleRequestHandler implements RequestHandlerInterface
{
    public function handle(FastCgiRequest $request): FastCgiResponse
    {
        return new FastCgiResponse(
            $request,
            ['Content-Type' => 'text/plain'],
            'Hello world!'
        );
    }
}
