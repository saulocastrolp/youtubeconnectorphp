<?php
    
    namespace Saulo\Youtubeconnectorphp\Utils;
    
    use Exception;
    use PHPMailer\PHPMailer\PHPMailer;
    use Dotenv\Dotenv;
    
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
        
    }