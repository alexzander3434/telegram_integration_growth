<?php

declare(strict_types=1);

namespace App\Tests;

use App\Entity\Order;
use App\Entity\TelegramIntegration;
use App\Entity\TelegramSendLog;
use App\Entity\TelegramSendStatus;
use App\Message\OrderCreatedMessage;
use App\Repository\TelegramSendLogRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;

final class CreateOrderTelegramSentTest extends KernelTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        self::ensureKernelShutdown();
        self::bootKernel();

        /** @var MockHttpClient $http */
        $http = static::getContainer()->get('test.telegram_http_client');
        $http->setResponseFactory(static function (): MockResponse {
            return new MockResponse(
                (string) json_encode(['ok' => true, 'result' => []], JSON_THROW_ON_ERROR),
                ['http_code' => 200, 'headers' => ['Content-Type' => 'application/json']]
            );
        });

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine.orm.default_entity_manager');
        $tool = new SchemaTool($em);
        $classes = $em->getMetadataFactory()->getAllMetadata();
        $tool->dropSchema($classes);
        $tool->createSchema($classes);

        $em->persist(new TelegramIntegration(
            shopId: 101,
            botToken: 'test-token',
            chatId: '12345',
            enabled: true
        ));
        $em->flush();
        $em->clear();
    }

    #[RunInSeparateProcess]
    public function testCreateOrderDispatchesTelegramSendAndWritesSentLog(): void
    {
        $kernel = self::$kernel;
        self::assertNotNull($kernel);

        $payload = [
            'number' => 'ORD-telegram-'.bin2hex(random_bytes(4)),
            'total' => '1599.00',
            'customerName' => 'Иван',
        ];

        $request = Request::create(
            '/shops/101/orders',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            (string) json_encode($payload, JSON_THROW_ON_ERROR)
        );

        $response = $kernel->handle($request);

        try {
            self::assertSame(201, $response->getStatusCode());
            $decoded = json_decode((string) $response->getContent(), true);
            self::assertIsArray($decoded);
            $orderId = (int) ($decoded['id'] ?? 0);
            self::assertGreaterThan(0, $orderId);

            /** @var TelegramSendLogRepository $logRepo */
            $logRepo = static::getContainer()->get(TelegramSendLogRepository::class);
            $log = $logRepo->findOneByShopIdAndOrderId('101', $orderId);
            self::assertNotNull($log);
            self::assertSame(TelegramSendStatus::SENT, $log->getStatus());

            /** @var MockHttpClient $http */
            $http = static::getContainer()->get('test.telegram_http_client');
            self::assertGreaterThanOrEqual(1, $http->getRequestsCount());
        } finally {
            $kernel->terminate($request, $response);
        }
    }

    #[RunInSeparateProcess]
    public function testRepeatedOrderCreatedMessageDoesNotDuplicateTelegramSendNorSendLogRows(): void
    {
        $kernel = self::$kernel;
        self::assertNotNull($kernel);

        $payload = [
            'number' => 'ORD-idem-'.bin2hex(random_bytes(4)),
            'total' => '99.00',
            'customerName' => 'Тест',
        ];

        $request = Request::create(
            '/shops/101/orders',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            (string) json_encode($payload, JSON_THROW_ON_ERROR)
        );

        $response = $kernel->handle($request);

        try {
            self::assertSame(201, $response->getStatusCode());
            $decoded = json_decode((string) $response->getContent(), true);
            self::assertIsArray($decoded);
            $orderId = (int) ($decoded['id'] ?? 0);
            self::assertGreaterThan(0, $orderId);

            /** @var MockHttpClient $http */
            $http = static::getContainer()->get('test.telegram_http_client');
            self::assertSame(1, $http->getRequestsCount());

            $message = new OrderCreatedMessage(
                orderId: $orderId,
                shopId: '101',
                number: $payload['number'],
                total: $payload['total'],
                customerName: $payload['customerName'],
                createdAt: $decoded['createdAt'],
            );

            /** @var MessageBusInterface $bus */
            $bus = static::getContainer()->get(MessageBusInterface::class);
            $bus->dispatch($message);
            $bus->dispatch($message);

            self::assertSame(1, $http->getRequestsCount());

            /** @var EntityManagerInterface $em */
            $em = static::getContainer()->get('doctrine.orm.default_entity_manager');
            $sendLogRows = (int) $em->createQueryBuilder()
                ->select('COUNT(l.id)')
                ->from(TelegramSendLog::class, 'l')
                ->where('l.shopId = :shopId')->andWhere('l.orderId = :orderId')
                ->setParameter('shopId', '101')
                ->setParameter('orderId', $orderId)
                ->getQuery()
                ->getSingleScalarResult();

            self::assertSame(1, $sendLogRows);

            /** @var TelegramSendLogRepository $logRepo */
            $logRepo = static::getContainer()->get(TelegramSendLogRepository::class);
            $logs = $logRepo->findBy(['shopId' => '101', 'orderId' => $orderId]);
            self::assertCount(1, $logs);
            self::assertSame(TelegramSendStatus::SENT, $logs[0]->getStatus());
        } finally {
            $kernel->terminate($request, $response);
        }
    }

    #[RunInSeparateProcess]
    public function testRetryHttpCreateOrderWithSameNumberDoesNotDuplicateTelegramSendNorSendLog(): void
    {
        $kernel = self::$kernel;
        self::assertNotNull($kernel);

        $payload = [
            'number' => 'ORD-retry-http-'.bin2hex(random_bytes(4)),
            'total' => '50.00',
            'customerName' => 'Повтор',
        ];
        $body = (string) json_encode($payload, JSON_THROW_ON_ERROR);

        $request1 = Request::create('/shops/101/orders', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], $body);
        $response1 = $kernel->handle($request1);

        try {
            self::assertSame(201, $response1->getStatusCode(), (string) $response1->getContent());
            $decoded1 = json_decode((string) $response1->getContent(), true);
            self::assertIsArray($decoded1);
            $orderId = (int) ($decoded1['id'] ?? 0);
            self::assertGreaterThan(0, $orderId);

            /** @var MockHttpClient $http */
            $http = static::getContainer()->get('test.telegram_http_client');
            self::assertSame(1, $http->getRequestsCount());

            $request2 = Request::create('/shops/101/orders', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], $body);
            $response2 = $kernel->handle($request2);

            try {
                self::assertSame(409, $response2->getStatusCode());
            } finally {
                $kernel->terminate($request2, $response2);
            }

            self::assertSame(1, $http->getRequestsCount());

            /** @var EntityManagerInterface $em */
            $em = static::getContainer()->get('doctrine.orm.default_entity_manager');
            $sendLogRows = (int) $em->createQueryBuilder()
                ->select('COUNT(l.id)')
                ->from(TelegramSendLog::class, 'l')
                ->where('l.shopId = :shopId')->andWhere('l.orderId = :orderId')
                ->setParameter('shopId', '101')
                ->setParameter('orderId', $orderId)
                ->getQuery()
                ->getSingleScalarResult();

            self::assertSame(1, $sendLogRows);
        } finally {
            $kernel->terminate($request1, $response1);
        }
    }

    #[RunInSeparateProcess]
    public function testTelegramFailurePersistsFailedSendLogWhileOrderStillCreated(): void
    {
        $kernel = self::$kernel;
        self::assertNotNull($kernel);

        /** @var MockHttpClient $http */
        $http = static::getContainer()->get('test.telegram_http_client');
        $http->setResponseFactory(static function (): MockResponse {
            return new MockResponse(
                (string) json_encode([
                    'ok' => false,
                    'description' => 'telegram api rejected send',
                ], JSON_THROW_ON_ERROR),
                ['http_code' => 200, 'headers' => ['Content-Type' => 'application/json']]
            );
        });

        $payload = [
            'number' => 'ORD-tg-fail-'.bin2hex(random_bytes(4)),
            'total' => '10.00',
            'customerName' => 'Ошибка TG',
        ];

        $request = Request::create(
            '/shops/101/orders',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            (string) json_encode($payload, JSON_THROW_ON_ERROR)
        );

        $response = $kernel->handle($request);

        try {
            self::assertSame(201, $response->getStatusCode(), (string) $response->getContent());
            $decoded = json_decode((string) $response->getContent(), true);
            self::assertIsArray($decoded);
            $orderId = (int) ($decoded['id'] ?? 0);
            self::assertGreaterThan(0, $orderId);

            /** @var EntityManagerInterface $em */
            $em = static::getContainer()->get('doctrine.orm.default_entity_manager');
            self::assertNotNull($em->find(Order::class, $orderId));

            /** @var TelegramSendLogRepository $logRepo */
            $logRepo = static::getContainer()->get(TelegramSendLogRepository::class);
            $log = $logRepo->findOneByShopIdAndOrderId('101', $orderId);
            self::assertNotNull($log);
            self::assertSame(TelegramSendStatus::FAILED, $log->getStatus());
            self::assertStringContainsString('telegram api rejected send', (string) $log->getError());

            self::assertGreaterThanOrEqual(1, $http->getRequestsCount());
        } finally {
            $kernel->terminate($request, $response);
        }
    }
}
