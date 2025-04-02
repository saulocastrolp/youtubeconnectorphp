
<?php
    session_start();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YouTube Music Connect</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link href="style.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="favicon.png">
</head>
<body>
    <div class="container">
        <img src="logo.png" alt="Logomarca" title="YouTube Music Connect" class="logo img-fluid"/>
        <br/>
        <h1> YouTube Music Connect </h1>
        <h3 id="download">Dowload APK: <a class="btn btn-success btn-rounded" href="uploads/apk/youtube_music_connect.apk" title="YouTube Music Connect APK" target="_blank">YouTube Music Connect APK</a></h3>
        <br/>
            <div class="card login-container" id="login-container">
                <h2 class="titulo">Login</h2>
                <form id="login-form">
                    <div class="mb-3">
                        <label for="email_login" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email_login" name="email" placeholder="Insira seu email!" required>
                    </div>
        
        
                    <div class="mb-3">
                        <label for="password_login" class="form-label">Senha</label>
                        <input type="password" class="form-control" id="password_login" name="password" placeholder="Insira sua senha!" required>
                    </div>
    
                    <button class="btn btn-success mb-3" type="submit">Entrar</button>
                </form>

                <button id="google-login" class="btn btn-danger mb-3">Entrar com Google</button>

                <p><a href="#" id="show-recovery">Esqueceu sua senha?</a></p>
                <p>Ainda não possui conta? <a href="#" id="show-register">Cadastre-se</a></p>
            </div>
        
            <div class="card register-container hidden" id="register-container">
                <h2 class="titulo">Registro</h2>
                <form id="register-form" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="name" class="form-label">Nome</label>
                        <input type="text" class="form-control" id="name" name="name" placeholder="Insira seu nome!" required>
                    </div>
                    <div class="mb-3">
                        <label for="email_register" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email_register" name="email" placeholder="Insira seu e-mail!" required>
                    </div>
                    <div class="mb-3">
                        <label for="password_register" class="form-label">Senha</label>
                        <input type="password" class="form-control" id="password_register" name="password" placeholder="Insira sua senha!" required>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirmacao de Senha</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Insira sua confirmção de senha!" required>
                    </div>
                    <div class="mb-3">
                        <label for="foto" class="form-label">Sua Foto</label>
                        <input class="form-control" type="file" name="foto" id="foto" required accept="image/*">
                    </div>
                    <button class="btn btn-primary" type="submit">Registrar</button>
                </form>
                <p>Já possui conta? <a href="#" id="show-login">Faça login</a></p>
            </div>
        
            <div class="card recovery-container hidden" id="recovery-container">
            
            <h2>Recuperar Senha</h2>
            <form id="recovery-form">
                <div class="mb-3">
                    <label for="email_recover" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email_recover" name="email" placeholder="Insira seu e-mail!" required>
                </div>
                <button class="btn-warning" type="submit">Enviar Email de Recuperação</button>
            </form>
        </div>
        
        <div class="card hidden" id="user-info">
            <div class="card-body">
                <img src="user.png" alt="Foto do Usuário" title="Foto do Usuário" class="foto img-fluid" id="user-foto"/>
                <h3 id="user-name">Nenhum Usuário Logado</h3>
                <h6 id="user-email">Nenhum Usuário Logado</h6>
                <a href="#" title="Deslogar" id="deslogar" class="btn btn-danger">Deslogar</a>
            </div>
        </div>

        <br/>
        <div class="container-musica-controllers">
            <div class="container-musica-artista">
                <img id="music-img" src="music_placehollder.png" alt="Foto da Música" title="Foto da Música" class="logo img-fluid"/>
                <h2 id="music-title">Nenhuma música tocando...</h2>
                <h3 id="artist-name"></h3>
            </div>

            <div class="conmtainer-controllers">
                <div class="row">
                    <div class="col-3">
                        <span id="curtirBtn" class="material-icons">thumb_up</span>
                        <span id="deslikeBtn" class="material-icons">thumb_down</span>
                        <span id="previousBtn" class="material-icons" onclick="sendCommand('previous')">skip_previous</span>
                        <span id="PlayPauseBtn" class="material-icons">play_circle</span>
                        <span id="nextBtn" class="material-icons" onclick="sendCommand('next')">skip_next</span>
                        <span id="suffleBtn" class="material-icons">shuffle</span>
                        <span id="repeatBtn" class="material-icons">repeat</span>
                    </div>
                    <div class="col-7">
                        <div class="progress-container" id="progressContainer">
                            <div class="progress-bar" id="progressBar"></div>
                        </div>
                    </div>
                    <div class="col-2">
                        <div class="volume-control">
                            <span id="volumeIcon" class="material-icons volume-icon">volume_up</span>
                            <input type="range" id="volumeSlider" min="0" max="1" step="0.01" value="1">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        


        <!-- Botões para autenticação e sincronização -->
        <?php if (!isset($_SESSION["ipytmd"]) || $_SESSION["ipytmd"] == null) { ?>
        <form id="formIP">
            <div class="mb-3">
                <label for="ip" class="form-label">Endereço de IP do Computador rodando o YouTube Music Desktop</label>
                <input type="text" class="form-control" id="ip" name="ip" placeholder="ex.:192.168.1.1">
                
                <div class="text-center mt-3">
                    <button type="submit" class="btn btn-info">Enviar IP</button>
                </div>
            </div>
        </form>
        <?php } ?>
        <div class="btn-group">
            <button id="request-code" class="btn btn-primary">📩 Solicitar Código</button>
            <button id="authenticate" class="btn btn-success">🔑 Autenticar</button>
            <button id="sync-state" class="btn btn-info">🔄 Sincronizar Tocando...</button>
            <!--button id="ip_search" class="btn btn-success">🔄 Buscar IP do YouTube Music Desktop</button-->
        </div>
        <?php // } ?>
        <hr/>
        <div class="playlists" id="playlists">
            <h2> Playlists </h2>
            <div class="table-responsive">
                <table class="table align-middle table-dark table-striped table-hover table-bordered ">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Playlist</th>
                    </tr>
                    </thead>
                    <tbody>
                    
                    </tbody>
                </table>
            </div>
        </div>
        
    </div>

    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://kit.fontawesome.com/d186725ce3.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <script src="https://cdn.socket.io/4.5.4/socket.io.min.js"></script>
    <script src="script.js"></script>
    
    <?php if (!isset($_SESSION['ipytmd'])) { ?>
            <script>
                document.addEventListener("DOMContentLoaded", () => {
                    //findMetadataServer();
                    //descobrirIPdoCompanion();
                });
            </script>
    <?php } ?>
    
    
   
</body>
</html>
