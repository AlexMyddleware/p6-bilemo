<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Entity\Customer;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CustomerControllerTest extends WebTestCase
{

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    private $client;

    // Function to set up the tests so that the database is not modified after each test
    public function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
        $container = self::$kernel->getContainer();
        $this->entityManager = $container->get('doctrine')->getManager();
        $this->entityManager->beginTransaction();
        $this->entityManager->getConnection()->setAutoCommit(false);

        // cache clearing
        $cache = static::getContainer()->get('cache.app');
        $cache->clear();

    }

    public function tearDown(): void
    {
        if ($this->entityManager->getConnection()->isTransactionActive()) {
            $this->entityManager->rollback();
        }
        $this->entityManager->close();
        $this->entityManager = null; // avoid memory leaks

        // cache clearing
        $cache = static::getContainer()->get('cache.app');
        $cache->clear();

        parent::tearDown();
    }

    public function testIndexCustomers()
    {
        $client = $this->client;
        $client->request('GET', '/customers/test');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertJson($client->getResponse()->getContent());
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertArrayHasKey('path', $responseData);
        $this->assertEquals('Welcome to your new customer controller, this is a test!', $responseData['message']);
        $this->assertEquals('src/Controller/CustomerController.php', $responseData['path']);
    }

    public function testGetAllCustomersOfTheAdminClient()
    {
        // $this->markTestSkipped('The test to get all clients has been skipped.');

        $client = $this->client;

        $userRepository = static::getContainer()->get(UserRepository::class);

        $testClient = $userRepository->findOneBy(['email' => 'admin@bilemo.com']);

        $client->loginUser($testClient);

        $client->request('GET', '/api/customers?page=1&limit=10', [], [], ['CONTENT_TYPE' => 'application/json']);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertJson($client->getResponse()->getContent());

        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(5, $responseData);
    }

    public function testGetAllCustomersOfTheNormalClient()
    {
        // $this->markTestSkipped('The test to get all clients has been skipped.');

        $client = $this->client;

        $userRepository = static::getContainer()->get(UserRepository::class);

        $testClient = $userRepository->findOneBy(['email' => 'margesimpson@bilemo.com']);

        $client->loginUser($testClient);

        // do a request with a limit of 1 customer and page 1, in query parameters
        $client->request('GET', '/api/customers?page=1&limit=1', [], [], ['CONTENT_TYPE' => 'application/json']);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertJson($client->getResponse()->getContent());

        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(1, $responseData);

    }

    public function testGetOneCustomerOfTheAdminClient()
    {
        // $this->markTestSkipped('The test to get one client has been skipped.');

        $client = $this->client;

        $userRepository = static::getContainer()->get(UserRepository::class);

        $testClient = $userRepository->findOneBy(['email' => 'admin@bilemo.com']);

        $client->loginUser($testClient);

        $client->request('GET', '/api/customers/3');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertJson($client->getResponse()->getContent());

        $responseData = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('email', $responseData);
        $this->assertArrayNotHasKey('password', $responseData);
        
    }

    public function testGetOneCustomerOfTheAdminClientInV2()
    {
        // $this->markTestSkipped('The test to get one client has been skipped.');

        $client = $this->client;

        $userRepository = static::getContainer()->get(UserRepository::class);

        $testClient = $userRepository->findOneBy(['email' => 'admin@bilemo.com']);

        $client->loginUser($testClient);

        $client->request('GET', '/api/customers/3', [], [], [
            'HTTP_ACCEPT' => 'application/json; version=2.0',
        ]);
        

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertJson($client->getResponse()->getContent());

        $responseData = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('email', $responseData);

        $this->assertArrayHasKey('password', $responseData);
    }

    public function testGetOneCustomerOfTheNormalClient()
    {
        // $this->markTestSkipped('The test to get one client has been skipped.');

        $client = $this->client;

        $userRepository = static::getContainer()->get(UserRepository::class);

        $testClient = $userRepository->findOneBy(['email' => 'margesimpson@bilemo.com']);

        $client->loginUser($testClient);

        $client->request('GET', '/api/customers/1');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertJson($client->getResponse()->getContent());

        $responseData = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('email', $responseData);
        $this->assertArrayNotHasKey('password', $responseData);

    }
    
    public function testGetOneCustomerOfTheNormalClientInV2()
    {
        // $this->markTestSkipped('The test to get one client has been skipped.');

        $client = $this->client;

        $userRepository = static::getContainer()->get(UserRepository::class);

        $testClient = $userRepository->findOneBy(['email' => 'margesimpson@bilemo.com']);

        $client->loginUser($testClient);

        $client->request('GET', '/api/customers/2', [], [], [
            'HTTP_ACCEPT' => 'application/json; version=2.0',
        ]);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertJson($client->getResponse()->getContent());

        $responseData = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('email', $responseData);
        $this->assertArrayHasKey('password', $responseData);

    }

    public function testGetOneCustomerFromWrongClient()
    {
        // $this->markTestSkipped('The test to get one client has been skipped.');

        $client = $this->client;

        $userRepository = static::getContainer()->get(UserRepository::class);

        $testClient = $userRepository->findOneBy(['email' => 'margesimpson@bilemo.com']);

        $client->loginUser($testClient);

        $client->request('GET', '/api/customers/3');

        $this->assertEquals(404, $client->getResponse()->getStatusCode());

        $this->assertJson($client->getResponse()->getContent());

        $responseData = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('message', $responseData);

    }

    public function testCreateOneCustomerAsANormalClient()
    {
        // $this->markTestSkipped('The test to create one client has been skipped.');

        $client = $this->client;

        $userRepository = static::getContainer()->get(UserRepository::class);

        $testClient = $userRepository->findOneBy(['email' => 'margesimpson@bilemo.com']);

        $client->loginUser($testClient);

        $client->request('POST', '/api/customers', [], [], ['CONTENT_TYPE' => 'application/json'], '{"email": "journal@gmail.com", "password": "jokari892"}');

        $this->assertEquals(201, $client->getResponse()->getStatusCode());
    }

    public function testUpdateCustomer()
    {
        $client = $this->client;

        $userRepository = static::getContainer()->get(UserRepository::class);

        $testClient = $userRepository->findOneBy(['email' => 'margesimpson@bilemo.com']);

        $client->loginUser($testClient);

        // Update the customer with id 1, with a new email and password
        $client->request('PUT', '/api/customers/1', [], [],
            ['CONTENT_TYPE' => 'application/json'], '{"email":"newemail@example.com","password":"newpassword"}');

        // Check that the response is successful
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        // Check that the customer was updated correctly
        $customer = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('newemail@example.com', $customer['email']);
    }

    public function testDeleteCustomer()
    {
        $client = $this->client;

        $userRepository = static::getContainer()->get(UserRepository::class);

        $testClient = $userRepository->findOneBy(['email' => 'margesimpson@bilemo.com']);

        $token = $this->generateTokenForUser($testClient);

        $client->setServerParameter('HTTP_Authorization', sprintf('Bearer %s', $token));

        // Create a new customer
        $newCustomer = new Customer();
        $newCustomer->setEmail('testcustomer@example.com');
        $newCustomer->setPassword('testpassword');
        $newCustomer->setClient($testClient);


        $this->entityManager->persist($newCustomer);
        $this->entityManager->flush();

        $newCustomerId = $newCustomer->getId();


        $client->request('DELETE', '/api/customers/' . $newCustomerId);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        // Manually commit the transaction
        $this->entityManager->commit();

        // Try to GET the deleted customer
        $client->request('GET', '/api/customers/' . $newCustomerId);

        // Confirm the customer is not found
        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }


    private function generateTokenForUser(User $user)
    {
        $jwtManager = static::getContainer()->get('lexik_jwt_authentication.jwt_manager');

        return $jwtManager->create($user);
    }
}
