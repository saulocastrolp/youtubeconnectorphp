<?php
    error_reporting(E_ERROR);
    ini_set('display_errors', "On");
    require __DIR__ . '/../vendor/autoload.php';
    use Firebase\JWT\JWT;
    use Firebase\JWT\Key;
    use GuzzleHttp\Client;
    use GuzzleHttp\Exception\GuzzleException;
    use GuzzleHttp\Exception\RequestException;
    use Saulo\Youtubeconnectorphp\Utils\Functions;
    use Slim\Factory\AppFactory;
    session_start();
    
    
    
    $app = AppFactory::create();
    $app->setBasePath('/api');
    
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . "/../");
    $dotenv->load();
    
    function getConnection() {
        return new PDO("mysql:host={$_ENV["DATABASE_HOST"]};dbname={$_ENV["DATABASE_DBNAME"]};charset=latin1", $_ENV["DATABASE_USER"], $_ENV["DATABASE_PASSWORD"]);
    }
    
    function getHttpClient() {
        return new Client(['base_uri' => $_SESSION["ipytmd"] . ":9863/api/v1/"]);
    }
    
    
    $app->get('/scan-metadata', function ($request, $response) {
        $ip_hostname = Functions::findYTMDServer();
        $response->getBody()->write(json_encode([
            'success' => true,
            'ip' => $ip_hostname["ip"],
            'hostname' => $ip_hostname["hostname"]
        ]));
        
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    });
    

// Google login handler
    $app->post('/login/google', function ($request, $response) {
        $data = $request->getParsedBody();
        $googleToken = $data['googleToken'];
        
        $payload = file_get_contents("https://www.googleapis.com/oauth2/v3/tokeninfo?id_token=" . $googleToken);
        $payload = json_decode($payload);
        
        if (!$payload || empty($payload->email)) {
            $response->getBody()->write(json_encode(['error' => 'Invalid token']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
        
        $pdo = getConnection();
        
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute(['email' => $payload->email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $jwt = \Firebase\JWT\JWT::encode(['email' => $payload->email], $_ENV["SECRET_KEY_JWT"], 'HS256');
        
        if (!$user) {
            
            $stmt = $pdo->prepare("INSERT INTO users (name, email, foto, token_gerar_senha_google, token_recuperacao_senha, token,google_id) VALUES (:name, :email, :foto, :token_gerar_senha_google, :token_recuperacao_senha, :token, :googleid)");
            $tokenRecuperacao = bin2hex(random_bytes(32));
            
            $stmt->execute([
                ':name' => $payload->name,
                ':email' => $payload->email,
                ':foto' => $payload->picture,
                ':token_gerar_senha_google' => $tokenRecuperacao,
                ':token_recuperacao_senha' => $tokenRecuperacao,
                ':token' => $jwt,
                ':googleid' => $payload->sub,
            ]);
            file_put_contents(__DIR__ . "/../public/uploads/" . uniqid() . $payload->picture, file_get_contents($payload->picture));
            Functions::sendRecoveryEmail($payload->email, $tokenRecuperacao,  $payload->name);
            
            $user = [
                'name' => $payload->name,
                'email' => $payload->email,
                'foto' => $payload->picture,
                'token_YTMD' => "teste"
            ];
        }
        
        
        session_start();
        $_SESSION["google_id_token"] = $googleToken;
        $_SESSION["aplication_token"] = $jwt;
        $response->getBody()->write(json_encode([
            'success' => true,
            'token' => $jwt,
            'google_id_token' => $googleToken,
            'user' => [
                'name' => $user['name'],
                'email' => $user['email'],
                'foto' => $user['foto'],
                'token' => $jwt,
                'tokenYtmd' => $user['token_YTMD'],
            ]
        ]));
        
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    });
    
    $app->post('/register', function ($request, $response) {
        $data = $request->getParsedBody();
        $name = $data['name'];
        $email = $data['email'];
        $password = $data['password'];
        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        
        if ($data['password'] !== $data['confirm_password']) {
            $response->getBody()->write(json_encode(['error' => 'Senhas não conferem']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
        
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $response->getBody()->write(json_encode(['error' => 'Email inválido.']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
        
        if (!empty($uploadedFiles['foto'])) {
            $foto = $uploadedFiles['foto'];
            if ($foto->getError() === UPLOAD_ERR_OK) {
                $filename = move_uploaded_file($uploadedFiles['foto']->getFilePath(), __DIR__ . '/uploads/' . $uploadedFiles['foto']->getClientFilename());
            } else {
                $filename = null;
            }
        }
        
        $jwt = JWT::encode(['email' => $email], $_ENV["SECRET_KEY_JWT"], 'HS256');
        $_SESSION["aplication_token"] = $jwt;
        
        $pdo = getConnection();
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, foto, token) VALUES (:name, :email, :password_hash, :foto, : token)");
        $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':password_hash' => $password_hash,
            ':foto' => $filename,
            ':token' => $jwt
        ]);
        
        $response->getBody()->write(json_encode([
            'success'=> true,
            'token' => $jwt,
            'user' => [
                'name' => $name,
                'email' => $email,
                'foto' =>  $filename
            ]
        ]));
        
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    });
    
    $app->post('/login', function ($request, $response) {
        $data = $request->getParsedBody();
        $email = $data['email'];
        $password = $data['password'];
        
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $response->getBody()->write(json_encode(['error' => 'Email inválido.']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
        
        $pdo = getConnection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user || !password_verify($password, $user['password_hash'])) {
            $response->getBody()->write(json_encode(['error' => 'Credenciais inválidas']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }
        
        $jwt = JWT::encode(['email' => $email], $_ENV["SECRET_KEY_JWT"], 'HS256');
        
        $stmt = $pdo->prepare("UPDATE users SET token = :token WHERE email = :email");
        $stmt->execute([
            ':email' => $email,
            ':token' => $jwt,
        ]);
        
        $_SESSION["aplication_token"] = $jwt;
        
        $response->getBody()->write(json_encode([
            'success'=> true,
            'token' => $jwt,
            'user' => [
                'name' => $user['name'],
                'email' => $user['email'],
                'foto' => $user['foto'],
                'tokenYtmd' => $user['token_YTMD']
            ]
        ]));
        
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    });
    
    // Endpoint para solicitar recuperação de senha
    $app->post('/recover-password', function ($request, $response) {
        $data = $request->getParsedBody();
        //var_dump($data);
        if (!isset($data['email'])) {
            $response->getBody()->write(json_encode(['error' => 'Email é obrigatório.']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
        
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $response->getBody()->write(json_encode(['error' => 'Email inválido.']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
        
        $pdo = getConnection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$data['email']]);
        $user = $stmt->fetch();
        
        if (!$user) {
            $response->getBody()->write(json_encode(['error' => 'Email não encontrado.']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }
        
        $token = bin2hex(random_bytes(32));
        $stmt = $pdo->prepare("UPDATE users SET token_recuperacao_senha = ? WHERE email = ?");
        $stmt->execute([$token, $data['email']]);
        
        Functions::sendRecoveryEmail($data['email'], $token, $user['name']);
        
        $response->getBody()->write(json_encode(['success'=> true, 'message' => 'Email de recuperação enviado!']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    });
    
    $app->post('/reset-password', function ($request, $response) {
        $data = $request->getParsedBody();
        if ($data['password'] !== $data['confirm_password']) {
            $response->getBody()->write(json_encode(['error' => 'Senhas não conferem']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
        
        $pdo = getConnection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE token_recuperacao_senha = ?");
        $stmt->execute([$data['token']]);
        $user = $stmt->fetch();
        
        if (!$user) {
            $response->getBody()->write(json_encode(['error' => 'Token inválido ou expirado!']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }
        
        $newPasswordHash = password_hash($data['confirm_password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, token_recuperacao_senha = NULL WHERE id = ?");
        $stmt->execute([$newPasswordHash, $user['id']]);
        
        $response->getBody()->write(json_encode(['success'=> true, 'message' => 'Senha redefinida com sucesso!']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    });
    
    // Endpoint para buscar dados do usuário autenticado
    $app->get('/user', function ($request, $response) {
        $authHeader = $request->getHeaderLine('Authorization');
        if (!$authHeader) {
            $response->getBody()->write(json_encode(['error' => 'Token não fornecido.']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }
        
        $token = str_replace('Bearer ', '', $authHeader);
        try {
            $decoded = JWT::decode($token, new Key($_ENV["SECRET_KEY_JWT"], 'HS256'));
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(['error' => 'Token inválido.']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }
        
        $pdo = getConnection();
        $stmt = $pdo->prepare("SELECT id, name, email, foto, token_YTMD FROM users WHERE email = ?");
        $stmt->execute([$decoded->email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            $response->getBody()->write(json_encode(['error' => 'Usuário não encontrado.']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }
        
        $response->getBody()->write(json_encode(['success'=> true, "user" => $user]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    });
    
    $app->get('/metadata', function ($request, $response) {
        $client = getHttpClient();
        $apiResponse = $client->request('GET', 'metadata');
        $response->getBody()->write($apiResponse->getBody()->getContents());
        return $response->withHeader('Content-Type', 'application/json')->withStatus($apiResponse->getStatusCode());
    });
    
    $app->post('/auth/requestcode', function ($request, $response) {
        try {
            $client = getHttpClient();
            $data = json_decode($request->getBody(), true);
            $apiResponse = $client->request('POST', 'auth/requestcode', [
                'json' => $data
            ]);
            $response->getBody()->write($apiResponse->getBody()->getContents());
            return $response->withHeader('Content-Type', 'application/json')->withStatus($apiResponse->getStatusCode());
        } catch (RequestException $ex) {
            $response->getBody()->write(json_encode(['error' => $ex->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus($ex->getResponse()->getStatusCode());
        }
        
    });
    
    $app->post('/auth/request', function ($request, $response) {
        try {
            $authHeader = $request->getHeaderLine('Authorization');
            $client = getHttpClient();
            $data = json_decode($request->getBody(), true);
            $apiResponse = $client->request('POST', 'auth/request', [
                'json' => $data
            ]);
            $contentAPIResponse = (string)$apiResponse->getBody();
            $contentAPIResponseDecoded = json_decode($contentAPIResponse,true);
            $token = str_replace('Bearer ', '', $authHeader);
            try {
                $decoded = JWT::decode($token, new Key($_ENV["SECRET_KEY_JWT"], 'HS256'));
            } catch (Exception $e) {
                $response->getBody()->write(json_encode(['error' => 'Token inválido.']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
            }
            
            $pdo = getConnection();
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$decoded->email]);
            $user = $stmt->fetch(PDO::FETCH_OBJ);
            
            $stmt = $pdo->prepare("UPDATE users SET token_YTMD = :token WHERE id = :id");
            $stmt->execute([
                ':id' => $user->id,
                ':token' => $contentAPIResponseDecoded["token"],
            ]);
            $_SESSION["ytmd_companion_token"] = $contentAPIResponseDecoded["token"];
            
            $response->getBody()->write($contentAPIResponse);
            return $response->withHeader('Content-Type', 'application/json')->withStatus($apiResponse->getStatusCode());
        } catch (RequestException $ex) {
            $response->getBody()->write(json_encode(['error' => $ex->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus($ex->getResponse()->getStatusCode());
        }
    });
    
    $app->get('/state', function ($request, $response) {
        try {
            $authHeader = $request->getHeaderLine('Authorization');
            if (!$authHeader) {
                $response->getBody()->write(json_encode(['error' => 'Token não fornecido.']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
            }
            $token = str_replace('Bearer ', '', $authHeader);
            
            $client = getHttpClient();
            //$headers = $token ? ['Authorization' => "Bearer $token"] : [];
            $headers = $token ? ['Authorization' => "$token"] : [];
            $apiResponse = $client->request('GET', 'state', ['headers' => $headers]);
            $response->getBody()->write($apiResponse->getBody()->getContents());
            return $response->withHeader('Content-Type', 'application/json')->withStatus($apiResponse->getStatusCode());
        } catch (RequestException $ex) {
            $response->getBody()->write(json_encode(['error' => $ex->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus($ex->getResponse()->getStatusCode());
        }
    });
    
    $app->get('/playlists', function ($request, $response) {
        try {
            $authHeader = $request->getHeaderLine('Authorization');
            if (!$authHeader) {
                $response->getBody()->write(json_encode(['error' => 'Token não fornecido.']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
            }
            $token = str_replace('Bearer ', '', $authHeader);
            
            $client = getHttpClient();
            //$headers = $token ? ['Authorization' => "Bearer $token"] : [];
            $headers = $token ? ['Authorization' => "$token"] : [];
            $apiResponse = $client->request('GET', 'playlists', ['headers' => $headers]);
            $response->getBody()->write($apiResponse->getBody()->getContents());
            return $response->withHeader('Content-Type', 'application/json')->withStatus($apiResponse->getStatusCode());
        } catch (RequestException $ex) {
            $response->getBody()->write(json_encode(['error' => $ex->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus($ex->getResponse()->getStatusCode());
        }
    });
    
    $app->post('/command', function ($request, $response) {
        try {
            $authHeader = $request->getHeaderLine('Authorization');
            if (!$authHeader) {
                $response->getBody()->write(json_encode(['error' => 'Token não fornecido.']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
            }
            $token = str_replace('Bearer ', '', $authHeader);
            
            $client = getHttpClient();
            //$headers = $token ? ['Authorization' => "Bearer $token"] : [];
            $headers = $token ? ['Authorization' => "$token"] : [];
            $data = json_decode($request->getBody()->getContents(), true);
            $apiResponse = $client->request('POST', 'command', [
                'headers' => $headers,
                'json' => $data
            ]);
            $response->getBody()->write(json_encode(array("ok"=> true, "msg" => "Comando enviado com suceso!")));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (RequestException $ex) {
            $response->getBody()->write(json_encode(['error' => $ex->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus($ex->getResponse()->getStatusCode());
        }
    });
    
    
    $app->addBodyParsingMiddleware();
$app->run();