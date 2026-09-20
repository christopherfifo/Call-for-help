<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Chamados</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <div class="d-flex" id="wrapper">
        <!-- Sidebar -->
        <div class="bg-dark text-white" id="sidebar-wrapper">
            <div class="sidebar-heading text-center py-4 fs-4 fw-bold text-uppercase border-bottom">
                <i class="bi bi-headset"></i> Chamados
            </div>
            <div class="list-group list-group-flush my-3">
                <a href="/dashboard/index.php" class="list-group-item list-group-item-action bg-transparent text-white fw-bold">
                    <i class="bi bi-speedometer2 me-2"></i>Dashboard
                </a>
                <a href="/chamados/" class="list-group-item list-group-item-action bg-transparent text-white fw-bold">
                    <i class="bi bi-ticket-detailed me-2"></i>Chamados
                </a>

                <?php if(isset($_SESSION['usuario_cargo']) && in_array($_SESSION['usuario_cargo'], ['TECNICO', 'ADMINISTRADOR', 'DEV'])): ?>
                <a href="/chamados/novo_informal.php" class="list-group-item list-group-item-action bg-transparent text-white fw-bold">
                    <i class="bi bi-headset me-2"></i>Chamado Informal
                </a>
                <?php endif; ?>

                <?php if(isset($_SESSION['usuario_cargo']) && in_array($_SESSION['usuario_cargo'], ['ADMINISTRADOR', 'DEV'])): ?>
                <a href="/admin/usuarios.php" class="list-group-item list-group-item-action bg-transparent text-white fw-bold">
                    <i class="bi bi-people me-2"></i>Usuários
                </a>
                <a href="/admin/categorias.php" class="list-group-item list-group-item-action bg-transparent text-white fw-bold">
                    <i class="bi bi-tags me-2"></i>Categorias
                </a>
                
                <a href="/admin/pedidos.php" class="list-group-item list-group-item-action bg-transparent text-white fw-bold">
                    <i class="bi bi-inbox me-2"></i>Central de Pedidos
                </a>
                <a href="/admin/solicitacoes_relatorio.php" class="list-group-item list-group-item-action bg-transparent text-white fw-bold">
                    <i class="bi bi-file-earmark-pdf me-2"></i>Relatórios Solicitados
                </a>
                <?php endif; ?>
                
                <?php if(isset($_SESSION['usuario_cargo']) && $_SESSION['usuario_cargo'] === 'DEV'): ?>
                <div class="sidebar-heading text-center py-2 mt-3 fs-6 fw-bold text-uppercase border-bottom border-top border-secondary">
                    Área DEV
                </div>
                <a href="/admin/administradores.php" class="list-group-item list-group-item-action bg-transparent text-warning fw-bold">
                    <i class="bi bi-shield-lock me-2"></i>Administradores
                </a>
                <a href="/admin/logs.php" class="list-group-item list-group-item-action bg-transparent text-warning fw-bold">
                    <i class="bi bi-journal-text me-2"></i>Logs do Sistema
                </a>
                <?php endif; ?>
                
                <a href="/auth/logout.php" class="list-group-item list-group-item-action bg-transparent text-danger fw-bold mt-5">
                    <i class="bi bi-box-arrow-right me-2"></i>Sair
                </a>
            </div>
        </div>
        <!-- /#sidebar-wrapper -->

        <!-- Page Content -->
        <div id="page-content-wrapper">
            <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
                <div class="container-fluid">
                    <button class="btn btn-primary" id="menu-toggle"><i class="bi bi-list"></i></button>
                    
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    
                    <div class="collapse navbar-collapse" id="navbarSupportedContent">
                                                <ul class="navbar-nav ms-auto mt-2 mt-lg-0">
                            <!-- Notificações -->
                            <li class="nav-item dropdown me-3">
                                <a class="nav-link dropdown-toggle" id="navbarNotif" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="bi bi-bell"></i>
                                    <span class="badge bg-danger rounded-pill" id="notif-count"></span>
                                </a>
                                <div class="dropdown-menu dropdown-menu-end p-2" aria-labelledby="navbarNotif" style="width: 300px; max-height: 400px; overflow-y: auto;" id="notif-list">
                                    <div class="text-center small text-muted">Carregando...</div>
                                </div>
                            </li>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle fw-bold" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="bi bi-person-circle me-1"></i> 
                                    <?= htmlspecialchars($_SESSION['usuario_nome'] ?? 'Usuário') ?>
                                </a>
                                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                    <a class="dropdown-item" href="#">Meu Perfil</a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item text-danger" href="/auth/logout.php">Sair</a>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>
            <div class="container-fluid px-4 py-4">
