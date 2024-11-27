<?php
// Incluir as configurações e funções
include 'includes/db.php'; // Este arquivo deve definir a conexão PDO
include 'includes/functions.php'; // Este arquivo deve conter as funções como 'importarDados', 'atualizarNome', 'excluirDado'

$agentes = [];
$mapas = [];

try {
    // Buscar dados
    $agentes = $con->query("SELECT * FROM agentes")->fetchAll(PDO::FETCH_ASSOC);
    $mapas = $con->query("SELECT * FROM mapas")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Erro ao buscar dados: " . $e->getMessage();
}

// Importação de dados
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['importar_dados'])) {
    importarDados($con);  // A função deve importar dados para as tabelas
    header("Location: {$_SERVER['PHP_SELF']}");
    exit;
}

// Atualização de nomes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['atualizar_nome'])) {
    $id = $_POST['id'];
    $novoNome = $_POST['novo_nome'];
    $tabela = $_POST['tabela'];
    // Verificação simples de segurança
    if (!empty($novoNome)) {
        atualizarNome($con, $id, $novoNome, $tabela);
        header("Location: {$_SERVER['PHP_SELF']}");
        exit;
    } else {
        echo "O novo nome não pode estar vazio!";
    }
}

// Exclusão de dados
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['excluir_dado'])) {
    $id = $_POST['id'];
    $tabela = $_POST['tabela'];
    excluirDado($con, $id, $tabela);  // Função para excluir dados de acordo com a tabela
    header("Location: {$_SERVER['PHP_SELF']}");
    exit;
}

// Limpar banco de dados
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['limpar_banco'])) {
    try {
        // Deletar todos os registros de ambas as tabelas
        $con->exec("DELETE FROM agentes");
        $con->exec("DELETE FROM mapas");

        // Opcional: Resetar os IDs auto incrementados
        $con->exec("ALTER TABLE agentes AUTO_INCREMENT = 1");
        $con->exec("ALTER TABLE mapas AUTO_INCREMENT = 1");

        // Redirecionar para a página atual após a operação
        header("Location: {$_SERVER['PHP_SELF']}");
        exit;
    } catch (PDOException $e) {
        echo "Erro ao limpar o banco de dados: " . $e->getMessage();
    }
}

// Filtragem de dados
$busca = $_GET['busca'] ?? '';
$agentesFiltrados = filtrarDados($agentes, $busca);
$mapasFiltrados = filtrarDados($mapas, $busca);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Valorant - Gerenciar Dados</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h1>Gerenciar Agentes e Mapas</h1>

    <!-- Menu de Busca -->
    <form method="GET" class="mb-4">
        <input type="text" name="busca" class="form-control mb-2" placeholder="Buscar agentes ou mapas..." value="<?= htmlspecialchars($busca) ?>">
        <button type="submit" class="btn btn-primary">Buscar</button>
    </form>

    <!-- Navegação -->
    <a href="#mapas" class="btn btn-info mb-4">Ir para Mapas</a>

    <!-- Botão para importar dados -->
    <form method="POST" class="mb-4">
        <button type="submit" name="importar_dados" class="btn btn-primary">Importar Dados</button>
    </form>

    <!-- Botão para limpar banco de dados -->
    <form method="POST" class="mb-4">
        <button type="submit" name="limpar_banco" class="btn btn-danger">Limpar Banco de Dados</button>
    </form>

    <!-- Exibição de Agentes -->
    <h2>Agentes</h2>
    <div class="row">
        <?php foreach ($agentesFiltrados as $agente): ?>
            <div class="col-md-4 mb-4">
                <div class="card">
                    <img src="<?= htmlspecialchars($agente['imagem']) ?>" class="card-img-top" alt="<?= htmlspecialchars($agente['nome']) ?>">
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($agente['nome']) ?></h5>
                        <p class="card-text"><?= htmlspecialchars($agente['descricao']) ?></p>
                        <form method="POST" class="mb-2">
                            <input type="hidden" name="id" value="<?= $agente['id'] ?>">
                            <input type="hidden" name="tabela" value="agentes">
                            <input type="text" name="novo_nome" placeholder="Novo Nome" class="form-control mb-2" required>
                            <button type="submit" name="atualizar_nome" class="btn btn-warning btn-sm">Atualizar Nome</button>
                        </form>
                        <form method="POST">
                            <input type="hidden" name="id" value="<?= $agente['id'] ?>">
                            <input type="hidden" name="tabela" value="agentes">
                            <button type="submit" name="excluir_dado" class="btn btn-danger btn-sm">Excluir</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Exibição de Mapas -->
    <h2 id="mapas">Mapas</h2>
    <div class="row">
        <?php foreach ($mapasFiltrados as $mapa): ?>
            <div class="col-md-4 mb-4">
                <div class="card">
                    <img src="<?= htmlspecialchars($mapa['imagem']) ?>" class="card-img-top" alt="<?= htmlspecialchars($mapa['nome']) ?>">
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($mapa['nome']) ?></h5>
                        <form method="POST" class="mb-2">
                            <input type="hidden" name="id" value="<?= $mapa['id'] ?>">
                            <input type="hidden" name="tabela" value="mapas">
                            <input type="text" name="novo_nome" placeholder="Novo Nome" class="form-control mb-2" required>
                            <button type="submit" name="atualizar_nome" class="btn btn-warning btn-sm">Atualizar Nome</button>
                        </form>
                        <form method="POST">
                            <input type="hidden" name="id" value="<?= $mapa['id'] ?>">
                            <input type="hidden" name="tabela" value="mapas">
                            <button type="submit" name="excluir_dado" class="btn btn-danger btn-sm">Excluir</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
