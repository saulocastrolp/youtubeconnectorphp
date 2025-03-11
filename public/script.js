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

    const showLogin = document.getElementById("show-login");
    const showRegister = document.getElementById("show-register");
    const showRecovery = document.getElementById("show-recovery");

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
        google.accounts.id.initialize({
            client_id: "217397841714-ibcn4efhoe57imd1jgt73c7l99dcp8gi.apps.googleusercontent.com",
            callback: handleGoogleLogin
        });

        // Exibir automaticamente o pop-up para login no canto superior direito
        google.accounts.id.prompt();
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
                user_foto.src = data.foto;
                user_name.innerHTML = data.name;
                user_email.innerHTML = data.email;
                swal("Sucesso!", `Bem-vindo ${data.name}`, "success").then(() => {
                    window.location.reload()
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
            document.getElementById("user-name").innerText = data.name;
            document.getElementById("user-email").innerText = data.email;
            document.getElementById("user-foto").src = data.foto || "user.png";
        } else {
            hideElement(user_info);
            showElement(login_container);
            localStorage.removeItem("google_token");
            localStorage.removeItem("ytbmc");
            swal("Erro!", data.error, "error");
        }
    }

    // Opcional: Evento para login manual caso necessário
    if (loginGoogleBtn) {
        loginGoogleBtn.addEventListener("click", function () {
            google.accounts.id.prompt();
        });
    }


    if (localStorage.getItem("ytbmc")) {
        hideElement(login_container);
        showElement(user_info);

    }

    fetchUserData();

});