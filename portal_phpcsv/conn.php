<?php
date_default_timezone_set('America/Sao_Paulo');

$localServidor = "localhost";
$user = "root";
$senha = "";
$banco = "banco";

$conn = mysqli_connect($localServidor,$user,$senha,$banco);

// Verificar a conexão
if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}