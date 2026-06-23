<?php
/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Application\Daemon;

use Icinga\Application\Logger;
use Icinga\Data\ConfigObject;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop\LoopInterface;
use React\Http\HttpServer;
use React\Http\Message\Response;
use React\Socket\SocketServer;

class HttpJob implements DaemonJob
{
    /** @var string */
    protected $socketPath;

    /** @var SocketServer */
    protected $socket;

    /** @var HttpServer */
    protected $server;

    /**
     * Create a new HttpJob
     *
     * @param ConfigObject $config
     */
    public function __construct(ConfigObject $config)
    {
        $this->socketPath = $config->get('socket', '/var/run/icingawebd.socket');
    }

    public function attach(LoopInterface $loop): void
    {
        Logger::info('Starting handling of incoming HTTP requests');

        if (file_exists($this->socketPath)) {
            // The SocketServer itself doesn't remove it, for whatever reason, and fails on restart if we don't either
            unlink($this->socketPath);
        }

        $this->socket = new SocketServer('unix://' . $this->socketPath, [], $loop);
        $this->server = new HttpServer($loop, function ($req) {
            return $this->handle($req);
        });
        $this->server->listen($this->socket);
    }

    public function cancel(): void
    {
        Logger::info('Stopping handling of incoming HTTP requests');

        $this->socket->close();
    }

    /**
     * Handle the given HTTP request
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    protected function handle(ServerRequestInterface $request): ResponseInterface
    {
        return Response::plaintext('Hello World!' . PHP_EOL);
    }
}
