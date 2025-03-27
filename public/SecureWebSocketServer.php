<?php
    require __DIR__ . '/../vendor/autoload.php';
    
    use Ratchet\Http\HttpServer;
    use Ratchet\Server\IoServer;
    use Ratchet\WebSocket\WsServer;
    use Ratchet\MessageComponentInterface;
    use Ratchet\ConnectionInterface;
    use React\Socket\SocketServer;
    use React\Socket\SecureServer;
    use React\EventLoop\StreamSelectLoop;
    use Ratchet\Client\Connector as WebSocketClientFactory;
    use GuzzleHttp\Client;
    use Saulo\Youtubeconnectorphp\Utils\Functions;
    use React\Socket\Connector as ReactConnector;
    use React\EventLoop\LoopInterface;
    use React\EventLoop\Loop;
    
    class SecureWebSocketServer implements MessageComponentInterface {
        protected $clients;
        protected $ytmSocket;
        protected $ytmClient;
        protected $loop;
        protected $ytmIp;
        protected $tokens = [];
        
        public function __construct($loop) {
            $this->clients = new \SplObjectStorage;
            $this->loop = $loop;
        }
        
        public function onOpen(ConnectionInterface $conn): void {
            // Captura o token e o IP enviados via `Sec-WebSocket-Protocol`
            $authData = $conn->httpRequest->getHeader('Sec-WebSocket-Protocol');
            
            if (!empty($authData)) {
                parse_str(str_replace(',', '&', $authData[0]), $decodedAuth);
                $token = $decodedAuth['token'] ?? 'default_token';
                $this->ytmIp = $decodedAuth['ip'] ?? '0.0.0.0';
            } else {
                $token = 'default_token';
                $this->ytmIp = '0.0.0.0';
            }
            
            echo "🟢 Cliente conectado ({$conn->resourceId}) com token: $token e IP: {$this->ytmIp}\n";
            
            $this->clients->attach($conn);
            
            // Conectar ao YTMD Companion apenas se ainda não tivermos uma conexão ativa
            if (!$this->ytmSocket) {
                $this->connectToYTMD($token);
            }
        }
        
        public function onMessage(ConnectionInterface $conn, $msg) {
            echo "📩 Mensagem recebida do cliente: $msg\n";
            
            if ($this->ytmSocket) {
                $this->ytmSocket->send($msg);
            }
        }
        
        public function onClose(ConnectionInterface $conn) {
            unset($this->tokens[$conn->resourceId]);
            $this->clients->detach($conn);
            echo "🔴 Cliente desconectado ({$conn->resourceId})\n";
        }
        
        public function onError(ConnectionInterface $conn, \Exception $e) {
            echo "❌ Erro: " . $e->getMessage() . "\n";
            $conn->close();
        }
        
        private function discoverYTMD(): ?string {
            if (!isset($_SESSION["ipytmd"])) {
                $ipHostname = Functions::findYTMDServer();
                if (is_array($ipHostname)) {
                    return $ipHostname["ip"];
                } else {
                    return null;
                }
                
            }
            return $_SESSION["ipytmd"];
        }
        
        private function connectToYTMD($token) {
            $reactConnector = new ReactConnector($this->loop);
            $wsClient = new WebSocketClientFactory($this->loop, $reactConnector);
            
            if ($this->ytmIp) {
                $url = "ws://{$this->ytmIp}:9863/api/v1/realtime";
                
                $wsClient($url, [], ['Authorization' => json_encode(['token' => $token])])
                    ->then(function(Ratchet\Client\WebSocket $conn) {
                        echo "✅ Conectado ao YTMD Companion em {$this->ytmIp}!\n";
                        $this->ytmSocket = $conn;
                        
                        $conn->on('message', function($msg) {
                            echo "📡 Mensagem do YTMD: $msg\n";
                            foreach ($this->clients as $client) {
                                $client->send($msg);
                            }
                        });
                        
                        $conn->on('close', function() {
                            echo "🔴 Desconectado do YTMD Companion! Tentando reconectar...\n";
                            $this->ytmSocket = null;
                            $this->reconnectToYTMD();
                        });
                        
                    })->otherwise(function ($err) {
                        echo "❌ Erro ao conectar ao YTMD: " . $err->getMessage() . "\n";
                        $this->reconnectToYTMD();
                    });
            } else {
                echo "❌ Erro ao conectar ao YTMD! IP Não encontrado\n";
            }
        }
        
        private function reconnectToYTMD() {
            $this->loop->addTimer(5, function() {
                echo "🔄 Tentando reconectar ao YTMD...\n";
                $this->connectToYTMD($this->tokens[array_key_first($this->tokens)] ?? "default_token");
            });
        }
    }

// Criar o loop de eventos corretamente
    $loop = Loop::get();
    $secureWebSocket = new SecureWebSocketServer($loop);
    
    $context = [
        'local_cert' => '/var/www/certificados/fullchain.pem',
        'local_pk' => '/var/www/certificados/privkey.pem',
        'allow_self_signed' => false,
        'verify_peer' => true
    ];

// Criando servidor WebSocket seguro
    $webSocketServer = new IoServer(
        new HttpServer(
            new WsServer($secureWebSocket)
        ),
        new SecureServer(
            new SocketServer('0.0.0.0:4430', [], $loop),
            $loop,
            $context
        ),
        $loop
    );
    
    echo "🚀 Servidor WebSocket Seguro rodando em wss://youtubeconnect.app.br:4430\n";

// 🔥 Mantendo o loop ativo mesmo se ocorrerem erros
    $loop->futureTick(function() use ($loop) {
        echo "🔄 Loop de eventos WebSocket rodando continuamente...\n";
    });
    
    $loop->run();
