document.addEventListener('DOMContentLoaded', event => {
    // Toggle the side navigation
    const sidebarToggle = document.body.querySelector('#menu-toggle');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', event => {
            event.preventDefault();
            document.body.querySelector('#wrapper').classList.toggle('toggled');
        });
    }
});

// Notificações
document.addEventListener("DOMContentLoaded", function() {

    const tituloOriginal = document.title;

    function atualizarTitulo(count) {
        if (count > 0) {
            document.title = "(" + count + ") " + tituloOriginal;
        } else {
            document.title = tituloOriginal;
        }
    }

    function loadNotif() {
        let notifCount = document.getElementById("notif-count");
        let notifList  = document.getElementById("notif-list");
        if (!notifCount || !notifList) return;

        fetch("/api/notificacoes_api.php")
        .then(res => res.json())
        .then(data => {
            let count = data.filter(n => !n.lida).length;

            // Badge no sino
            notifCount.innerText = count > 0 ? count : "";

            // Contador no título da aba (estilo Teams)
            atualizarTitulo(count);

            // Lista do dropdown
            let html = "";
            if (data.length === 0) {
                html = "<div class='text-center small text-muted py-2'>Nenhuma notificação</div>";
            }
            data.forEach(n => {
                let bg = n.lida ? "" : "bg-light";
                html += `<a href="#" class="dropdown-item border-bottom pb-2 pt-2 ${bg}" onclick="marcarLida(${n.id}, event)">
                    <div class="small fw-bold">${n.tipo}</div>
                    <div class="small text-wrap" style="white-space: normal;">${n.mensagem}</div>
                    <div class="text-muted" style="font-size:0.7rem">${new Date(n.criado_em).toLocaleString()}</div>
                </a>`;
            });
            notifList.innerHTML = html;
        })
        .catch(() => {
            // Silencia erros de rede — não quebra a UI
        });
    }

    window.marcarLida = function(id, e) {
        e.preventDefault();
        fetch("/api/notificacoes_api.php?ler=" + id).then(() => loadNotif());
    };

    loadNotif();
    setInterval(loadNotif, 30000);
});