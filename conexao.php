<?php
// Dados de conexão com o banco
$servidor = "localhost"; // Servidor do banco (XAMPP usa localhost)
$usuario = "root"; // Usuário padrão no XAMPP é 'root'
$senha = ""; // Senha geralmente é vazia no XAMPP
$banco = "biblioteca_virtual"; // Nome do banco que você criou
// Criando a conexão
$conexao = new mysqli($servidor, $usuario, $senha, $banco);
// Verificando a conexão
if ($conexao->connect_error) {
    die("Erro na conexão: " . $conexao->connect_error);
} else {
    // Apenas para teste — pode remover em produção
    // echo "Conexão realizada com sucesso!";
}
