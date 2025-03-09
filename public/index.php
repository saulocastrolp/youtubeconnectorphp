<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YouTube Music Connect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link href="style.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="favicon.png">
    <script>
        let API_URL = "https://youtubeconnect.app.br/api";

        async function handleAuthCallback() {
            const urlParams = new URLSearchParams(window.location.search);
            const accessToken = urlParams.get("access_token");

            if (accessToken) {
                console.log("🔹 Token recebido na URL:", accessToken);
                localStorage.setItem("access_token", accessToken);
                window.history.replaceState({}, document.title, "/"); // Remove o token da URL
                getUserInfo();
            } else {
                console.warn("⚠️ Nenhum token encontrado na URL.");
            }
        }



        async function sendCommand(endpoint) {
            const accessToken = localStorage.getItem("access_token");

            if (!accessToken) {
                console.warn("⚠️ Usuário não autenticado. Redirecionando para login...");
                authenticate();
                return;
            }

            try {
                console.log(`📡 Enviando comando: ${endpoint}`);
                const response = await fetch(`${API_URL}/${endpoint}`, {
                    method: "POST",
                    headers: {
                        Authorization: `Bearer ${accessToken}`,
                    },
                });

                const data = await response.json();
                console.log("✅ Resposta do servidor:", data.message);
            } catch (error) {
                console.error("❌ Erro ao enviar comando:", error);
            }
        }


        async function authenticate() {
            window.location.href = `${API_URL}/login`;
        }

        async function getStatus() {
            const accessToken = localStorage.getItem("access_token");

            if (!accessToken) {
                console.warn("⚠️ Token ausente. Status não pode ser carregado.");
                return;
            }

            try {
                const response = await fetch(`${API_URL}/status`, {
                    headers: { Authorization: `Bearer ${accessToken}` }
                });

                const data = await response.json();

                if (data.error) {
                    console.warn("⚠️ Token inválido. Redirecionando para login...");
                    authenticate();
                } else {
                    document.getElementById("music-title").innerText = data.title || "Nenhuma música tocando";
                    document.getElementById("artist-name").innerText = data.channel || "";
                }

                console.log(`🎵 Música atualizada: ${data.title} - ${data.channel}`);
            } catch (error) {
                console.error("❌ Erro ao obter status:", error);
            }
        }

        async function getUserInfo() {
            let accessToken = localStorage.getItem("access_token");
            console.log("🔍 Enviando token para a API:", accessToken);


            if (!accessToken) {
                console.warn("⚠️ Nenhum token armazenado. Usuário não autenticado.");
                return;
            }

            try {
                const response = await fetch(`${API_URL}/user`, {
                    headers: {
                        Authorization: `Bearer ${accessToken}`,
                    },
                });

                const data = await response.json();

                if (data.error) {
                    console.warn("⚠️ Token expirado ou inválido para obter as informações do usuário. Tentando renovar...");

                    // Se o backend retornou um novo token, armazenamos ele no localStorage
                    if (data.new_access_token) {
                        console.log("🔄 Atualizando token de acesso...");
                        localStorage.setItem("access_token", data.new_access_token);
                        return getUserInfo(); // Chama novamente com o novo token
                    }

                    console.warn("🚫 Token inválido para renovar os dados do usuário. Redirecionando para login...");
                    //setTimeout(authenticate, 2000);
                } else {
                    document.getElementById("login-btn").style.display = "none";
                    document.getElementById("user-info").style.display = "flex";
                    document.getElementById("user-name").innerText = data.name;
                    document.getElementById("user-photo").src = data.picture;
                }
            } catch (error) {
                console.error("Erro ao obter dados do usuário:", error);
            }
        }


        async function sync() {
            const accessToken = localStorage.getItem("access_token");

            if (!accessToken) {
                console.warn("⚠️ Usuário não autenticado. Redirecionando para login...");
                authenticate();
                return;
            }

            try {
                console.log("🔄 Sincronizando reprodução...");
                const response = await fetch(`${API_URL}/sync?device=${navigator.userAgent.includes("Mobile") ? "celular" : "computador"}`, {
                    headers: { Authorization: `Bearer ${accessToken}` }
                });

                const data = await response.json();
                console.log("✅ Sincronização:", data.message, "Dispositivo:", data.device);
            } catch (error) {
                console.error("❌ Erro ao sincronizar reprodução:", error);
            }
        }

        async function transferPlayback() {
            const accessToken = localStorage.getItem("access_token");

            if (!accessToken) {
                console.warn("⚠️ Usuário não autenticado. Redirecionando para login...");
                authenticate();
                return;
            }

            try {
                console.log("📲 Transferindo reprodução...");
                const response = await fetch(`${API_URL}/transfer`, {
                    headers: { Authorization: `Bearer ${accessToken}` }
                });

                const data = await response.json();
                console.log("✅ Transferência realizada:", data.message, "Novo dispositivo:", data.device);
            } catch (error) {
                console.error("❌ Erro ao transferir reprodução:", error);
            }
        }

        window.onload = function () {
            handleAuthCallback();
            getUserInfo();
            getStatus();
        };

        setInterval(() => {
            getStatus();
        }, 5000);
    </script>
    <style>
        #user-info {
            display: none;
            align-items: center;
            gap: 10px;
        }
        #user-photo {
            width: 40px;
            height: 40px;
            border-radius: 50%;
        }
    </style>
</head>
<body>
    <div class="container">
        <img src="logo.png" alt="Logomarca" title="YouTube Music Connect" class="logo img-fluid"/>
        <br/>
        <h1> YouTube Music Connect </h1>
        <br/>

        <div id="user-info">
            <img id="user-photo" alt="Foto do Usuário">
            <span id="user-name"></span>
        </div>

        <button class="btn btn-dark" id="login-btn" onclick="authenticate()">🔑 Login</button>
        <br/>
        <div class="container-musica-artista">
            <h2 id="music-title">Nenhuma música tocando...</h2>
            <h3 id="artist-name"></h3>
        </div>
        
    
        <div class="btn-group">
            <button class="btn btn-dark" onclick="sync()">🔄 Sincronizar</button>
            <button class="btn btn-dark" onclick="transferPlayback()">📲 Transferir Reprodução</button>
        </div>
        <hr/>
        <div class="btn-group">
            <button class="btn btn-dark" onclick="sendCommand('play')">▶️ Play</button>
            <button class="btn btn-dark" onclick="sendCommand('pause?videoId=' + currentVideoId)">⏸️ Pause</button>
            <button class="btn btn-dark" onclick="sendCommand('previous?videoId=' + currentVideoId)">⏮️ Anterior</button>
            <button class="btn btn-dark" onclick="sendCommand('next?videoId=' + currentVideoId)">⏭️ Próxima</button>
        </div>
        <hr/>
        <div class="btn-group">
            <button class="btn btn-dark" onclick="sendCommand('like?videoId=' + currentVideoId)">❤️ Curtir</button>
            <button class="btn btn-dark" onclick="sendCommand('dislike?videoId=' + currentVideoId)">👎 Não Curtir</button>
            <button class="btn btn-dark" onclick="sendCommand('shuffle')">🔀 Aleatório</button>
            <button class="btn btn-dark" onclick="sendCommand('repeat?videoId=' + currentVideoId)">🔁 Repetir</button>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
</body>
</html>
