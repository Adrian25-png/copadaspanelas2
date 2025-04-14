<?php
// Habilitar a exibição de erros
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Inclui o arquivo de conexão
require 'conexao.php';

// Verifica se a conexão foi estabelecida
if (!isset($pdo)) {
    die("Erro: Conexão com o banco de dados não estabelecida.");
}

// Busca todos os campeonatos
try {
    $sql = "SELECT * FROM campeonatos";
    $stmt = $pdo->query($sql);
    $campeonatos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro ao buscar campeonatos: " . $e->getMessage());
}

// Ativar/Desativar campeonato
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $acao = $_POST['acao'];

    try {
        if ($acao === 'ativar') {
            // Desativa todos os campeonatos
            $pdo->query("UPDATE campeonatos SET ativo = FALSE");

            // Ativa o campeonato selecionado
            $sql = "UPDATE campeonatos SET ativo = TRUE WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['id' => $id]);
        } elseif ($acao === 'desativar') {
            // Desativa o campeonato selecionado
            $sql = "UPDATE campeonatos SET ativo = FALSE WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['id' => $id]);
        }

        // Redireciona para evitar reenvio do formulário
        header("Location: ativar_campeonato.php");
        exit;
    } catch (PDOException $e) {
        die("Erro ao atualizar campeonato: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Ativar/Desativar Campeonato</title>
    <style>
        .mensagem {
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
        }
        .sucesso {
            background-color: #d4edda;
            color: #155724;
        }
        .erro {
            background-color: #f8d7da;
            color: #721c24;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 8px;
            text-align: left;
        }
    </style>
</head>
<body>
    <h1>Ativar/Desativar Campeonato</h1>

    <?php if (isset($erro)): ?>
        <div class="mensagem erro"><?= $erro ?></div>
    <?php endif; ?>

    <?php if (isset($sucesso)): ?>
        <div class="mensagem sucesso"><?= $sucesso ?></div>
    <?php endif; ?>

    <table>
        <tr>
            <th>ID</th>
            <th>Nome</th>
            <th>Status</th>
            <th>Ações</th>
        </tr>
        <?php foreach ($campeonatos as $campeonato): ?>
        <tr>
            <td><?= htmlspecialchars($campeonato['id']) ?></td>
            <td><?= htmlspecialchars($campeonato['nome']) ?></td>
            <td><?= $campeonato['ativo'] ? 'Ativo' : 'Inativo' ?></td>
            <td>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="id" value="<?= $campeonato['id'] ?>">
                    <button type="submit" name="acao" value="ativar">Ativar</button>
                </form>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="id" value="<?= $campeonato['id'] ?>">
                    <button type="submit" name="acao" value="desativar">Desativar</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>