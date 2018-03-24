<?php

namespace Maestroprog\PhpFpm;

use Protocol\FCGI\Record;

class FastCgiRequest
{
    private $request;
    private $params;
    private $data;
    private $stdin;

    public function __construct(Record ...$records)
    {
        foreach ($records as $record) {
            switch (true) {
                case $record instanceof Record\BeginRequest:
                    $this->request = $record;
                    break;

                case $record instanceof Record\AbortRequest:
                    throw new \RuntimeException(sprintf('Aborted request "%d".', $record->getRequestId()));
//                    break;

                case $record instanceof Record\Params:
                    $this->params = $record->getValues();
                    break;

                case $record instanceof Record\Data:
                    $this->data = http_parse_params($record->getContentLength());
                    break;

                case $record instanceof Record\Stdin:
                    $this->stdin = $record->getContentData();
                    break;

                default:
                    throw new \UnexpectedValueException(sprintf('Unknown record type "%s".', get_class($record)));
            }
        }
    }

    public function getRequest()
    {
        return $this->request;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function getData()
    {
        return $this->data;
    }

    public function getStdin(): string
    {
        return $this->stdin;
    }
}
