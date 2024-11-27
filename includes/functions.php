<?php

// Função para importar dados
function importarDados($con) {
    $agentesAPI = file_get_contents('https://valorant-api.com/v1/agents');
    $mapasAPI = file_get_contents('https://valorant-api.com/v1/maps');

    $agentes = json_decode($agentesAPI, true)['data'];
    $mapas = json_decode($mapasAPI, true)['data'];

    // Limpa os dados antigos
    $con->exec("DELETE FROM agentes");
    $con->exec("DELETE FROM mapas");

    // Insere agentes evitando duplicados
    $stmtCheckAgente = $con->prepare("SELECT COUNT(*) FROM agentes WHERE nome = ?");
    $stmtAgente = $con->prepare("INSERT INTO agentes (nome, descricao, imagem) VALUES (?, ?, ?)");
    foreach ($agentes as $agente) {
        if ($agente['isPlayableCharacter']) {
            $stmtCheckAgente->execute([$agente['displayName']]);
            if ($stmtCheckAgente->fetchColumn() == 0) {
                $stmtAgente->execute([ 
                    $agente['displayName'], 
                    $agente['description'], 
                    $agente['fullPortrait'] ?? ''
                ]);
            }
        }
    }

    // Insere mapas evitando duplicados
    $stmtCheckMapa = $con->prepare("SELECT COUNT(*) FROM mapas WHERE nome = ?");
    $stmtMapa = $con->prepare("INSERT INTO mapas (nome, imagem) VALUES (?, ?)");
    foreach ($mapas as $mapa) {
        $stmtCheckMapa->execute([$mapa['displayName']]);
        if ($stmtCheckMapa->fetchColumn() == 0) {
            $stmtMapa->execute([$mapa['displayName'], $mapa['splash']]);
        }
    }
}

// Função para atualizar o nome
function atualizarNome($con, $id, $novoNome, $tabela) {
    $stmtUpdate = $con->prepare("UPDATE $tabela SET nome = ? WHERE id = ?");
    $stmtUpdate->execute([$novoNome, $id]);
}

// Função para excluir dados
function excluirDado($con, $id, $tabela) {
    $stmtDelete = $con->prepare("DELETE FROM $tabela WHERE id = ?");
    $stmtDelete->execute([$id]);
}

// Função para filtrar dados
function filtrarDados($dados, $busca) {
    return array_filter($dados, function ($item) use ($busca) {
        return stripos($item['nome'], $busca) !== false;
    });
}

?>
