<?php
require_once '../vendor/autoload.php';
include_once('../conn.php');
include_once('../logs/criaLogs.php'); // Inclui a função de log
include_once('../dbSql/inserirDados.php'); // Inclui a função genérica de inserção de dados
include_once('../dbSql/dateConverterSql.php'); // Inclui a função de conversão de data
include_once('../data_processing/utils.php'); // Inclui as funções comuns
include_once('../dbSql/truncarTabelaSql.php');
include_once('../logs/ordenarGravarErrosLog.php');
include_once('vagasDataPipeline.php'); // Inclui a função de processamento de linha

ini_set('display_errors', 1);   // Exibir erros
ini_set('display_startup_errors', 1);   // Exibir erros
error_reporting(E_ALL);  // Exibir erros

ini_set('max_execution_time', 300); // Tempo em segundos
ini_set('memory_limit', '512M');   // Memória em MB

function processarVagasEstagio($file, $conn, $tabela, $processarLinha){
    try {   // Iniciar uma transação
        mysqli_autocommit($conn, FALSE);

        registrarLogDepuracao("Função processarVagasEstagio iniciada.");

        // Limpa a tabela antes de inserir novos dados
        // LEMBRAR DE CODIFICAR PARA QUE APENAS O USUÁRIO ADM POSSA EXECUTAR ESSA FUNÇÃO.
        truncarTabela($conn, $tabela);

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
        registrarLogDepuracao("Planilha $file carregada.");

        $worksheet = $spreadsheet->getActiveSheet();
        registrarLogDepuracao("Aba ativa da planilha obtida.");

        // Inicializa as variáveis que contarão as linhas e colunas inseridas e os erros
        $totalLinhas = 0;
        $totalColunas = 0;
        $erros = 0;
        
        // Array para armazenar os erros detalhados
        $errosDetalhados = [];

        // Itera sobre todas as linhas da planilha
        iterarSobreLinhas($worksheet, $processarLinha, $conn, $tabela, $totalLinhas, $totalColunas, $erros, $errosDetalhados, false);

        // Captura e grava os erros no log
        capturarErrosToLog($errosDetalhados, $tabela, $totalLinhas, $totalColunas, $erros, $file, false);

        // Exibe a mensagem resumida no navegador
        exibirMensagemResumida($tabela, $totalLinhas, $totalColunas, $erros);

        // Confirmar a transação
        $conn->commit();

    } catch (Exception $e) {    // Captura exceções
        // Rollback da transação em caso de erro
        mysqli_rollback($conn);
        registrarLogErro("Erro ao processar o arquivo {$file}: " . $e->getMessage(), $file);
        // Enviar notificação por e-mail ou outro canal
    }   //  Fim do bloco TRY-CATCH
}   //  Fim da função processarVagasEstagio

// Verifica se o arquivo foi enviado via GET
if (isset($_GET['file'])) {
    $file = urldecode($_GET['file']);
    $tabela = 'portal_vagas_estagio'; // Informar a tabela que será trabalhada
    processarVagasEstagio($file, $conn, $tabela, 'vagasPlanilhaExtrairMapToDb');
}   //  Fim do IF de verificação de arquivo enviado via GET

$conn->close();
