<?php
session_start();

require_once '../../config/conexao.php';
require_once '../../actions/funcoes/classificar.php';

$pdo = conectar();

if (!isset($_SESSION['admin_id'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}

include("../../actions/cadastro_adm/session_check.php");

$isAdmin = isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';

function atualizarFases($fase_selecionada) {
    global $pdo;
    $ordem_fases = ['oitavas', 'quartas', 'semifinais', 'final'];
    $indice_selecionado = array_search($fase_selecionada, $ordem_fases);
    if ($indice_selecionado !== false) {
        foreach ($ordem_fases as $indice => $fase) {
            $status = ($indice < $indice_selecionado) ? true : false;
            $stmt = $pdo->prepare("UPDATE fase_execucao SET executado = ? WHERE fase = ?");
            $stmt->execute([$status, $fase]);
        }
    }
}

// Atualiza fase_final se enviado via POST
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['fase_final'])) {
    $nova_fase_final = $_POST['fase_final'];
    $stmt = $pdo->prepare("UPDATE configuracoes SET fase_final = ? WHERE id = (SELECT MAX(id) FROM configuracoes)");
    $stmt->execute([$nova_fase_final]);
    atualizarFases($nova_fase_final);
}

// Busca fase_final atual
$stmt = $pdo->prepare("SELECT fase_final FROM configuracoes ORDER BY id DESC LIMIT 1");
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$fase_final = $row ? $row['fase_final'] : 'oitavas';

// Define tabela de confrontos conforme fase
switch ($fase_final) {
    case 'oitavas': $tabela_confrontos = "oitavas_de_final_confrontos"; break;
    case 'quartas': $tabela_confrontos = "quartas_de_final_confrontos"; break;
    case 'semifinais': $tabela_confrontos = "semifinais_confrontos"; break;
    case 'final': $tabela_confrontos = "final_confrontos"; break;
    default: die("Fase final desconhecida.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['classificar'])) {
        try {
            classificarOitavasDeFinal($pdo);
            classificarQuartasDeFinal($pdo);
            classificarSemifinais($pdo);
            classificarFinal($pdo);
            $_SESSION['success_message'] = "Classificação realizada com sucesso!";
        } catch (Exception $e) {
            $_SESSION['error_message'] = "Erro ao classificar: " . $e->getMessage();
        }
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    } elseif (isset($_POST['classificar_fase_final'])) {
        try {
            classificarOitavasDeFinal($pdo);
            classificarQuartasDeFinal($pdo);
            classificarSemifinais($pdo);
            classificarFinal($pdo);
            $_SESSION['success_message_fase_final'] = "Classificação desta fase realizada com sucesso!";
        } catch (Exception $e) {
            $_SESSION['error_message_fase_final'] = "Erro ao classificar esta fase: " . $e->getMessage();
        }
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    } elseif (isset($_POST['atualizar_individual'])) {
        $id = $_POST['id'];
        $gols_marcados_timeA = $_POST['gols_marcados_timeA'];
        $gols_marcados_timeB = $_POST['gols_marcados_timeB'];

        if (empty($id) || !is_numeric($id)) {
            $_SESSION['error_message'] = "ID inválido.";
        } else {
            $gols_contra_timeA = $gols_marcados_timeB;
            $gols_contra_timeB = $gols_marcados_timeA;

            if ($gols_marcados_timeA > $gols_marcados_timeB) {
                $resultado_timeA = 'V';
                $resultado_timeB = 'D';
            } elseif ($gols_marcados_timeA < $gols_marcados_timeB) {
                $resultado_timeA = 'D';
                $resultado_timeB = 'V';
            } else {
                $_SESSION['error_message'] = "Empates não são permitidos. Atualização não realizada.";
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }

            try {
                $stmt = $pdo->prepare("UPDATE $tabela_confrontos SET 
                    gols_marcados_timeA = ?, gols_contra_timeB = ?, 
                    gols_marcados_timeB = ?, gols_contra_timeA = ? 
                    WHERE id = ?");
                $stmt->execute([
                    $gols_marcados_timeA, $gols_contra_timeB,
                    $gols_marcados_timeB, $gols_contra_timeA,
                    $id
                ]);

                $_SESSION['success_message'] = "Confronto atualizado com sucesso!";
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            } catch (PDOException $e) {
                $_SESSION['error_message'] = "Erro ao atualizar o confronto: " . $e->getMessage();
            }
        }
    }
}

function obterNomeTime($id_time) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT nome FROM times WHERE id = ?");
    $stmt->execute([$id_time]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? $row['nome'] : "Desconhecido";
}

$sql_confrontos = "SELECT * FROM $tabela_confrontos";
$stmt_confrontos = $pdo->query($sql_confrontos);
$confrontos = $stmt_confrontos->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Atualizar Confrontos</title>
    <link rel="stylesheet" href="../../../public/css/adm/adicionar_dados_finais.css">
    <link rel="stylesheet" href="../../../public/css/cssfooter.css">
    <link rel="stylesheet" href="../../../public/css/adm/header_adm.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
<?php include "header_adm.php"; ?>

<div class="main fade-in">
    <h1>Atualizar Confrontos para a Fase de <?php echo ucfirst($fase_final); ?></h1>

    <div class="form-container">
        <form method="post" action="">
            <button id="butao" type="submit" name="classificar">Classificar Fases Finais</button>
            <?php if (isset($_SESSION['success_message'])): ?>
                <p class="success-message"><?php echo $_SESSION['success_message']; ?></p>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>
            <?php if (isset($_SESSION['error_message'])): ?>
                <p class="error-message"><?php echo $_SESSION['error_message']; ?></p>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>
        </form>

        <form method="post" action="">
            <label>Selecionar Fase Final:</label>
            <select id="fase_final" name="fase_final" onchange="this.form.submit()">
                <option value="oitavas" <?php if ($fase_final == 'oitavas') echo 'selected'; ?>>Oitavas de Final</option>
                <option value="quartas" <?php if ($fase_final == 'quartas') echo 'selected'; ?>>Quartas de Final</option>
                <option value="semifinais" <?php if ($fase_final == 'semifinais') echo 'selected'; ?>>Semifinais</option>
                <option value="final" <?php if ($fase_final == 'final') echo 'selected'; ?>>Final</option>
            </select>
        </form>

        <?php

        // Verificar se o número de grupos é ímpar
        $stmt_num_grupos = $pdo->query("SELECT numero_grupos FROM configuracoes WHERE id = 1");
        $num_grupos_row = $stmt_num_grupos->fetch(PDO::FETCH_ASSOC);
        $numero_grupos_config = $num_grupos_row ? (int)$num_grupos_row['numero_grupos'] : 0;

        $erro_numero_grupos_impar = ($numero_grupos_config % 2 !== 0);
        ?>

        <?php if ($erro_numero_grupos_impar): ?>
            <p class="error-message">
                ⚠ Não é possível gerar confrontos automáticos com um número <strong>ímpar</strong> de grupos (<?php echo $numero_grupos_config; ?> grupo(s)).<br>
                Altere para um número <strong>par</strong> de grupos na configuração para que os confrontos possam ser gerados corretamente.
            </p>
        <?php elseif ($confrontos && count($confrontos) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Time A</th>
                        <th>Gols Time A</th>
                        <th>vs</th>
                        <th>Gols Time B</th>
                        <th>Time B</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($confrontos as $row_confrontos): ?>
                        <tr>
                            <form method="post" action="">
                                <td><?php echo htmlspecialchars(obterNomeTime($row_confrontos['timeA_id'])); ?></td>
                                <td>
                                    <input type="number" name="gols_marcados_timeA" value="<?php echo htmlspecialchars($row_confrontos['gols_marcados_timeA']); ?>" required>
                                </td>
                                <td>vs</td>
                                <td>
                                    <input type="number" name="gols_marcados_timeB" value="<?php echo htmlspecialchars($row_confrontos['gols_marcados_timeB']); ?>" required>
                                </td>
                                <td><?php echo htmlspecialchars(obterNomeTime($row_confrontos['timeB_id'])); ?></td>
                                <td>
                                    <input type="hidden" name="id" value="<?php echo $row_confrontos['id']; ?>">
                                    <button type="submit" name="atualizar_individual">Atualizar</button>
                                </td>
                            </form>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class = "error-message">Não existem confrontos gerados para esta fase. Verifique se a fase anterior foi classificada corretamente.</p>
        <?php endif; ?>


        <form method="post" action="">
            <h3>Deseja classificar os times para essa fase final?</h3>
            <p>Selecione uma opção:</p>
            <label><input type="radio" name="opcao" value="sim" required> Sim</label>
            <label><input type="radio" name="opcao" value="nao" required> Não</label>
            <input type="hidden" name="classificar_fase_final" value="1">
            <button id="butao" type="submit">Classificar</button>
            <?php if (isset($_SESSION['success_message_fase_final'])): ?>
                <p class="success-message"><?php echo $_SESSION['success_message_fase_final']; ?></p>
                <?php unset($_SESSION['success_message_fase_final']); ?>
            <?php endif; ?>
            <?php if (isset($_SESSION['error_message_fase_final'])): ?>
                <p class="error-message"><?php echo $_SESSION['error_message_fase_final']; ?></p>
                <?php unset($_SESSION['error_message_fase_final']); ?>
            <?php endif; ?>
        </form>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        document.querySelectorAll('.fade-in').forEach(function(el, i) {
            setTimeout(() => el.classList.add('visible'), i * 20);
        });
    });
</script>

</body>
</html>