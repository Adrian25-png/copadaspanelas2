<?php
session_start();
// Verifique se o usuário é administrador aqui

require_once '../../config/conexao.php';
require_once '../../actions/funcoes/classificar.php';

$pdo = conectar(); // inicializa a conexão PDO

// Exemplo de execução da classificação (pode estar dentro do POST de um form)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['classificar'])) {
    classificarOitavasDeFinal($pdo);
    classificarQuartasDeFinal($pdo);
    classificarSemifinais($pdo);
    classificarFinal($pdo);

    $_SESSION['success_message'] = "Classificação realizada com sucesso!";
    header("Location: adicionar_dados_finais.php");
    exit;
}

if (!isset($_SESSION['admin_id'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}

include("../../actions/cadastro_adm/session_check.php");

$isAdmin = isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';

require_once __DIR__ . '/../../config/conexao.php';
$pdo = conectar(); // Agora $pdo contém o objeto PDO



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


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['fase_final'])) {
    $nova_fase_final = $_POST['fase_final'];
    $stmt = $pdo->prepare("UPDATE configuracoes SET fase_final = ? WHERE id = (SELECT MAX(id) FROM configuracoes)");
    $stmt->execute([$nova_fase_final]);
    atualizarFases($nova_fase_final);
}

$stmt = $pdo->prepare("SELECT fase_final FROM configuracoes ORDER BY id DESC LIMIT 1");
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if ($row) {
    $fase_final = $row['fase_final'];
} else {
    header('Location: adicionar_grupo.php');
    exit;
}

$tabela_confrontos = "";
switch ($fase_final) {
    case 'oitavas': $tabela_confrontos = "oitavas_de_final_confrontos"; break;
    case 'quartas': $tabela_confrontos = "quartas_de_final_confrontos"; break;
    case 'semifinais': $tabela_confrontos = "semifinais_confrontos"; break;
    case 'final': $tabela_confrontos = "final_confrontos"; break;
    default: die("Fase final desconhecida.");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['atualizar_individual'])) {
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

$sql_confrontos = "SELECT * FROM $tabela_confrontos";
$result_confrontos = $pdo->query($sql_confrontos);

function obterNomeTime($id_time) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT nome FROM times WHERE id = ?");
    $stmt->execute([$id_time]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row['nome'];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atualizar Confrontos</title>
    <link rel="stylesheet" href="../../../public/css/adm/adicionar_dados_finais.css">
    <link rel="stylesheet" href="../../../public/css/cssfooter.css">
    <link rel="stylesheet" href="../../../public/css/adm/header_adm.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">

</head>
<body>
<?php include "header_adm.php";?>
<div class="main fade-in">
 <h1 id="dynamic-text">Atualizar Confrontos para a Fase de <?php echo ucfirst($fase_final); ?></h1>

<script>
    // JavaScript - script.js
    document.addEventListener('DOMContentLoaded', () => {
        // Efeito de digitação para o título
        const textElement = document.getElementById('dynamic-text');
        const text = textElement.textContent;
        textElement.textContent = '';

        let index = 0;
        const typingSpeed = 20; // Aumente ou diminua a velocidade da digitação

        function typeLetter() {
            if (index < text.length) {
                textElement.textContent += text.charAt(index);
                index++;
                setTimeout(typeLetter, typingSpeed);
            }
        }

        typeLetter();
    });
</script>

    <div class="form-container">
        <form method="post" action="">
            
            <button id="butao" type="submit" name="classificar">Classificar fases finais</button>
            
            <?php if (isset($_SESSION['success_message'])): ?>
                <p class="success-message"><?php echo $_SESSION['success_message']; ?></p>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>

        </form>

        <!-- Formulário para selecionar a fase final -->
        <form method="post" action="">
            <label>Selecionar Fase final:</label>
            <select id="fase_final" name="fase_final" onchange="this.form.submit()">
                <option value="oitavas" <?php if ($fase_final == 'oitavas') echo 'selected'; ?>>Oitavas de Final</option>
                <option value="quartas" <?php if ($fase_final == 'quartas') echo 'selected'; ?>>Quartas de Final</option>
                <option value="semifinais" <?php if ($fase_final == 'semifinais') echo 'selected'; ?>>Semifinais</option>
                <option value="final" <?php if ($fase_final == 'final') echo 'selected'; ?>>Final</option>
            </select>
        </form>

        <!-- Formulário para atualizar os confrontos -->
        <form method="post" action="">
            <?php if ($result_confrontos && $result_confrontos->rowCount() > 0): ?>
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
                    <?php while ($row_confrontos = $result_confrontos->fetch(PDO::FETCH_ASSOC)) { 
                        $nome_timeA = obterNomeTime($row_confrontos['timeA_id']);
                        $nome_timeB = obterNomeTime($row_confrontos['timeB_id']);
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($nome_timeA); ?></td>
                        <td>
                            <form method="post" action="">
                                <input type="number" name="gols_marcados_timeA" min="0" value="<?php echo htmlspecialchars($row_confrontos['gols_marcados_timeA']); ?>" required>
                        </td>
                        <td>vs</td>
                        <td>
                            <input type="number" name="gols_marcados_timeB" min="0" value="<?php echo htmlspecialchars($row_confrontos['gols_marcados_timeB']); ?>" required>
                        </td>
                        <td><?php echo htmlspecialchars($nome_timeB); ?></td>
                        <td>
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($row_confrontos['id']); ?>">
                            <button id="butao" type="submit" name="atualizar_individual">Atualizar</button>
                            </form>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
            <?php else: ?>
            <p>Não existem times classificados para a fase.</p>
            <?php endif; ?>
        </form>

    </div>
    <div class="form-container">
        <!-- Formulário para classificar os confrontos -->
        <form id="classificacao-form" method="post" action="../../actions/funcoes/classificar.php" target="result_frame">
            <h3>Deseja classificar os times para essa fase final?</h3>
            <p>Selecione uma opção:</p>
            <label>
                <input type="radio" name="opcao" value="sim" required>
                Sim, aperte o botão Classificar;
            </label>
            <label>
                <input type="radio" name="opcao" value="nao" required>
                Não, aperte o botão Classificar;
            </label>
            <button id="butao" type="submit" name="classificar">Classificar</button>
        </form>
        <!-- Frame para redirecionamento após classificação -->
        <iframe name="result_frame" style="display:none;"></iframe>
    </div>

</div>
<script>
    document.getElementById('classificacao-form').addEventListener('submit', function(event) {
        var selecionado = document.querySelector('input[name="opcao"]:checked');
        
        if (selecionado && selecionado.value === 'sim') {
            // Redireciona para a mesma página após a classificação
            event.preventDefault(); // Previne o envio padrão
            var form = this;
            fetch(form.action, {
                method: form.method,
                body: new FormData(form)
            }).then(response => {
                if (response.ok) {
                    window.location.href = window.location.href; 
                }
            }).catch(error => {
                console.error('Erro ao classificar:', error);
            });
        }
    });
</script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    document.querySelectorAll('.fade-in').forEach(function(el, i) {
        setTimeout(() => el.classList.add('visible'), i * 20);
    });
});
</script>
<?php require_once '../footer.php' ?>
</body>
</html>
