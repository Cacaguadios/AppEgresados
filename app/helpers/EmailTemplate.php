<?php
/**
 * Plantillas HTML para correos del sistema.
 * Mantiene los colores y la tipografía base de la app.
 */

class EmailTemplate {
    private const RED = '#7A1501';
    private const GREEN = '#00853E';
    private const BG = '#FAFAFA';
    private const TEXT = '#121212';
    private const MUTED = '#757575';

    public static function buildSystemEmailHtml(string $subject, string $message, string $tipo = 'general'): string {
        $title = self::escape($subject);
        $paragraphs = [self::escape(self::introTextForTipo($tipo))];
        $accentLabel = self::labelForTipo($tipo);

        return self::baseLayout(
            $title,
            $accentLabel,
            $paragraphs,
            self::systemBodyExtra($tipo, $message)
        );
    }

    public static function buildSystemEmailText(string $subject, string $message, string $tipo = 'general'): string {
        $text = strtoupper($subject) . PHP_EOL . PHP_EOL . trim($message);
        $footer = self::footerText($tipo);
        if ($footer !== '') {
            $text .= PHP_EOL . PHP_EOL . $footer;
        }
        return $text;
    }

    public static function buildVerificationEmailHtml(string $code, string $tipo = 'registro'): string {
        $title = $tipo === 'registro' ? 'Código de verificación' : 'Código de recuperación';
        $intro = $tipo === 'registro'
            ? 'Usa este código para verificar tu correo y continuar con tu registro.'
            : 'Usa este código para continuar con la recuperación de tu contraseña.';

        $codeBlock = '
            <div style="margin:24px 0;padding:22px 20px;background:#F3FBF5;border:1px solid rgba(0,133,62,.25);border-radius:18px;text-align:center;">
              <div style="font-size:13px;line-height:20px;color:' . self::MUTED . ';font-weight:600;letter-spacing:.04em;text-transform:uppercase;margin-bottom:10px;">Tu código</div>
              <div style="display:inline-block;padding:10px 16px;background:#FFFFFF;border:2px solid rgba(0,133,62,.35);border-radius:12px;font-family:Cousine, \"Courier New\", monospace;font-size:40px;line-height:44px;letter-spacing:10px;color:' . self::RED . ';font-weight:700;-webkit-user-select:all;user-select:all;">' . self::escape($code) . '</div>
              <div style="margin-top:10px;font-size:12px;line-height:18px;color:#2F5A38;">Selecciona el código para copiarlo rápidamente.</div>
            </div>
        ';

        $expiryBlock = '
            <div style="margin-top:20px;padding:14px 16px;background:#F6FBF7;border:1px solid rgba(0,133,62,.22);border-radius:14px;text-align:center;">
                                                        <span style="display:inline-block;color:#2F5A38;font-size:13px;line-height:20px;font-weight:600;">Este código expira en</span>
                                                        <span style="display:inline-block;color:#0B5A30;background:rgba(0,133,62,.10);border:1px solid rgba(0,133,62,.20);padding:2px 8px;border-radius:7px;font-size:13px;line-height:18px;font-weight:700;margin-left:6px;">10 minutos</span>.
            </div>
        ';

        return self::baseLayout(
            $title,
            'Verificación',
            [self::escape($intro)],
            $codeBlock . $expiryBlock . self::footerBlock('Si no lo solicitaste, puedes ignorar este mensaje.')
        );
    }

    public static function buildVerificationEmailText(string $code, string $tipo = 'registro'): string {
        $title = $tipo === 'registro' ? 'Código de verificación' : 'Código de recuperación';
        $intro = $tipo === 'registro'
            ? 'Usa este código para verificar tu correo y continuar con tu registro.'
            : 'Usa este código para continuar con la recuperación de tu contraseña.';
        return $title . PHP_EOL . PHP_EOL . $intro . PHP_EOL . PHP_EOL . 'Código: ' . $code . PHP_EOL . PHP_EOL . 'Este código expira en 10 minutos. Si no lo solicitaste, ignora este mensaje.';
    }

    private static function baseLayout(string $title, string $badge, array $paragraphs, string $afterBodyHtml = ''): string {
        $bodyHtml = '';
        foreach ($paragraphs as $paragraph) {
            $bodyHtml .= '<p style="margin:0 0 14px;color:' . self::TEXT . ';font-size:16px;line-height:24px;">' . $paragraph . '</p>';
        }

        return '<!doctype html>'
            . '<html lang="es">'
            . '<head>'
            . '<meta charset="UTF-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1.0">'
            . '<title>' . $title . '</title>'
            . '</head>'
            . '<body style="margin:0;padding:0;background:' . self::BG . ';font-family:-apple-system,BlinkMacSystemFont,\"Segoe UI\",Roboto,\"Helvetica Neue\",Arial,sans-serif;color:' . self::TEXT . ';">'
            . '<div style="padding:24px 12px;">'
            . '<div style="max-width:620px;margin:0 auto;background:#fff;border-radius:18px;overflow:hidden;border:1px solid #eadfdd;">'
            . '<div style="padding:22px 24px 16px;background-color:' . self::RED . ';text-align:center;">'
            . '<img src="https://www.utpuebla.edu.mx/images/03_filosofy/utp.png" alt="Universidad Tecnológica de Puebla" width="86" height="86" style="display:inline-block;border-radius:50%;border:3px solid rgba(255,255,255,0.28);background:#ffffff;">'
            . '<p style="margin:14px 0 0;color:#ffffff;font-size:12px;line-height:18px;letter-spacing:1.4px;text-transform:uppercase;font-weight:700;">Bolsa de Trabajo UTP</p>'
            . '</div>'
            . '<div style="padding:26px 24px 30px;">'
            . '<h1 style="margin:0 0 12px;text-align:center;color:' . self::RED . ';font-size:30px;line-height:34px;font-weight:700;">' . $title . '</h1>'
            . '<div style="text-align:center;margin:0 0 16px;">'
            . '<span style="display:inline-block;padding:7px 16px;background:' . self::GREEN . ';border:1px solid ' . self::GREEN . ';border-radius:999px;color:#ffffff;font-size:12px;line-height:16px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;">' . self::escape($badge) . '</span>'
            . '</div>'
            . $bodyHtml
            . $afterBodyHtml
            . '</div>'
            . '</div>'
            . '</div>'
            . '</body>'
            . '</html>';
    }

    private static function systemBodyExtra(string $tipo, string $message): string {
        $positiveTypes = ['oferta_aprobada', 'oferta_nueva', 'postulacion_seleccionada', 'invitacion_oferta', 'invitacion_vacante', 'nueva_postulacion'];
        $warningTypes = ['oferta_rechazada', 'postulacion_rechazada', 'perfil_no_cumple', 'postulacion_retirada'];

        $isPositive = in_array($tipo, $positiveTypes, true);
        $isWarning = in_array($tipo, $warningTypes, true);

        $accent = $isPositive ? self::GREEN : self::RED;
        $title = $isPositive
            ? 'Notificación positiva'
            : ($isWarning ? 'Aviso importante' : 'Seguimiento de proceso');
        $boxBg = $isPositive ? '#F3FBF5' : '#FAFAFA';
        $boxBorder = $isPositive ? '1px solid rgba(0,133,62,.22)' : '1px solid rgba(122,21,1,.12)';

        $footer = self::footerText($tipo);
        $messageHtml = '<div style="margin:22px 0 18px;padding:18px 18px 18px 20px;background:' . $boxBg . ';' . $boxBorder . ';border-left:5px solid ' . $accent . ';border-radius:16px;">'
            . '<div style="font-size:13px;line-height:20px;color:' . self::MUTED . ';font-weight:600;text-transform:uppercase;letter-spacing:.04em;margin-bottom:10px;">' . $title . '</div>'
            . '<div style="font-size:16px;line-height:25px;color:' . self::TEXT . ';">' . self::paragraphsFromPlainText($message) . '</div>'
            . '</div>';

        if ($footer === '') {
            return $messageHtml;
        }

        return $messageHtml . self::footerBlock($footer);
    }

    private static function footerBlock(string $text): string {
        return '<div style="margin-top:20px;padding-top:18px;border-top:1px solid rgba(122,21,1,.12);color:' . self::MUTED . ';font-size:13px;line-height:20px;">' . self::escape($text) . '</div>';
    }

    private static function footerText(string $tipo): string {
        if (in_array($tipo, ['oferta_aprobada', 'oferta_nueva', 'postulacion_seleccionada', 'invitacion_oferta', 'invitacion_vacante', 'nueva_postulacion'], true)) {
            return 'Ingresa al sistema para revisar el detalle y continuar con tu proceso.';
        }

        if (in_array($tipo, ['oferta_rechazada', 'postulacion_rechazada', 'perfil_no_cumple', 'feedback_pendiente', 'postulacion_retirada'], true)) {
            return 'Puedes revisar tu cuenta dentro de la plataforma para ver más detalles.';
        }

        return 'Mensaje generado por Bolsa de Trabajo UTP.';
    }

    private static function labelForTipo(string $tipo): string {
        $map = [
            'oferta_aprobada' => 'Oferta aprobada',
            'oferta_rechazada' => 'Oferta rechazada',
            'oferta_nueva' => 'Nueva oferta',
            'postulacion_seleccionada' => 'Seleccionado',
            'postulacion_rechazada' => 'No seleccionado',
            'postulacion_retirada' => 'Postulación retirada',
            'invitacion_oferta' => 'Invitación',
            'invitacion_vacante' => 'Vacante recomendada',
            'nueva_postulacion' => 'Nueva postulación',
            'perfil_no_cumple' => 'Actualiza tu perfil',
            'feedback_pendiente' => 'Seguimiento',
            'general' => 'Notificación',
        ];

        return $map[$tipo] ?? 'Notificación';
    }

    private static function introTextForTipo(string $tipo): string {
        $map = [
            'oferta_aprobada' => 'Tu publicación fue revisada y aprobada exitosamente.',
            'oferta_rechazada' => 'Tu oferta requiere ajustes antes de volver a publicarse.',
            'oferta_nueva' => 'Se abrió una nueva oportunidad que puede interesarte.',
            'nueva_postulacion' => 'Tienes una actualización en el flujo de postulaciones.',
            'postulacion_seleccionada' => '¡Excelente noticia! Tu proceso avanzó favorablemente.',
            'postulacion_rechazada' => 'Tu estado de postulación se actualizó recientemente.',
            'postulacion_retirada' => 'Se registró un cambio en el estado de una postulación.',
            'invitacion_oferta' => 'Recibiste una invitación para aplicar a una vacante.',
            'invitacion_vacante' => 'Hay una vacante sugerida para tu perfil profesional.',
            'perfil_no_cumple' => 'Recomendamos actualizar tu perfil para mejorar tu match con vacantes.',
            'feedback_pendiente' => 'Necesitamos tu retroalimentación para cerrar el seguimiento.',
            'general' => 'Te compartimos una actualización importante de la plataforma.',
        ];

        return $map[$tipo] ?? 'Te compartimos una actualización importante de la plataforma.';
    }

    private static function messageToParagraphs(string $message): array {
        $parts = preg_split('/\R+/', trim($message)) ?: [];
        $paragraphs = [];

        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }
            $paragraphs[] = self::escape($part);
        }

        return $paragraphs ?: [self::escape(trim($message))];
    }

    private static function paragraphsFromPlainText(string $message): string {
        $parts = preg_split('/\R+/', trim($message)) ?: [];
        $html = '';
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }
            $html .= '<p style="margin:0 0 10px;">' . self::escape($part) . '</p>';
        }
        return $html ?: '<p style="margin:0;">' . self::escape(trim($message)) . '</p>';
    }

    private static function escape(string $value): string {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
