<?php
    
    namespace Saulo\Youtubeconnectorphp\Utils;
    
    use Exception;
    use GuzzleHttp\Client;
    use GuzzleHttp\Exception\GuzzleException;
    use PHPMailer\PHPMailer\PHPMailer;
    use Dotenv\Dotenv;
    
    session_start();
    
    class Functions {
        
        public static function sendRecoveryEmail($email, $token, $nome) {
            $dotenv = Dotenv::createImmutable(__DIR__ . "/../../");
            $dotenv->load();
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->CharSet = $_ENV["EMAIL_CHARSERT"];
                $mail->Host = $_ENV["EMAIL_HOST"];
                $mail->SMTPAuth = $_ENV["EMAIL_AUTH"];
                $mail->Username = $_ENV["EMAIL_USERNAME"];
                $mail->Password =  $_ENV["EMAIL_PASSWORD"];;
                $mail->SMTPSecure = $_ENV["EMAIL_PROTOCOL"];
                $mail->Port =  $_ENV["EMAIL_PORT"];
                
                $mail->setFrom($_ENV["EMAIL_EMAIL"], $_ENV["EMAIL_FROM_NAME"]);
                $mail->addAddress($email);
                
                $htmlContent = file_get_contents(__DIR__ . '/../../public/email_recuperacao.html');
                $html = str_replace('{{token}}', $token, $htmlContent);
                $html = str_replace('{{nome}}', $nome, $html);
                $mail->isHTML(true);
                
                $mail->Subject = 'Recuperação de senha';
                $mail->Body = $html;
                
                if (!$mail->send()) {
                    print_r("Erro ao enviar email: " . $mail->ErrorInfo);
                }
            } catch (Exception $e) {
                error_log("Erro ao enviar email: " . $mail->ErrorInfo);
            }
        }
        
        /**
         * Retorna o IP local correto utilizado na rede
         */
        public static function getLocalIp(): ?string {
            $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
            socket_connect($socket, '8.8.8.8', 53);
            socket_getsockname($socket, $localIp);
            socket_close($socket);
            
            return $localIp;
        }
        
        /**
         * Descobre o IP do servidor YouTube Music Desktop Companion na rede
         */
        public static function findYTMDServer(): ?array {
            $localIp = self::getLocalIp();
            
            if (!$localIp || str_starts_with($localIp, '127.')) {
                return null;
            }
            
            $network = implode('.', array_slice(explode('.', $localIp), 0, 3));
            $client = new Client(['timeout' => 0.2]);
            
            for ($i = 1; $i <= 254; $i++) {
                $ip = "{$network}.{$i}";
                $url = "http://{$ip}:9863/metadata";
                
                try {
                    $res = $client->get($url);
                    if ($res->getStatusCode() === 200) {
                        $hostName = gethostbyaddr($ip) ?: 'Desconhecido';
                        $_SESSION["ipytmd"] = $ip;
                        $_SESSION["hostnameytmd"] = $hostName;
                        return [ "ip" => $ip, "hostname" => $hostName ];
                    }
                } catch (GuzzleException) {
                    continue;
                }
            }
            
            return null;
        }
        
    }