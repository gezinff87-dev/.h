<?php
// Arquivo: enviar.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Dados do formulário
    $nome = htmlspecialchars(trim($_POST['nome'] ?? ''));
    $telefone = htmlspecialchars(trim($_POST['telefone'] ?? ''));
    $email = htmlspecialchars(trim($_POST['email'] ?? ''));
    $assunto = htmlspecialchars(trim($_POST['assunto'] ?? ''));
    $mensagem = htmlspecialchars(trim($_POST['mensagem'] ?? ''));
    
    // Validação
    $errors = [];
    if (empty($nome)) $errors[] = "Nome é obrigatório";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "E-mail inválido";
    if (empty($mensagem)) $errors[] = "Mensagem é obrigatória";
    
    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => implode(', ', $errors)
        ]);
        exit;
    }
    
    // CONFIGURE SEU E-MAIL AQUI
    $to = "tyrylopis@gmail.com"; // ← TROQUE AQUI!
    
    // Assunto
    $email_subject = "Contato Site Advogada: $assunto";
    
    // Corpo do e-mail
    $email_body = "NOVO CONTATO - SITE DA ADVOGADA\n";
    $email_body .= "================================\n\n";
    $email_body .= "Nome: $nome\n";
    $email_body .= "Telefone: $telefone\n";
    $email_body .= "E-mail: $email\n";
    $email_body .= "Assunto: $assunto\n\n";
    $email_body .= "Mensagem:\n";
    $email_body .= "--------------------------------\n";
    $email_body .= "$mensagem\n";
    $email_body .= "--------------------------------\n\n";
    $email_body .= "Enviado em: " . date('d/m/Y H:i:s') . "\n";
    $email_body .= "IP: " . ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR']);
    
    // Cabeçalhos
    $headers = "From: contato@siteadvogada.onrender.com\r\n";
    $headers .= "Reply-To: $email\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/plain; charset=utf-8";
    
    // Tenta enviar
    if (mail($to, $email_subject, $email_body, $headers)) {
        echo json_encode([
            'success' => true, 
            'message' => '✅ Mensagem enviada com sucesso! Entrarei em contato em breve.'
        ]);
    } else {
        // Fallback: salva em arquivo local (para debug)
        $log_data = [
            'data' => date('Y-m-d H:i:s'),
            'nome' => $nome,
            'email' => $email,
            'telefone' => $telefone,
            'assunto' => $assunto,
            'mensagem' => $mensagem
        ];
        
        file_put_contents('contatos.log', json_encode($log_data) . "\n", FILE_APPEND);
        
        echo json_encode([
            'success' => false, 
            'message' => '⚠️ Mensagem recebida! Sistema de e-mails offline. Entre em contato pelo WhatsApp: (11) 99999-9999'
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode([
        'success' => false, 
        'message' => 'Método não permitido'
    ]);
}
?>
