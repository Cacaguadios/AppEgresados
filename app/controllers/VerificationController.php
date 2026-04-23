<?php
/**
 * Controlador de Verificación por Email
 * Maneja códigos de verificación para registro y recuperación de contraseña
 */

require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../helpers/Security.php';

class VerificationController {

    private $usuarioModel;

    public function __construct() {
        $this->usuarioModel = new Usuario();
    }

    /* ================================================================
     *  Dominios permitidos según rol
     * ================================================================ */
    private function getAllowedDomain($role) {
        $domains = [
            'egresado' => '@alumno.utpuebla.edu.mx',
            'docente'  => '@utpuebla.edu.mx',
            'ti'       => '@utpuebla.edu.mx',
        ];
        return $domains[$role] ?? null;
    }

    /**
     * Obtener etiqueta del dominio para mostrar en UI
     */
    public function getDomainLabel($role) {
        $domain = $this->getAllowedDomain($role);
        return $domain ? ltrim($domain, '@') : 'utpuebla.edu.mx';
    }

    /* ================================================================
     *  Validar correo según rol (personal para egresados, institucional para docentes/TI)
     * ================================================================ */
    public function validateInstitutionalEmail($email, $role) {
        $email = strtolower(trim($email));
        $domain = $this->getAllowedDomain($role);

        if (!$domain) {
            return ['success' => false, 'message' => 'Rol no reconocido.'];
        }

        if (empty($email)) {
            $emailType = $role === 'egresado' ? 'El correo personal es requerido.' : 'El correo institucional es requerido.';
            return ['success' => false, 'message' => $emailType];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'El formato del correo no es válido.'];
        }

        // Verificar dominio
        if (!str_ends_with($email, $domain)) {
            $roleName = $role === 'egresado' ? 'egresados' : 'docentes/personal';
            return [
                'success' => false,
                'message' => "El correo debe terminar en {$domain} para {$roleName}."
            ];
        }

        return ['success' => true, 'email' => $email];
    }

    /* ================================================================
     *  Generar y guardar código de verificación
     * ================================================================ */
    public function sendVerificationCode($email, $tipo = 'registro') {
        $email = strtolower(trim($email));

        // Generar código de 6 dígitos
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Invalidar códigos previos del mismo email y tipo
        $this->usuarioModel->invalidateVerificationCodes($email, $tipo);

        // Guardar nuevo código (expira en 10 minutos)
        $expiration = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        $this->usuarioModel->createVerificationCode($email, $code, $tipo, $expiration);

        // Enviar correo (SMTP en producción o log en desarrollo)
        $mailResult = $this->sendVerificationEmail($email, $code, $tipo);

        if (!($mailResult['success'] ?? false)) {
            return [
                'success' => false,
                'message' => $mailResult['message'] ?? 'No se pudo enviar el correo de verificación.'
            ];
        }

        return [
            'success' => true,
            'message' => $mailResult['message'] ?? ('Código de verificación enviado a ' . $email)
        ];
    }

    /* ================================================================
     *  Verificar código ingresado
     * ================================================================ */
    public function verifyCode($email, $code, $tipo = 'registro') {
        $email = strtolower(trim($email));
        $code = trim($code);

        if (empty($code) || strlen($code) !== 6) {
            return ['success' => false, 'message' => 'El código debe tener 6 dígitos.'];
        }

        $record = $this->usuarioModel->getVerificationCode($email, $tipo);

        if (!$record) {
            return ['success' => false, 'message' => 'No se encontró un código activo. Solicita uno nuevo.'];
        }

        // Verificar expiración
        if (strtotime($record['fecha_expiracion']) < time()) {
            return ['success' => false, 'message' => 'El código ha expirado. Solicita uno nuevo.'];
        }

        // Verificar intentos (máximo 5)
        if ($record['intentos'] >= 5) {
            return ['success' => false, 'message' => 'Demasiados intentos. Solicita un nuevo código.'];
        }

        // Incrementar intentos
        $this->usuarioModel->incrementVerificationAttempts($record['id']);

        // Verificar código
        if ($record['codigo'] !== $code) {
            $remaining = 4 - $record['intentos'];
            return [
                'success' => false,
                'message' => "Código incorrecto. Te quedan {$remaining} intentos."
            ];
        }

        // Marcar como usado
        $this->usuarioModel->markVerificationCodeUsed($record['id']);

        return ['success' => true, 'message' => 'Código verificado correctamente.'];
    }

    /* ================================================================
     *  Verificar email para registro (paso 4)
     * ================================================================ */
    public function verifyRegistrationEmail($userId, $email, $codigo = '') {
        $email = strtolower(trim($email));

        // Actualizar usuario con email verificado
        $this->usuarioModel->markEmailAsVerified($userId);

        // Registrar en auditoría (si disponible)
        $this->logEmailVerification($userId, $email, 'registro', $codigo);

        return ['success' => true];
    }

    /* ================================================================
     *  Log de verificación de email (auditoría)
     * ================================================================ */
    private function logEmailVerification($userId, $email, $tipo, $codigo = '') {
        try {
            $data = [
                'usuario_id' => $userId,
                'email_verificado' => $email,
                'tipo_verificacion' => $tipo,
                'codigo_usado' => $codigo,
                'ip_direccion' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
                'fecha_verificacion' => date('Y-m-d H:i:s'),
            ];
            $this->usuarioModel->insert('email_verification_log', $data);
        } catch (Exception $e) {
            // Log silenciosamente si hay error en auditoría
        }
    }

    /* ================================================================
     *  Enviar código de recuperación de contraseña
     * ================================================================ */
    public function sendPasswordResetCode($email) {
        $email = strtolower(trim($email));

        if (empty($email)) {
            return ['success' => false, 'message' => 'El correo es requerido.'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'El formato del correo no es válido.'];
        }

        // Verificar que el email exista como correo de verificación
        $usuario = $this->usuarioModel->getByInstitutionalEmail($email);

        if (!$usuario) {
            // También buscar en email normal
            $usuario = $this->usuarioModel->getByEmail($email);
        }

        if (!$usuario) {
            // Por seguridad, no revelar si el email existe o no
            return [
                'success' => true,
                'message' => 'Si el correo está registrado, recibirás un código de verificación.'
            ];
        }

        // Generar y enviar código
        return $this->sendVerificationCode($email, 'recuperacion');
    }

    /* ================================================================
     *  Resetear contraseña
     * ================================================================ */
    public function resetPassword($email, $newPassword, $confirmPassword) {
        $email = strtolower(trim($email));

        if (empty($newPassword)) {
            return ['success' => false, 'message' => 'La contraseña es requerida.'];
        }

        if (strlen($newPassword) < 8) {
            return ['success' => false, 'message' => 'La contraseña debe tener al menos 8 caracteres.'];
        }

        if ($newPassword !== $confirmPassword) {
            return ['success' => false, 'message' => 'Las contraseñas no coinciden.'];
        }

        // Validar formato de contraseña
        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $newPassword)) {
            return [
                'success' => false,
                'message' => 'La contraseña debe tener al menos una mayúscula, una minúscula, un número y un carácter especial.'
            ];
        }

        // Buscar usuario por email institucional o normal
        $usuario = $this->usuarioModel->getByInstitutionalEmail($email);
        if (!$usuario) {
            $usuario = $this->usuarioModel->getByEmail($email);
        }

        if (!$usuario) {
            return ['success' => false, 'message' => 'No se encontró una cuenta con ese correo.'];
        }

        // Actualizar contraseña
        $this->usuarioModel->updatePassword($usuario['id'], $newPassword);

        return ['success' => true, 'message' => 'Contraseña actualizada correctamente.'];
    }

    /* ================================================================
     *  Enviar email de verificación (SMTP o log)
     * ================================================================ */
    private function sendVerificationEmail($to, $code, $tipo) {
        $subject = $tipo === 'registro' 
            ? 'Código de verificación - Registro UTP' 
            : 'Código de recuperación - UTP';

        $message = $tipo === 'registro'
            ? "Tu código de verificación es: {$code}. Expira en 10 minutos."
            : "Tu código de recuperación es: {$code}. Expira en 10 minutos.";

        $driver = strtolower((string) (getenv('MAIL_DRIVER') ?: 'log'));

        if ($driver === 'smtp') {
            if ($this->sendEmailViaSmtp($to, $subject, $message)) {
                return [
                    'success' => true,
                    'message' => 'Código de verificación enviado a ' . $to
                ];
            }

            return [
                'success' => false,
                'message' => 'No se pudo enviar el correo de verificación por SMTP. Verifica MAIL_HOST, MAIL_PORT, MAIL_USER y MAIL_PASS.'
            ];
        }

        $this->logSimulatedEmail($to, $subject, $code, $tipo);

        return [
            'success' => true,
            'message' => 'Código generado en modo local (MAIL_DRIVER=log). Revisa storage/logs/emails.log para ver el código.'
        ];
    }

    private function sendEmailViaSmtp($to, $subject, $message) {
        $autoload = __DIR__ . '/../../vendor/autoload.php';
        if (file_exists($autoload)) {
            require_once $autoload;
        }

        if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            return false;
        }

        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

            $host = getenv('MAIL_HOST') ?: '';
            $port = (int) (getenv('MAIL_PORT') ?: 587);
            $user = getenv('MAIL_USER') ?: '';
            $pass = getenv('MAIL_PASS') ?: '';
            $from = getenv('MAIL_FROM') ?: $user;
            $fromName = getenv('MAIL_FROM_NAME') ?: (getenv('APP_NAME') ?: 'AppEgresados UTP');
            $encryption = strtolower((string) (getenv('MAIL_ENCRYPTION') ?: 'tls'));

            if ($host === '' || $from === '') {
                return false;
            }

            $mail->isSMTP();
            $mail->Host = $host;
            $mail->Port = $port;

            if ($user !== '' && $pass !== '') {
                $mail->SMTPAuth = true;
                $mail->Username = $user;
                $mail->Password = $pass;
            } else {
                $mail->SMTPAuth = false;
            }

            if ($encryption === 'ssl') {
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            }

            $mail->CharSet = 'UTF-8';
            $mail->setFrom($from, $fromName);
            $mail->addAddress($to);
            $mail->Subject = $subject;
            $mail->isHTML(false);
            $mail->Body = $message;

            $mail->send();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /* ================================================================
     *  Registrar envío simulado de email (fallback)
     * ================================================================ */
    private function logSimulatedEmail($to, $subject, $code, $tipo) {

        $logDir = __DIR__ . '/../../storage/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $logFile = $logDir . '/emails.log';
        $timestamp = date('Y-m-d H:i:s');
        $log = "[{$timestamp}] TO: {$to} | SUBJECT: {$subject} | CODE: {$code} | TYPE: {$tipo}\n";
        file_put_contents($logFile, $log, FILE_APPEND);
    }
}
?>
