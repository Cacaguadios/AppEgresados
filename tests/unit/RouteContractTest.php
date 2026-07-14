<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class RouteContractTest extends TestCase
{
    public function testViewsDoNotContainDeploymentSpecificRoutes(): void
    {
        $root = dirname(__DIR__, 2);
        $directories = [$root . '/views', $root . '/public/assets/components'];

        foreach ($directories as $directory) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
            foreach ($iterator as $file) {
                if (!$file->isFile() || !in_array($file->getExtension(), ['php', 'html'], true)) {
                    continue;
                }

                $contents = (string) file_get_contents($file->getPathname());
                self::assertStringNotContainsString('/AppEgresados/', $contents, $file->getPathname());
                self::assertDoesNotMatchRegularExpression(
                    "#(?:\.\./)+auth/login\.php#",
                    $contents,
                    $file->getPathname()
                );
            }
        }
    }

    public function testNavigationComponentsUseCleanRegisteredRoutes(): void
    {
        $components = dirname(__DIR__, 2) . '/public/assets/components';
        $expectedRoutes = [
            'sidebar-egresado.html' => [
                '/egresado/inicio', '/egresado/ofertas', '/egresado/postulaciones',
                '/egresado/publicar-oferta', '/egresado/mis-ofertas',
                '/egresado/perfil', '/egresado/seguimiento',
            ],
            'sidebar-docente.html' => [
                '/docente/inicio', '/docente/publicar-oferta', '/docente/mis-ofertas',
                '/docente/postulantes', '/docente/directorio',
            ],
            'sidebar-admin.html' => [
                '/admin/inicio', '/admin/moderacion', '/admin/verificacion',
                '/admin/seguimiento', '/admin/reportes', '/admin/usuarios',
                '/admin/seguridad',
            ],
        ];

        foreach ($expectedRoutes as $file => $routes) {
            $contents = (string) file_get_contents($components . '/' . $file);
            self::assertStringNotContainsString('.php', $contents, $file);
            foreach ($routes as $route) {
                self::assertStringContainsString('{APP}' . $route, $contents, $file . ': ' . $route);
            }
        }
    }
}
