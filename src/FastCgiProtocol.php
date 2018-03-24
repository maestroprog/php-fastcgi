<?php

namespace Maestroprog\PhpFpm;

use Esockets\Base\AbstractProtocol;
use Esockets\Base\CallbackEventListener;
use Protocol\FCGI\FrameParser;
use Protocol\FCGI\Record;
use Protocol\FCGI\Record\EndRequest;
use Protocol\FCGI\Record\Stdout;

class FastCgiProtocol extends AbstractProtocol
{
    /**
     * @return mixed
     * @throws \Esockets\Base\Exception\ReadException
     */
    public function returnRead()
    {
        $buffer = '';
        do {
            $data = $this->provider->read($this->provider->getReadBufferSize(), false);
            if (null === $data && empty($buffer)) {
                return null;
            }
            $buffer .= $data;
        } while (!FrameParser::hasFrame($buffer));

        $frames = [];
        do {
            $frames[] = FrameParser::parseFrame($buffer);
        } while (strlen($buffer));

        return $frames;
    }

    /**
     * @inheritdoc
     */
    public function onReceive(callable $callback): CallbackEventListener
    {
        return $this->eventReceive->attachCallbackListener($callback);
    }

    /**
     * @param $data
     * @return bool
     * @throws \Esockets\Base\Exception\SendException
     */
    public function send($data): bool
    {
        if ($data instanceof FastCgiResponse) {
            /** @var Record[] $frames */
            static $frames = [];
            if (empty($frames)) {
                $frames[] = new Stdout();
                $frames[] = new Stdout();
                $frames[] = new EndRequest();
            }
            $headers = [];
            foreach ($data->getHeaders() as $header => $value) {
                $headers[] = "{$header}: {$value}";
            }
            $headers = implode("\r\n", $headers);
            $frames[0]->setContentData($headers . "\r\n\r\n" . $data->getBody());

            array_walk($frames, function (Record $record) use ($data) {
                $record->setRequestId($data->getRequest()->getRequest()->getRequestId());
            });

            $data = implode('', $frames);
        }

        return $this->provider->send($data);
    }
}
