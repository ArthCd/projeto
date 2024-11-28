<?php
// Configuração do banco de dados
$con = new PDO("mysql:host=localhost;dbname=banco;charset=utf8", "root", "");
$con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Criação das tabelas, se necessário
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

// Processamento de requisições do frontend
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? null;

    if ($action === 'importar') {
        // Importar da API
        $urlAgentes = "https://valorant-api.com/v1/agents";
        $urlMapas = "https://valorant-api.com/v1/maps";

        // Importar agentes
        $agentes = json_decode(file_get_contents($urlAgentes), true)['data'];
        $stmtAgentes = $con->prepare("INSERT IGNORE INTO agentes (nome, descricao, imagem) VALUES (:nome, :descricao, :imagem)");
        foreach ($agentes as $agente) {
            $stmtAgentes->execute([
                ':nome' => $agente['displayName'],
                ':descricao' => $agente['description'],
                ':imagem' => $agente['fullPortrait']
            ]);
        }

        // Importar mapas
        $mapas = json_decode(file_get_contents($urlMapas), true)['data'];
        $stmtMapas = $con->prepare("INSERT IGNORE INTO mapas (nome, imagem) VALUES (:nome, :imagem)");
        foreach ($mapas as $mapa) {
            $stmtMapas->execute([
                ':nome' => $mapa['displayName'],
                ':imagem' => $mapa['splash']
            ]);
        }

        echo json_encode(['message' => 'Dados importados com sucesso!']);
        exit;
    } elseif ($action === 'listar') {
        // Listar agentes ou mapas
        $type = $_POST['type'] ?? null;
        $search = $_POST['search'] ?? '';

        // Definir a consulta SQL e parâmetros de acordo com a busca
        if ($search) {
            $query = "SELECT * FROM $type WHERE nome LIKE :search";
        } else {
            $query = "SELECT * FROM $type";
        }

        $stmt = $con->prepare($query);
        if ($search) {
            $stmt->execute([':search' => '%' . $search . '%']);
        } else {
            $stmt->execute(); // Executar sem parâmetros
        }

        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    } elseif ($action === 'atualizar') {
        // Atualizar nome
        $id = $_POST['id'] ?? null;
        $novoNome = $_POST['novo_nome'] ?? null;
        $tabela = $_POST['tabela'] ?? null;
        if ($id && $novoNome && $tabela) {
            $stmt = $con->prepare("UPDATE $tabela SET nome = :novo_nome WHERE id = :id");
            $stmt->execute([':novo_nome' => $novoNome, ':id' => $id]);
            echo json_encode(['message' => 'Registro atualizado com sucesso!']);
            exit;
        }
    } elseif ($action === 'excluir') {
        // Excluir registro
        $id = $_POST['id'] ?? null;
        $tabela = $_POST['tabela'] ?? null;
        if ($id && $tabela) {
            $stmt = $con->prepare("DELETE FROM $tabela WHERE id = :id");
            $stmt->execute([':id' => $id]);
            echo json_encode(['message' => 'Registro excluído com sucesso!']);
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciamento Valorant</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h1>Gerenciamento Valorant</h1>
        <button id="importar" class="btn btn-primary mb-4">Importar Dados da API</button>
        
        <!-- Campo de busca -->
        <input type="text" id="busca" placeholder="Buscar..." class="form-control mb-3">

        <h2>Agentes</h2>
        <div id="agentes" class="mb-5"></div>

        <h2>Mapas</h2>
        <div id="mapas"></div>
    </div>

    <script>
        const fetchDados = async (type, containerId, search = '') => {
            const container = document.getElementById(containerId);
            container.innerHTML = '<p>Carregando...</p>';

            const response = await fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ action: 'listar', type, search })
            });
            const data = await response.json();

            if (!data.length) {
                container.innerHTML = '<p>Nenhum registro encontrado.</p>';
                return;
            }

            container.innerHTML = data.map(item => `
                <div class="card mb-3">
                    <div class="card-body">
                        <h5>${item.nome}</h5>
                        <p>${item.descricao || 'Sem descrição'}</p>
                        <img src="${item.imagem}" alt="${item.nome}" style="max-width: 100%; height: auto;">
                        <button class="btn btn-danger" onclick="excluir(${item.id}, '${type}')">Excluir</button>
                        <button class="btn btn-warning" onclick="atualizar(${item.id}, '${type}')">Atualizar Nome</button>
                    </div>
                </div>
            `).join('');
        };

        const excluir = async (id, tabela) => {
            if (confirm('Tem certeza que deseja excluir?')) {
                const response = await fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ action: 'excluir', id, tabela })
                });
                const data = await response.json();
                alert(data.message);
                fetchDados(tabela, tabela);
            }
        };

        const atualizar = async (id, tabela) => {
            const novoNome = prompt('Digite o novo nome:');
            if (novoNome) {
                const response = await fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ action: 'atualizar', id, novo_nome: novoNome, tabela })
                });
                const data = await response.json();
                alert(data.message);
                fetchDados(tabela, tabela);
            }
        };

        document.getElementById('importar').addEventListener('click', async () => {
            await fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ action: 'importar' })
            });
            fetchDados('agentes', 'agentes');
            fetchDados('mapas', 'mapas');
        });

        document.getElementById('busca').addEventListener('input', (event) => {
            const search = event.target.value;
            fetchDados('agentes', 'agentes', search);
            fetchDados('mapas', 'mapas', search);
        });

        // Carregar dados iniciais
        fetchDados('agentes', 'agentes');
        fetchDados('mapas', 'mapas');
    </script>
</body>
</html>
