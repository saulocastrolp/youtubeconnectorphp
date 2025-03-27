<?php
    $token = filter_input(INPUT_GET, 'token', FILTER_DEFAULT);
    if (!$token) {
        die('Token inválido ou não fornecido.');
    }
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <title>Recuperar Senha -  YouTube Connect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link href="style.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="favicon.png">

    <script src="https://cdn.socket.io/4.5.4/socket.io.min.js"></script>
</head>
<body>
<div class="container">
    <img src="logo.png" alt="Logomarca" title="YouTube Music Connect" class="logo img-fluid"/>
    <br/>
    <h1> YouTube Music Connect </h1>
    <br/>
    <div class="card recover-password-container" id="recover-password-container">
        <form id="reset-password-form">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            <div class="mb-3">
                <label for="password" class="form-label">Senha</label>
                <input type="password" class="form-control" id="password" name="password" placeholder="Insira sua senha!" required>
            </div>
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirmação de Senha</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Insira sua confirmação de senha!" required>
            </div>
            <button class="btn btn-warning" type="submit">Redefinir senha</button>
        </form>
    </div>
</div>

<script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://kit.fontawesome.com/d186725ce3.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script src="script.js"></script>
</body>
</html>
