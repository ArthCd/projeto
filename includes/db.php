<?php
// Conexão com o banco de dados

    $con = new PDO("mysql:host=localhost;dbname=banco;charset=utf8", "root", "");
  // Criação das tabelas, caso não existam
  $con->exec("
  CREATE TABLE IF NOT EXISTS agentes (
      id INT AUTO_INCREMENT PRIMARY KEY,
      nome VARCHAR(255) NOT NULL UNIQUE,
      descricao TEXT,
      imagem TEXT
  );
  CREATE TABLE IF NOT EXISTS mapas (
      id INT AUTO_INCREMENT PRIMARY KEY,
      nome VARCHAR(255) NOT NULL UNIQUE,
      imagem TEXT
  );
");

?>
