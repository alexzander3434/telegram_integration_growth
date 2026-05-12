<?php

declare(strict_types=1);

namespace App\Tests;

use App\Entity\Shop;
use App\Repository\TelegramIntegrationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Request;

final class TelegramIntegrationConnectTest extends KernelTestCase
{
    private int $shopId;

    protected function setUp(): void
    {
        parent::setUp();
        self::ensureKernelShutdown();
        self::bootKernel();

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine.orm.default_entity_manager');
        $tool = new SchemaTool($em);
        $classes = $em->getMetadataFactory()->getAllMetadata();
        $tool->dropSchema($classes);
        $tool->createSchema($classes);

        $shop = new Shop('Connect test shop');
        $em->persist($shop);
        $em->flush();
        $this->shopId = (int) $shop->getId();
        $em->clear();
    }

    #[RunInSeparateProcess]
    public function testConnectEnabledDoesNotCallTelegramHttp(): void
    {
        $kernel = self::$kernel;
        self::assertNotNull($kernel);

        /** @var MockHttpClient $http */
        $http = static::getContainer()->get('test.telegram_http_client');
        $http->setResponseFactory(static function (string $method, string $url, array $options = []): MockResponse {
            self::fail('HTTP client should not be used on connect (no token probe)');
        });

        $body = [
            'botToken' => '123:ABC',
            'chatId' => '-1001',
            'enabled' => true,
        ];

        $request = Request::create(
            sprintf('/shops/%d/telegram/connect', $this->shopId),
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            (string) json_encode($body, JSON_THROW_ON_ERROR)
        );

        $response = $kernel->handle($request);
        try {
            self::assertSame(201, $response->getStatusCode(), (string) $response->getContent());
            self::assertSame(0, $http->getRequestsCount());

            /** @var TelegramIntegrationRepository $integrationRepo */
            $integrationRepo = static::getContainer()->get(TelegramIntegrationRepository::class);
            $integration = $integrationRepo->findOneByShopId($this->shopId);
            self::assertNotNull($integration);
            self::assertTrue($integration->isEnabled());
        } finally {
            $kernel->terminate($request, $response);
        }
    }

    #[RunInSeparateProcess]
    public function testConnectDisabledDoesNotCallTelegramApi(): void
    {
        $kernel = self::$kernel;
        self::assertNotNull($kernel);

        /** @var MockHttpClient $http */
        $http = static::getContainer()->get('test.telegram_http_client');
        $http->setResponseFactory(static function (string $method, string $url, array $options = []): MockResponse {
            self::fail('HTTP client should not be used when integration is disabled');
        });

        $body = [
            'botToken' => 'bad:token',
            'chatId' => '-1001',
            'enabled' => false,
        ];

        $request = Request::create(
            sprintf('/shops/%d/telegram/connect', $this->shopId),
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            (string) json_encode($body, JSON_THROW_ON_ERROR)
        );

        $response = $kernel->handle($request);
        try {
            self::assertSame(201, $response->getStatusCode(), (string) $response->getContent());
            self::assertSame(0, $http->getRequestsCount());
        } finally {
            $kernel->terminate($request, $response);
        }
    }
}
