<?php

namespace Pho\DHT\Protocol;

class Server
{
    protected $loop, $socket;
    protected $connector;
    protected $router;
    public function __construct(string $ip, int $port, $router)
    {
        $uri = $ip.":".(string) $port;
        $this->router = $router;
        $this->loop = \React\EventLoop\Factory::create();
        $this->socket = new \React\Socket\Server($uri, $this->loop);
        $this->connector = new React\Socket\Connector($this->loop, array(
            'timeout' => 10.0
        ));
        $this->setup();
    }

    protected function handleFindPeers(string $parameter, array $excludes = [])
    {
        if(count($excludes)==0) {
            // add my id
        }
        $hops = $this->router->findPeers($parameter);
        if($hops instanceof PeerInterface)
        {
            return $hops;
        }
        $promises = [];
        foreach($hops as $peer)
        {
            $uri = $peer->ip().":".$peer->port();
            $promises[] = $this->connector->connect($uri)->then(function (\React\Socket\ConnectionInterface $conn) use ($data) {
                $conn->write($data."\n");
                $conn->end();
            });
        }
        $results = Block\awaitAll($promises, $this->loop);
        foreach($results as $result) {
            foreach($result as $peer) {
                $excludes[] = $peer->id();
            }
        }
        foreach($results as $result) {
            foreach($result as $peer) {
                $this->handleFindPeers($peed->id(), $excludes);       
            }
        }
    }

    protected function setup(): void
    {
        $this->socket->on('connection', function (\React\Socket\ConnectionInterface $connection) {
            //$connection->write("Hello " . $connection->getRemoteAddress() . "!\n");
            //$connection->write("Welcome to this amazing server!\n");
            //$connection->write("Here's a tip: don't say anything.\n");
        
            $connection->on('data', function ($data) use ($connection) {
                $data = trim($data);
                $data = explode(" ", $data);
                $command = $data[0];
                $parameter = "";
                if(isset($data[1]));
                    $parameter = $data[1];
                switch($command)
                {
                    case "PING":
                        $connection->write("PONG\n");
                        break;
                    case "FIND_NODE":
                        $x = $this->handleFindPeers($parameter);
                        break;
                    case "FIND_VALUE":

                        break;
                    case "QUIT":
                        $connection->close();
                        break;
                }
            });
        });
    }

    public function run(): void
    {
        $this->loop->run();
    }
}