<?php
// Arquivo: enviar.php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Se for requisição OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Verifica se é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit();
}

// Inicializa resposta
$response = ['success' => false, 'message' => 'Erro desconhecido'];

try {
    // Captura dados brutos do POST
    $input = file_get_contents('php://input');
    
    // Tenta decodificar JSON primeiro
    $data = json_decode($input, true);
    
    // Se não for JSON, usa $_POST
    if (json_last_error() !== JSON_ERROR_NONE || empty($data)) {
        $data = $_POST;
    }
    
    // Validação dos dados
    $nome = isset($data['nome']) ? trim(htmlspecialchars($data['nome'])) : '';
    $telefone = isset($data['telefone']) ? trim(htmlspecialchars($data['telefone'])) : '';
    $email = isset($data['email']) ? trim(htmlspecialchars($data['email'])) : '';
    $assunto = isset($data['assunto']) ? trim(htmlspecialchars($data['assunto'])) : '';
    $mensagem = isset($data['mensagem']) ? trim(htmlspecialchars($data['mensagem'])) : '';
    
    // Validações
    $errors = [];
    if (empty($nome)) $errors[] = "Nome é obrigatório";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "E-mail inválido";
    if (empty($mensagem)) $errors[] = "Mensagem é obrigatória";
    
    if (!empty($errors)) {
        $response = ['success' => false, 'message' => implode('. ', $errors)];
        echo json_encode($response);
        exit();
    }
    
    // Dados para log (útil para debug)
    $log_data = [
        'data' => date('Y-m-d H:i:s'),
        'nome' => $nome,
        'email' => $email,
        'telefone' => $telefone,
        'assunto' => $assunto,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'Desconhecido'
    ];
    
    // Tenta enviar e-mail (COLOQUE SEU E-MAIL AQUI!)
    $to = "SEU-EMAIL@DOMINIO.COM"; // ← TROQUE AQUI!
    $subject = "Contato Site Advogada: " . ($assunto ?: "Novo Contato");
    
    $email_body = "NOVO CONTATO DO SITE\n";
    $email_body .= "====================\n\n";
    $email_body .= "Nome: $nome\n";
    $email_body .= "Telefone: $telefone\n";
    $email_body .= "E-mail: $email\n";
    $email_body .= "Assunto: $assunto\n\n";
    $email_body .= "Mensagem:\n";
    $email_body .= str_repeat("-", 40) . "\n";
    $email_body .= "$mensagem\n";
    $email_body .= str_repeat("-", 40) . "\n\n";
    $email_body .= "Enviado em: " . date('d/m/Y H:i:s') . "\n";
    $email_body .= "IP: " . ($log_data['ip']);
    
    $headers = "From: contato@siteadvogada.onrender.com\r\n";
    $headers .= "Reply-To: $email\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    // Tenta enviar
    if (mail($to, $subject, $email_body, $headers)) {
        $response = [
            'success' => true, 
            'message' => '✅ Mensagem enviada com sucesso! Entrarei em contato em breve.'
        ];
    } else {
        // Fallback: salva em arquivo local
        file_put_contents('contatos.log', json_encode($log_data) . "\n", FILE_APPEND);
        
        $response = [
            'success' => false, 
            'message' => '⚠️ Mensagem recebida! Sistema de e-mails offline. Entre em contato pelo WhatsApp: (11) 99999-9999'
        ];
    }
    
} catch (Exception $e) {
    $response = ['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()];
}

// Garante que sempre retorna JSON
echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit();
?>
