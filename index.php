<?php

require_once __DIR__ . '/config/config.php';

if (isset($_SESSION['usuario_id'])) {
    if (isset($_SESSION['primeiro_acesso']) && $_SESSION['primeiro_acesso']) {
        redirect('/auth/primeiro_acesso.php');
    } else {
        redirect('/dashboard/index.php');
    }
} else {
    redirect('/auth/login.php');
}