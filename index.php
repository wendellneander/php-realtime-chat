<?php

use Ratchet\Http\HttpServer;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;

// Make sure composer dependencies have been installed
require __DIR__ . '/vendor/autoload.php';

/**
 * chat.php
 * Send any incoming messages to all connected clients (except sender)
 */
class Chat implements MessageComponentInterface {
    /** @var SplObjectStorage  */
    protected $clients;

    /**
     * SimpleChat constructor.
     */
    public function __construct()
    {
        // Iniciamos a coleção que irá armazenar os clientes conectados
        $this->clients = new \SplObjectStorage;
    }

    /**
     * Evento que será chamado quando um cliente se conectar ao websocket
     *
     * @param ConnectionInterface $conn
     */
    public function onOpen(ConnectionInterface $conn)
    {
        // Adicionando o cliente na coleção
        $this->clients->attach($conn);
        echo "Cliente conectado ({$conn->resourceId})" . PHP_EOL;
    }

    /**
     * Evento que será chamado quando um cliente enviar dados ao websocket
     *
     * @param ConnectionInterface $from
     * @param string $data
     */
    public function onMessage(ConnectionInterface $from, $data)
    {
        // Convertendo os dados recebidos para vetor e adicionando a data
        $data = json_decode($data) ?? new stdClass();
        $data->time = date('H:i - d M, Y');

        // Passando pelos clientes conectados e enviando a mensagem
        // para cada um deles
        foreach ($this->clients as $client) {
            if($from != $client){
                $client->send(json_encode($data));
            }
        }

        echo "Cliente {$from->resourceId} enviou uma mensagem" . PHP_EOL;
    }

    /**
     * Evento que será chamado quando o cliente desconectar do websocket
     *
     * @param ConnectionInterface $conn
     */
    public function onClose(ConnectionInterface $conn)
    {
        // Retirando o cliente da coleção
        $this->clients->detach($conn);
        echo "Cliente {$conn->resourceId} desconectou" . PHP_EOL;
    }

    /**
     * Evento que será chamado caso ocorra algum erro no websocket
     *
     * @param ConnectionInterface $conn
     * @param Exception $e
     */
    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        // Fechando conexão do cliente
        $conn->close();

        echo "Ocorreu um erro: {$e->getMessage()}" . PHP_EOL;
    }
}

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new Chat()
        )
    ),
    8080
);

$server->run();
