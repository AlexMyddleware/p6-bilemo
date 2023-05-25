<?php

namespace App\Tests\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CustomerControllerTest extends WebTestCase
{
    public function testIndexClients()
    {
        $client = static::createClient();
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

        $client = static::createClient();

        $userRepository = static::getContainer()->get(UserRepository::class);

        $testClient = $userRepository->findOneBy(['email' => 'admin@bilemo.com']);

        $client->loginUser($testClient);

        $client->request('GET', '/api/customers');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertJson($client->getResponse()->getContent());

        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(3, $responseData);

    }

    public function testGetAllCustomersOfTheNormalClient()
    {
        // $this->markTestSkipped('The test to get all clients has been skipped.');

        $client = static::createClient();

        $userRepository = static::getContainer()->get(UserRepository::class);

        $testClient = $userRepository->findOneBy(['email' => 'margesimpson@bilemo.com']);

        $client->loginUser($testClient);

        $client->request('GET', '/api/customers');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertJson($client->getResponse()->getContent());

        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(2, $responseData);

    }

    public function testGetOneCustomerOfTheAdminClient()
    {
        // $this->markTestSkipped('The test to get one client has been skipped.');

        $client = static::createClient();

        $userRepository = static::getContainer()->get(UserRepository::class);

        $testClient = $userRepository->findOneBy(['email' => 'admin@bilemo.com']);

        $client->loginUser($testClient);

        $client->request('GET', '/api/customers/3');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertJson($client->getResponse()->getContent());

        $responseData = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('email', $responseData);
        $this->assertArrayHasKey('password', $responseData);
    }

    public function testGetOneCustomerOfTheNormalClient()
    {
        // $this->markTestSkipped('The test to get one client has been skipped.');

        $client = static::createClient();

        $userRepository = static::getContainer()->get(UserRepository::class);

        $testClient = $userRepository->findOneBy(['email' => 'margesimpson@bilemo.com']);

        $client->loginUser($testClient);

        $client->request('GET', '/api/customers/1');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertJson($client->getResponse()->getContent());

        $responseData = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('email', $responseData);
        $this->assertArrayHasKey('password', $responseData);

    }

    public function testGetOneCustomerFromWrongClient()
    {
        // $this->markTestSkipped('The test to get one client has been skipped.');

        $client = static::createClient();

        $userRepository = static::getContainer()->get(UserRepository::class);

        $testClient = $userRepository->findOneBy(['email' => 'margesimpson@bilemo.com']);

        $client->loginUser($testClient);

        $client->request('GET', '/api/customers/3');

        $this->assertEquals(403, $client->getResponse()->getStatusCode());

        $this->assertJson($client->getResponse()->getContent());

        $responseData = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('message', $responseData);

    }
}
