<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class PublicUrlTest extends TestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testPublicUrlsAtDomainRoot(): void
    {
        putenv('APP_BASE_PATH=');

        require dirname(__DIR__, 2) . '/config/bootstrap.php';

        self::assertSame('', BASE_URL);
        self::assertSame('/assets', ASSETS_URL);
        self::assertSame('/api', API_URL);
        self::assertSame('/login', appUrl('/login'));
        self::assertSame('/admin/inicio', getDashboardUrl('admin'));
        self::assertSame('/docente/inicio', getDashboardUrl('ti'));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testPublicUrlsInSubdirectory(): void
    {
        putenv('APP_BASE_PATH=/AppEgresados/');

        require dirname(__DIR__, 2) . '/config/bootstrap.php';

        self::assertSame('/AppEgresados', BASE_URL);
        self::assertSame('/AppEgresados/assets', ASSETS_URL);
        self::assertSame('/AppEgresados/api', API_URL);
        self::assertSame('/AppEgresados/login', appUrl('/login'));
        self::assertSame('/AppEgresados/egresado/inicio', getDashboardUrl('egresado'));
    }

    public function testBrowserComponentsAreInsidePublicAssets(): void
    {
        $components = dirname(__DIR__, 2) . '/public/assets/components';

        foreach ([
            'notice-password.html',
            'sidebar-admin.html',
            'sidebar-docente.html',
            'sidebar-egresado.html',
            'topbar.html',
        ] as $component) {
            self::assertFileExists($components . '/' . $component);
        }
    }
}
