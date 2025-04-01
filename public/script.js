const portaYTMD = 9863;

const showLoadingPg = () => {
    const loader = document.createElement('div');
    loader.id = 'loading-overlay';
    loader.style.position = 'fixed';
    loader.style.top = '0';
    loader.style.left = '0';
    loader.style.width = '100%';
    loader.style.height = '100%';
    loader.style.backgroundColor = 'rgba(0, 0, 0, 0.7)';
    loader.style.display = 'flex';
    loader.style.alignItems = 'center';
    loader.style.justifyContent = 'center';
    loader.style.zIndex = '9999';
    loader.innerHTML = '<div style="color: #fff; font-size: 20px;">Carregando, por favor aguarde...</div>';
    document.body.appendChild(loader);
};

const hideLoadingpg = () => {
    const loader = document.getElementById('loading-overlay');
    if (loader) loader.remove();
};

/*async function findMetadataServer() {
    showLoadingPg();
    try {
        const res = await fetch('/api/scan-metadata');
        const data = await res.json();

        if (data.success && data.ip) {
            console.log(data.ip);
            localStorage.setItem('ip_ytmd', data.ip);
            localStorage.setItem('hostname_ytmd', data.hostname);
            hideLoadingpg();

            //swal("Servidor encontrado!", `IP(s) com servidor ativo: ${data.ips.join(', ')}`, "success");
        } else {
            swal("Servidor não encontrado", "Nenhum servidor respondendo foi localizado na rede.", "error");
            hideLoadingpg();
        }
    } catch (error) {
        swal("Erro", `Falha ao buscar servidor: ${error.message}`, "error");
        hideLoadingpg();
    }
    hideLoadingpg();
}*/

async function sendCommand(command) {
    const token = localStorage.getItem("ytmd_token");
    const ipYTMD = localStorage.getItem("ip_ytmd");
    if (!ipYTMD) {
        swal("Erro!", "Você encontrar o servidor de Conexão primeiro.", "error");
    } else {
        if (!token) {
            //swal("Erro!", "Você precisa estar autenticado para executar comandos.", "error");
            return;
        }

        const [commandName, query] = command.split("?");
        let data = {command: commandName};

        console.info(commandName);
        if (commandName == "shuffle") {
            const aleatorioBtn = document.getElementById("aleatorioBtn");
            console.info(localStorage.getItem('shuffle'));
            if(localStorage.getItem('shuffle') === null || localStorage.getItem('shuffle') === undefined || localStorage.getItem('shuffle') === 'false') {
                if (aleatorioBtn) {
                    aleatorioBtn.classList.remove('btn-dark');
                    aleatorioBtn.classList.add('btn-success');
                }

                localStorage.setItem('shuffle', 'true')
            } else {
                if (aleatorioBtn) {
                    aleatorioBtn.classList.add('btn-dark');
                    aleatorioBtn.classList.remove('btn-success');
                }
                localStorage.setItem('shuffle', 'false')
            }
        }

        if (query) {
            const params = new URLSearchParams(query);
            if (params.has("videoId")) {
                data["data"] = {videoId: params.get("videoId")};
            }
            if (params.has("repeatMode")) {
                data["data"] = params.get("repeatMode");
            }
        }

        try {
            const response = await fetch(`https://${ipYTMD}:9863/api/v1/command`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Authorization": `${token}`
                },
                body: JSON.stringify(data)
            });

            const result = await response;
            if (response) {
                //window.location.reload();
                return null;
                //swal("Sucesso!", "Comando executado com sucesso.", "success");
            } else {
                swal("Erro!", result.error || "Falha ao executar o comando.", "error");
            }
        } catch (error) {
            console.error("Ocorreu um erro ao executar o comando. " , error);
            //swal("Erro!", "Não foi possível conectar ao servidor.", "error");
        }
    }
}



async function descobrirIPdoCompanion() {
    const localIP = await descobrirLocalIP() || "192.168.1.1";
    if (localIP) {
        showLoadingPg();
        const baseRede = localIP.split('.').slice(0, 3).join('.');

        for (let i = 1; i < 255; i++) {
            const ip = `${baseRede}.${i}`;
            const url = `http://${ip}:${portaYTMD}/metadata`;

            try {
                const controller = new AbortController();
                setTimeout(() => controller.abort(), 200); // timeout curto

                const res = await fetch(url, {signal: controller.signal});
                if (res.ok) {
                    const metadata = await res.json();
                    console.log('Companion encontrado em:', ip, metadata);

                    localStorage.setItem('ip_ytmd', ip);
                    localStorage.setItem('hostname_ytmd', metadata.player?.name || 'YTMD');

                    // Envia para o servidor
                    await fetch('/api/registrar-companion', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({
                            ip: ip,
                            hostname: metadata.player?.name || 'YTMD'
                        })
                    });
                    hideLoadingpg();
                    break;
                }
            } catch (e) {
                // Ignora erro
            }
        }

    }
}

async function descobrirLocalIP(timeoutMs = 10000) {
    return new Promise((resolve) => {
        const pc = new RTCPeerConnection({ iceServers: [] });
        pc.createDataChannel('');
        pc.createOffer().then((offer) => pc.setLocalDescription(offer));

        const timeout = setTimeout(() => {
            pc.close();
            resolve(null);
        }, timeoutMs);

        pc.onicecandidate = (ice) => {
            if (ice && ice.candidate && ice.candidate.candidate) {
                const ipRegex = /([0-9]{1,3}(\.[0-9]{1,3}){3})/;
                const match = ipRegex.exec(ice.candidate.candidate);

                if (match && match[1]) {
                    clearTimeout(timeout);
                    const ip = match[1];
                    pc.close();
                    resolve(ip);
                }
            }
        };
    });
}


function convertToYouTubeMusicTime(seconds) {
    const minutes = Math.floor(seconds / 60);
    const remainingSeconds = seconds % 60;
    return `?t=${minutes}m${remainingSeconds}s`;
}

document.addEventListener("DOMContentLoaded", function() {

    const loginForm = document.getElementById("login-form");
    const login_container = document.getElementById("login-container");
    const registerForm = document.getElementById("register-form");
    const register_container = document.getElementById("register-container");
    const recoveryForm = document.getElementById("recovery-form");
    const recovery_container = document.getElementById("recovery-container");
    const resetPasswordForm = document.getElementById("reset-password-form");
    const user_info = document.getElementById("user-info");
    const user_foto = document.getElementById("user-foto");
    const user_name = document.getElementById("user-name");
    const user_email = document.getElementById("user-email");
    const deslogar = document.getElementById("deslogar");
    const loginGoogleBtn = document.getElementById("google-login");

    const formIP = document.getElementById("formIP");

    const showLogin = document.getElementById("show-login");
    const showRegister = document.getElementById("show-register");
    const showRecovery = document.getElementById("show-recovery");

    const token = localStorage.getItem("token");
    const musicImg = document.getElementById("music-img");
    const musicTitle = document.getElementById("music-title");
    const artistName = document.getElementById("artist-name");
    const requestCodeBtn = document.getElementById("request-code");
    const authenticateBtn = document.getElementById("authenticate");
    const syncStateBtn = document.getElementById("sync-state");
    const ipSearchBtn = document.getElementById("ip_search");

    const curtirBtn = document.getElementById("curtirBtn");
    const deslikeBtn = document.getElementById("deslikeBtn");
    const repeatBtn = document.getElementById("repeatBtn");
    const aleatorioBtn = document.getElementById("aleatorioBtn");

    if (curtirBtn) {
        let status = localStorage.getItem("metadata");
        if (status) {
            status = JSON.parse(status);
            console.info(status);

            if(status.video.likeStatus != 2) {
                curtirBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    sendCommand("toggleLike");
                    curtirBtn.setAttribute('disabled', 'disabled');
                    curtirBtn.classList.remove('btn-dark');
                    curtirBtn.classList.add('btn-success');
                    //window.location.reload();
                });
            } else if (status.video.likeStatus == 2) {
                curtirBtn.setAttribute('disabled', 'disabled');
                curtirBtn.classList.remove('btn-dark');
                curtirBtn.classList.add('btn-success');
            }
        }
    }

    if (deslikeBtn) {
        let status = localStorage.getItem("metadata");
        if (status) {
            status = JSON.parse(status);
            if (status.video.likeStatus != 0) {
                deslikeBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    sendCommand("toggleDislike");
                    deslikeBtn.setAttribute('disabled', 'disabled');
                    window.location.reload();
                });
            } else {
                deslikeBtn.setAttribute('disabled', 'disabled');
            }
        }
    }

    if (repeatBtn) {
        let metadata = localStorage.getItem("metadata");
        let mode = 0
        let status = "Sem repetição";
        let html = repeatBtn.innerHTML;

        if (metadata) {
            metadata = JSON.parse(metadata);
            switch (metadata.player.queue.repeatMode) {
                case 0:
                    mode = 1;
                    status = "Única";
                    break;
                case 1:
                    mode = 2;
                    status = "Todos";
                    break;
                case 2:
                    mode = 0;
                    status = "Sem repetição";
                    break;
                default:
                    mode = 0;
            }
            repeatBtn.addEventListener('click', (e) => {
                e.preventDefault();
                let btn = e.currentTarget;
                sendCommand(`repeatMode?repeatMode=${mode}`);
                btn.innerHTML = `${html} ${status}`;
            });

            repeatBtn.innerHTML = `${html} ${status}`;
        }
    }

    const playlistTable = document.querySelector("#playlists table tbody");

    function showLoading(button) {
        button.dataset.originalText = button.innerHTML;
        button.innerHTML = "<span class='spinner-border spinner-border-sm'></span> Aguarde...";
        button.disabled = true;
    }

    function hideLoading(button) {
        button.innerHTML = button.dataset.originalText;
        button.disabled = false;
    }

    const showElement = (element) => {
        element.classList.remove("hidden");
        element.classList.add("visible");
    }

    const hideElement = (element) => {
        element.classList.remove("visible");
        element.classList.add("hidden");
    }

    const switchForm = (formToShow) => {
        [loginForm, registerForm, recoveryForm].forEach(form => hideElement(form.parentElement));
        showElement(formToShow.parentElement);
    }

    // Event listeners para troca de formulários
    showLogin ? showLogin.addEventListener("click", () => switchForm(loginForm)) : null;
    showRegister ? showRegister.addEventListener("click", () => switchForm(registerForm)) : null;
    showRecovery ? showRecovery.addEventListener("click", () => switchForm(recoveryForm)) : null;

    if (formIP) {
        formIP.addEventListener("submit",  async function enviaIPdoCompanion(ev) {
            ev.preventDefault();
            const localIP = formIP?.ip?.value || null;
            if (localIP) {

                try {

                    localStorage.setItem('ip_ytmd', localIP);
                    localStorage.setItem('hostname_ytmd', 'YTMD');

                    // Envia para o servidor
                    await fetch('/api/registrar-companion', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({
                            ip: localIP,
                            hostname: 'YTMD'
                        })
                    }).then(() => {
                        //window.location.reload();
                    });
                } catch (error) {
                    console.error(error);
                }
            }else {
                swal("Error", "Você deve informar o IP onde está rodando o YouTube Music Desktop", "error");
            }

        });
    }

    if (deslogar) {
        deslogar.addEventListener("click", () => {
            hideElement(user_info);
            showElement(login_container);
            localStorage.removeItem("google_token");
            localStorage.removeItem("ytbmc");
        });
    }

    // Função de Login
    if (loginForm) {
        loginForm.addEventListener("submit", async (e) => {
            e.preventDefault();
            let button = e.currentTarget.querySelector('button');
            showLoading(button);
            const formData = new FormData(loginForm);
            const formObject = {};
            formData.forEach((value, key) => { formObject[key] = value; });

            const response = await fetch("/api/login", {
                method: "POST",
                headers: {"Content-Type": "application/json"},
                body: JSON.stringify(formObject)
            });

            const data = await response.json();
            if (response.ok) {
                localStorage.setItem("ytbmc", data.token);
                user_foto.src = data.foto;
                user_name.innerHTML = data.name;
                user_email.innerHTML = data.email;
                hideLoading(button);
                swal("Sucesso", `Bem-vindo ${data.user.name}`, "success").then(() => window.location.reload());
                //window.location.href = "/dashboard.html";
            } else {
                swal("Erro:", data.error, "error");
            }
        });
    }

    // Função de Registro
    if (registerForm) {
        registerForm.addEventListener("submit", async (e) => {
            e.preventDefault();
            let button = e.currentTarget.querySelector('button');
            showLoading(button);
            const formData = new FormData(registerForm);
            const formObject = {};
            formData.forEach((value, key) => { formObject[key] = value; });

            const response = await fetch("/api/register", {
                method: "POST",
                headers: {"Content-Type": "application/json"},
                body: JSON.stringify(formObject)
            });

            const data = await response.json();
            if (response.ok) {
                localStorage.setItem("ytbmc", data.token);
                swal("Sucesso!", data.message, "success");
                hideLoading(button);
                switchForm(loginForm);
            } else {
                swal("Erro:", data.error, "error");
            }
        });
    }

    // Função de Recuperação de Senha
    if (recoveryForm) {
        recoveryForm.addEventListener("submit", async (e) => {
            e.preventDefault();
            let button = e.currentTarget.querySelector('button');
            showLoading(button);
            const formData = new FormData(recoveryForm);
            const formObject = {};
            formData.forEach((value, key) => { formObject[key] = value; });

            const response = await fetch("/api/recover-password", {
                method: "POST",
                headers: {"Content-Type": "application/json"},
                body: JSON.stringify(formObject)
            });

            const data = await response.json();
            if (response.ok) {
                swal("Sucesso", data.message, "success");
                hideLoading(button);
                switchForm(loginForm);
            } else {
                swal("Erro", data.error, "error");
            }
        });
    }

    // Função para resetar a senha
    if (resetPasswordForm) {
        resetPasswordForm.addEventListener("submit", async (e) => {
            e.preventDefault();
            let button = e.currentTarget.querySelector('button');
            showLoading(button);
            const params = new URLSearchParams(window.location.search);
            const token = params.get('token');

            if (!token) {
                swal("Erro!", "Token inválido ou ausente.", "error");
                return;
            }

            const formData = new FormData(resetPasswordForm);
            const jsonObject = {};
            formData.forEach((value, key) => { jsonObject[key] = value; });
            jsonObject['token'] = token;

            const response = await fetch("/api/reset-password", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(jsonObject)
            });

            const data = await response.json();
            if (response.ok) {
                hideLoading(button);
                swal("Sucesso!", data.message, "success").then(() => {
                    window.location.href = "/";
                });
            } else {
                swal("Erro!", data.error, "error");
            }
        });
    }

    // Login com Google
    // Inicialização do Google Sign-In

    let googleTokenArmazenado = localStorage.getItem("google_token");
    if (!googleTokenArmazenado) {
        if (google) {
            google.accounts.id.initialize({
                client_id: "217397841714-ibcn4efhoe57imd1jgt73c7l99dcp8gi.apps.googleusercontent.com",
                callback: handleGoogleLogin
            });

            // Exibir automaticamente o pop-up para login no canto superior direito
            google.accounts.id.prompt();
        }

    }

    async function handleGoogleLogin(response) {
        showLoading(loginGoogleBtn);
        try {
            let googleTokenArmazenado = localStorage.getItem("google_token");
            if (!googleTokenArmazenado) {
                const googleToken = response.credential; // ID Token do Google

                const responseGoogle = await fetch("/api/login/google", {
                    method: "POST",
                    headers: {"Content-Type": "application/json"},
                    body: JSON.stringify({googleToken})
                });

                const data = await responseGoogle.json();
                if (data.error) throw new Error(data.error);

                localStorage.setItem("google_token", data.google_id_token);
                localStorage.setItem("ytbmc", data.token);
                localStorage.setItem("ytmd_token", data.user.tokenYtmd);
                user_foto.src = data.user.foto;
                user_name.innerHTML = data.user.name;
                user_email.innerHTML = data.user.email;
                swal("Sucesso!", `Bem-vindo ${data.user.name}`, "success").then(() => {
                    window.location.reload();
                });
            }
        } catch (error) {
            swal("Erro:", "Erro ao fazer login com Google: " + error.message, "error");
        } finally {
            hideLoading(loginGoogleBtn);
        }
    }

    // Carregar dados do usuário ao carregar a página
    async function fetchUserData() {
        const token = localStorage.getItem("ytbmc");
        if (!token) return;

        const response = await fetch("/api/user", {
            method: "GET",
            headers: { "Authorization": `Bearer ${token}`, "Content-Type": "application/json" }
        });

        const data = await response.json();
        if (response.ok) {
            document.getElementById("user-name").innerText = data.user.name;
            document.getElementById("user-email").innerText = data.user.email;
            document.getElementById("user-foto").src = data.user.foto || "user.png";
            localStorage.setItem("ytmd_token", data.user.token_YTMD)
        } else {
            hideElement(user_info);
            showElement(login_container);
            localStorage.removeItem("google_token");
            localStorage.removeItem("ytbmc");
            //swal("Erro!", data.error, "error");
        }
    }

    function validarIP(ip) {
        const regexIP = /^((25[0-5]|2[0-4]\\d|1?\\d?\\d)(\\.|$)){4}$/;
        return regexIP.test(ip);
    }

    async function requestCode() {
        const response = await fetch("/api/auth/requestcode", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                appId: "youtubeconnector",
                appName: "YouTube Connector",
                appVersion: "1.0.0"
            })
        });

        const data = await response.json();
        if (response.ok) {
            swal("Código Solicitado!", `Use este código para autenticação: ${data.code}`, "info");
            localStorage.setItem("ytmd_auth_code", data.code);
        } else {
            swal("Erro!", data.error, "error");
        }
    }

    async function authenticate() {
        const token = localStorage.getItem("ytbmc");
        if (!token) return;
        const code = localStorage.getItem("ytmd_auth_code");
        if (!code) {
            swal("Erro!", "Solicite um código primeiro.", "error");
            return;
        }

        const response = await fetch("/api/auth/request", {
            method: "POST",
            headers: { "Authorization": `Bearer ${token}`, "Content-Type": "application/json" },
            body: JSON.stringify({
                appId: "youtubeconnector",
                code: code
            })
        });

        const data = await response.json();

        if (response.ok) {
            console.info(data);
            swal("Autenticado!", "Companion Server autenticado com sucesso!", "success");
            localStorage.setItem("ytmd_token", data.token);
        } else {
            swal("Erro!", data.error, "error");
        }
    }

    async function syncState() {
        const companionToken = localStorage.getItem("ytmd_token");
        /*if (!companionToken) {
            swal("Erro!", "Você precisa autenticar primeiro.", "error");
            return;
        }*/

        const ipYTMD = localStorage.getItem("ip_ytmd");
        if (!ipYTMD) {
            swal("Erro!", "Você encontrar o servidor de Conexão primeiro.", "error");
            return;
        } else {
            const response = await fetch(`https://${ipYTMD}:9863/api/v1/state`, {
                method: "GET",
                headers: {"Authorization": `${companionToken}`, "Content-Type": "application/json"}
            });

            const data = await response.json();
            if (response.ok && data.video) {
                localStorage.setItem("metadata", JSON.stringify(data));
                localStorage.setItem("videoId", data?.video?.videoId);
                localStorage.setItem("playlistId", data?.player?.playlistId);
                const video = data?.video;
                const playlistId = data?.player?.playlistId || "";
                const duration = convertToYouTubeMusicTime(video?.durationSeconds);

                musicImg.src = video?.thumbnails?.length > 0 ? video?.thumbnails[video?.thumbnails?.length -1]?.url : "music_placeholder.png";
                musicImg.onclick = () => window.open(`https://music.youtube.com/watch?v=${video?.videoId}&list=${playlistId}&t=${duration}`, '_blank');
                musicTitle.innerHTML = `<a href="https://music.youtube.com/watch?v=${video.videoId}&list=${playlistId}&t=${duration}" target="_blank">${video?.title}</a>`;
                artistName.innerHTML = `<a href="https://music.youtube.com/channel/${video?.channelId}" target="_blank">${video?.author}</a>`;
            } else {
                swal("Erro!", "Nenhuma música tocando no momento. " + data.error, "error");
            }
        }
    }
    async function playlist() {
        const companionToken = localStorage.getItem("ytmd_token");
        /*if (!companionToken) {
            swal("Erro!", "Você precisa autenticar primeiro.", "error");
            return;
        }*/

        const ipYTMD = localStorage.getItem("ip_ytmd");
        if (!ipYTMD) {
            swal("Erro!", "Você encontrar o servidor de Conexão primeiro.", "error");
            return;
        } else {
            const response = await fetch(`https://${ipYTMD}:9863/api/v1/playlists`, {
                method: "GET",
                headers: {"Authorization": `${companionToken}`, "Content-Type": "application/json"}
            });

            const data = await response.json();
            if (response.ok) {
                console.info(playlistTable);
                if (playlistTable) {
                    console.info(data);
                    let html = "";
                    data.forEach(el => {
                        html += `<tr><td><a href="https://music.youtube.com/watch?list=${el.id}">${el.id}</a></td><td><a href="https://music.youtube.com/watch?list=${el.id}">${el.title}</a></td></tr>`;
                    });
                    playlistTable.innerHTML = html;
                }
            } else {
                swal("Erro!", "Nenhuma playlist encontrada. " + data.error, "error");
            }
        }
    }


    if (requestCodeBtn) requestCodeBtn.addEventListener("click", requestCode);
    if (authenticateBtn) authenticateBtn.addEventListener("click", authenticate);
    if (syncStateBtn) syncStateBtn.addEventListener("click", syncState);

    // Opcional: Evento para login manual caso necessário
    if (loginGoogleBtn) {
        loginGoogleBtn.addEventListener("click", function () {
            google.accounts.id.prompt();
        });
    }

    if (ipSearchBtn) {
        ipSearchBtn.addEventListener("click", async (ev) => {
            ev.preventDefault();
            //findMetadataServer();
            //descobrirIPdoCompanion();
        });
    }

    if (localStorage.getItem("ip_ytmd")) {
        if (ipSearchBtn) {
            hideElement(ipSearchBtn);
        }

        if (formIP) {
            hideElement(formIP);
        }
    }
    else {
        //descobrirIPdoCompanion();
    }


    if (localStorage.getItem("ytbmc")) {
        if (login_container) {
            hideElement(login_container);
        }

        if (user_info) {
            showElement(user_info);
        }
    }


    if (localStorage.getItem("ytmd_token")) {
        if (requestCodeBtn) {
            hideElement(requestCodeBtn)
        }

        if (authenticateBtn) {
            hideElement(authenticateBtn)
        }
    }

    if (user_info) {
        fetchUserData();
    }

    if (localStorage.getItem("ip_ytmd") && localStorage.getItem("ytmd_token")) {
        syncState();
        setInterval(() => {
            //syncState();
        }, 50000);

        setTimeout(() => {
            playlist();
            if (suf) {
                console.log(localStorage.getItem('shuffle'));
                if (localStorage.getItem('shuffle') === null || localStorage.getItem('shuffle') === undefined) {
                    aleatorioBtn.classList.add('btn-dark');
                    aleatorioBtn.classList.remove('btn-success');
                    localStorage.setItem('shuffle', "false")
                }
            }
        }, 10000);

    }

    // Conectando-se ao servidor Socket.IO
    const companionToken = localStorage.getItem("ytmd_token");
    const ipYTMD = localStorage.getItem("ip_ytmd");

    if (companionToken && ipYTMD) {
        const socket = io(`https://${ipYTMD}:9863/api/v1/realtime`, {
            transports: ['websocket'],
            auth: {
                token: companionToken
            },
            secure: true
        });

        // Evento de conexão bem-sucedida
        socket.on('connect', () => {
            console.log('✅ Conectado ao servidor WebSocket! ID:', socket.id);

            // Exemplo de envio de dados ao servidor
            socket.emit('mensagem', { conteudo: 'Olá servidor!' });
        });

        // Evento ao receber uma mensagem do servidor
        socket.on('state-update', (data) => {
            //console.log('📩 Status auto Play:', data?.player?.queue?.autoplay);
            //console.log('📩 State Modificado recebida do servidor:', data);
            localStorage.setItem("metadata", JSON.stringify(data));
            localStorage.setItem("videoId", data?.video?.id);
            localStorage.setItem("playlistId", data?.player?.playlistId);
            const video = data?.video;
            const playlistId = data?.player?.playlistId || "";
            const duration = convertToYouTubeMusicTime(video?.durationSeconds);

            musicImg.src = video?.thumbnails?.length > 0 ? video?.thumbnails[video?.thumbnails?.length -1]?.url : "music_placeholder.png";
            musicImg.onclick = () => window.open(`https://music.youtube.com/watch?v=${video?.id}&list=${playlistId}&t=${duration}`, '_blank');
            musicTitle.innerHTML = `<a href="https://music.youtube.com/watch?v=${video?.id}&list=${playlistId}&t=${duration}" target="_blank">${video?.title}</a>`;
            artistName.innerHTML = `<a href="https://music.youtube.com/channel/${video?.channelId}" target="_blank">${video?.author}</a>`;
        });

        // Evento ao receber uma mensagem do servidor
        socket.on('mensagem', (data) => {
            //console.log('📩 Mensagem recebida do servidor:', data);
        });

        // Evento de desconexão
        socket.on('disconnect', (reason) => {
            console.warn('❌ Desconectado:', reason);
        });

        // Evento de erro
        socket.on('connect_error', (error) => {
            console.error('Erro na conexão:', error);
        });
    }


});