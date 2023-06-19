<?php

namespace App\Tests\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ClientControllerTest extends WebTestCase
{
    public function testIndexClients()
    {
        $client = static::createClient();
        $client->request('GET', '/clients/test');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertJson($client->getResponse()->getContent());
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertArrayHasKey('path', $responseData);
        $this->assertEquals('Welcome to your new client controller, this is a test!', $responseData['message']);
        $this->assertEquals('src/Controller/ClientController.php', $responseData['path']);
    }

    public function testGetAllClients()
    {
        // $this->markTestSkipped('The test to get all clients has been skipped.'); 

        $client = static::createClient();

        $userRepository = static::getContainer()->get(UserRepository::class);

        $testClient = $userRepository->findOneBy(['email' => 'admin@bilemo.com']);

        $client->loginUser($testClient);

        $client->request('GET', '/api/clients');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertJson($client->getResponse()->getContent());

        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(2, $responseData);

    }


    public function testGetAllClientsWithoutBeingAnAdmin()
    {
        // $this->markTestSkipped('The test to get all users has been skipped.');

        $client = static::createClient();

        $clientRepository = static::getContainer()->get(UserRepository::class);

        $testClient = $clientRepository->findOneBy(['email' => 'margesimpson@bilemo.com']);

        $client->loginUser($testClient);

        $client->request('GET', '/api/clients');

        $this->assertEquals(403, $client->getResponse()->getStatusCode());

    }
}
