<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}

include("../../actions/cadastro_adm/session_check.php");

$isAdmin = isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';

include '../../config/conexao.php';
$pdo = conectar(); // Conexão PDO
?>

<!DOCTYPE html>
<html>
<head>
    <title>Adicionar Grupo</title>
    <link rel="stylesheet" href="../../../public/css/adm/header_adm.css">
    <link rel="stylesheet" href="../../../public/css/adm/adicionar_grupo.css">
    <link rel="stylesheet" href="../../../public/css/cssfooter.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
<?php require_once 'header_adm.php'; ?>
<div class="main">
    <div class="form-container fade-in">
        <h1>Adicionar Grupo</h1>
        <form id="formConfiguracao" method="post" action="">
            <fieldset>
                <legend>Configuração dos Grupos:</legend>
                <label for="equipesPorGrupo">Número de equipes por grupo (máx 16):</label>
                <input type="number" id="equipesPorGrupo" name="equipesPorGrupo" min="1" max="16" required><br>
                <label for="numeroGrupos">Número de grupos (máx 18):</label>
                <input type="number" id="numeroGrupos" name="numeroGrupos" min="1" max="18" required><br>
                <label for="faseFinal">Fase Final:</label>
                <select id="faseFinal" name="faseFinal" required>
                    <option value="oitavas">Oitavas de Final</option>
                    <option value="quartas">Quartas de Final</option>
                </select><br>
                <button type="submit">Calcular e Criar Grupos</button>
            </fieldset>
        </form>
        <div id="mensagem">
            <?php
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $equipesPorGrupo = intval($_POST['equipesPorGrupo']);
                $numeroGrupos = intval($_POST['numeroGrupos']);
                $faseFinal = $_POST['faseFinal'];

                $MAX_EQUIPES_POR_GRUPO = 16;
                $MAX_GRUPOS = 18;
                $MIN_TIMES_OITAVAS = 16;
                $MIN_TIMES_QUARTAS = 8;

                if ($equipesPorGrupo > $MAX_EQUIPES_POR_GRUPO || $numeroGrupos > $MAX_GRUPOS) {
                    echo "<p class='error-message'>O número máximo de equipes por grupo é 16 e o número máximo de grupos é 18.</p>";
                } else {
                    $totalEquipes = $equipesPorGrupo * $numeroGrupos;

                    if (($faseFinal === 'oitavas' && $totalEquipes < $MIN_TIMES_OITAVAS) || 
                        ($faseFinal === 'quartas' && $totalEquipes < $MIN_TIMES_QUARTAS)) {
                        $minimo = ($faseFinal === 'oitavas') ? $MIN_TIMES_OITAVAS : $MIN_TIMES_QUARTAS;
                        echo "<p class='error-message'>Não é possível criar grupos. O número total de equipes deve ser pelo menos $minimo para a fase final selecionada.</p>";
                    } else {
                        // Condições da fase final
                        $erroDivisao = false;
                        if ($faseFinal === 'oitavas' && ($totalEquipes % 16 !== 0 || ($numeroGrupos * $equipesPorGrupo) % 8 !== 0)) {
                            $erroDivisao = true;
                            echo "<p class='error-message'>Para a fase de oitavas, o total de equipes deve ser múltiplo de 16 e divisão de grupos precisa ser exata.</p>";
                        }
                        if ($faseFinal === 'quartas' && ($totalEquipes % 8 !== 0 || ($numeroGrupos * $equipesPorGrupo) % 2 !== 0)) {
                            $erroDivisao = true;
                            echo "<p class='error-message'>Para a fase de quartas, o total de equipes deve ser múltiplo de 8 e divisão de grupos precisa ser exata.</p>";
                        }

                        if (!$erroDivisao) {
                            try {
                                // Desativa restrições FK temporariamente
                                $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

                                // Limpar tabelas necessárias
                                $tables = [
                                    'jogadores', 'posicoes_jogadores', 'times', 'final', 'quartas_de_final',
                                    'oitavas_de_final', 'semifinais', 'jogos_finais',
                                    'jogos_fase_grupos', 'grupos', 'final_confrontos',
                                    'oitavas_de_final_confrontos', 'quartas_de_final_confrontos', 'semifinais_confrontos'
                                ];

                                foreach ($tables as $table) {
                                    $pdo->exec("TRUNCATE TABLE $table");
                                }

                                $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

                                // Inserir configuração
                                $stmt = $pdo->prepare("REPLACE INTO configuracoes (id, equipes_por_grupo, numero_grupos, fase_final) VALUES (1, ?, ?, ?)");
                                $stmt->execute([$equipesPorGrupo, $numeroGrupos, $faseFinal]);

                                // Limpar grupos
                                $pdo->exec("DELETE FROM grupos");

                                // Inserir novos grupos
                                for ($i = 1; $i <= $numeroGrupos; $i++) {
                                    $nomeGrupo = "Grupo " . chr(64 + $i);
                                    $stmt = $pdo->prepare("INSERT INTO grupos (nome) VALUES (?)");
                                    $stmt->execute([$nomeGrupo]);
                                }

                                echo "<p class='success-message'>Grupos criados com sucesso!</p>";
                            } catch (PDOException $e) {
                                echo "<p class='error-message'>Erro ao criar grupos: " . $e->getMessage() . "</p>";
                            }
                        }
                    }
                }
            }
            ?>
        </div>
    </div>
</div>
<?php require_once '../footer.php'; ?>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        document.querySelectorAll('.fade-in').forEach(function(el, i) {
            setTimeout(() => el.classList.add('visible'), i * 20);
        });
    });
</script>
</body>
</html>