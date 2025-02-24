<?php
require_once 'vendor/autoload.php';
require_once 'data_processing/utils.php';
require_once 'data_processing/metaProcessFile.php';
require_once 'data_processing/processSpreadSheetFileAndSave.php';
require_once 'dbSql/selectPlanilhaUpload.php';
include_once('conn.php');

// Lógica para fazer o download do arquivo
include_once('data_processing/download.php');

$message = metaProcessFile($conn, (obterDataOriArquivo($_POST['dataModificacao'] ?? null)));

// Variáveis que serão utilizadas em IFs diferentes abaixo
$fileExtension = '';
$fileName = '';
$dateCreation = '';
$nomeArquivoCompleto = '';
$valorInput = '';
$dateUpload = '';
$outputFilePath = '';


// PEGA O FORMULÁRIO VIA POST, verifica se ele foi enviado e se o arquivo foi submetido corretamente
// Processa upload de arquivo
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['xls_file'])) {
    // Obtém informações sobre o arquivo enviado
    $file = $_FILES['xls_file'];
    $fileName = $file['name'];
    // Captura o valor do timestamp obtido do Metadado e converte o timestamp para o formato desejado
    $dateCreation = obterDataOriArquivo($_POST['dataModificacao'] ?? null);

    // Lista de nomes de arquivos permitidos (normalizados, sem extensão)
    $nomesPermitidos = [
        'acessoportal',
        'acesso portal',
        'consultadevagasdeestagio',
        'consulta de vagas de estagio',
        'saida'
    ];

    // Lista de extensões de arquivos permitidas
    $extensoesPermitidas = ['csv', 'xls', 'xlsx'];

    processSpreadSheet($file, $dateCreation, $nomesPermitidos, $extensoesPermitidas, $message);
}


// echo "<label>&nbsp Arquivo Carregado: $fileName</label><br>";
// echo "<label>&nbsp Data de Criação: $dateCreation</label><br>";

// Diretório onde os arquivos de upload estão armazenados
$diretorioUploads = 'C:/xampp/htdocs/portal/portal_phpcsv/uploads';

// Verifica se o diretório existe
if (!is_dir($diretorioUploads)) {
    die("Erro: O diretório de uploads não existe ou não está acessível.");
}

// Obtém a lista de arquivos no diretório de uploads
$arquivos = array_filter(
    scandir($diretorioUploads),
    fn($f) => is_file($diretorioUploads . DIRECTORY_SEPARATOR . $f)
);

// Inicializa variáveis para exibição de detalhes do arquivo selecionado no dropdown
$id = $fileName = $fileSize = $valorInput = $dateCreation = $dateUpload = $outputFilePath = '';
$nomeArquivoSelDrop = $_POST['nomeArquivoSelDrop'] ?? '';
$mensagemLog = empty($nomeArquivoSelDrop) ? "Nenhum arquivo foi selecionado no Dropdown." :
    "Nome do arquivo que está selecionado no Dropdown: {$nomeArquivoSelDrop}";
registrarLogDepuracao("{$mensagemLog}");
// $aux = 'AcessoPortal';  // Nome do arquivo que será utilizado para teste no DB

// Se um arquivo foi selecionado no dropdown, busca os detalhes no banco
if (!empty($nomeArquivoSelDrop)) {
    $detalhesArquivo = selectPlanilhaUpload($conn, $nomeArquivoSelDrop);    // Trocar a variável $nomeArquivoSelDrop por $aux e descomentar linha 69, se for fazer teste no DB
    if ($detalhesArquivo) {
        // Extrai os dados retornados pelo banco para variáveis
        extract($detalhesArquivo); // Cria $id, $arquivoNome, $arquivoTamanho, etc.
    } else {
        // Define valores padrão caso o arquivo não seja encontrado no banco
        $mensagemErro = "As informações do arquivo selecionado não foram encontradas no banco de dados.";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="utf-8" />
    <title>Upload de Arquivos</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
    <!-- Importa o arquivo JavaScript que contém as mensagens -->
    <script src="js/messages.js"></script>
    <!-- Importa o arquivo JavaScript que contém a função obterMetadadosArquivo -->
    <script src="js/captureFileMetadata.js"></script>
    <!-- Importa o arquivo JavaScript que contém a função capturarNomeArquivoSelDropdown -->
    <script src="js/obterFileNomeDropdown.js"></script>
</head>

<body>
    <div class="container mt-5">
        <div class="row">
            <!-- Coluna para Upload de Arquivos -->
            <div class="col-md-6">
                <h1 class="text-danger">Upload de Arquivos</h1>
                <p>Selecione um arquivo para fazer o upload.</p>
                <!-- Adiciona a chamada para exibir a mensagem de processamento ao enviar o formulário -->
                <form action="" method="post" enctype="multipart/form-data" onsubmit="exibirMensagemProcessamento(); return confirmarExclusao();">
                    <div class="form-group">
                        <input type="file" id="arquivo" name="xls_file" class="btn btn-success" required onchange="obterMetadadosArquivo()">
                    </div>
                    <input type="hidden" id="nomeArquivo" name="nomeArquivo">
                    <input type="hidden" id="tipoMime" name="tipoMime">
                    <input type="hidden" id="tamanho" name="tamanho">
                    <input type="hidden" id="dataModificacao" name="dataModificacao">
                    <input type="submit" value="Enviar" class="btn btn-success">
                </form>
                <div id="mensagemProcessamento" style="display:none;">
                    <p class="text-warning">Sua solicitação está sendo executada, aguarde o término da mesma!</p>
                </div>
                <!-- Adiciona a chamada para exibir os metadados do arquivo selecionado -->
                <div id="metadadosArquivo"></div>
            </div>

            <!-- Espaço vazio para a segunda parte -->
            <!-- <div class="col"></div> -->

            <!-- Coluna para Download de Arquivos -->
            <div class="col-md-6">
                <h2 class="text-danger">Download de Arquivos</h2>
                <form id="formDropdown" action="index.php" method="POST">
                    <!-- Dropdown de arquivos -->
                    <div class="form-group col-md-auto">
                        <select class="form-control" id="dropdownArquivos" name="nomeArquivoSelDrop" onchange="this.form.submit()" required>
                            <option value="" disabled selected>Selecione um arquivo para download.</option>
                            <?php foreach ($arquivos as $arquivo): ?>
                                <option value="<?= htmlspecialchars($arquivo); ?>" <?= $arquivo === $nomeArquivoSelDrop ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($arquivo); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Exibição de detalhes do arquivo -->
                    <div class="container">
                        <?php if (!empty($mensagemErro)): ?>
                            <div class="alert alert-warning" role="alert">
                                <?= htmlspecialchars($mensagemErro); ?>
                            </div>
                        <?php else: ?>
                            <div class="form-group">
                                <label>Tam. do arquivo: / <?= htmlspecialchars($id); ?> / <?= htmlspecialchars($fileName); ?> &emsp; <?= htmlspecialchars($fileSize); ?> bytes</label>
                                <!-- <input type="text" readonly class="form-control-plaintext" id="tamanhoArquivo" value="<?= htmlspecialchars($fileSize); ?> bytes"> -->
                            </div>
                            <div class="form-group">
                                <label>Data original do arquivo: &emsp; <?= htmlspecialchars($dateCreation); ?></label>
                            </div>
                            <div class="form-group">
                                <label>Data de upload: &emsp; <?= htmlspecialchars($dateUpload); ?></label>
                            </div>
                            <div class="form-group">
                                <label>Local armazenado: &emsp; <?= htmlspecialchars($outputFilePath); ?></label>
                            </div>
                        <?php endif; ?>
                        <!-- <input type="submit" value="Download" class="btn btn-success"> -->
                    </div>

                    <!-- Campos ocultos para enviar o caminho e o nome do arquivo -->
                    <input type="hidden" name="outputFilePath" value="<?= htmlspecialchars($outputFilePath); ?>">
                    <input type="hidden" name="fileName" value="<?= htmlspecialchars($fileName); ?>">

                    <!-- Botão para download -->
                    <?php if (!empty($nomeArquivoSelDrop) && !empty($outputFilePath)): ?>
                        <button type="submit" name="downloadArquivo" class="btn btn-success">Download  <?= htmlspecialchars($fileName); ?></button>
                    <?php else: ?>
                        <div class="alert alert-warning" role="alert">
                            Selecione um arquivo para habilitar o download.
                        </div>
                    <?php endif; ?>
                </form>
                <!-- Exibição da mensagem de erro -->
                <?php if (!empty($mensagemErro)): ?>
                    <div class="alert alert-danger" role="alert">
                        <?= htmlspecialchars($mensagemErro); ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</body>

</html>