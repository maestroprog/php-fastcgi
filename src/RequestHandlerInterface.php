<?php

namespace Maestroprog\PhpFpm;

interface RequestHandlerInterface
{
    public function handle(FastCgiRequest $request): FastCgiResponse;
}
