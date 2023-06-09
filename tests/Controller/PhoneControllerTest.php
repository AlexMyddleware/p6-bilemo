<?php

namespace App\Tests\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PhoneControllerTest extends WebTestCase
{
    public function testIndexPhones()
    {
        $client = static::createClient();
        $client->request('GET', '/phones/test');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertJson($client->getResponse()->getContent());
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertArrayHasKey('path', $responseData);
        $this->assertEquals('Welcome to your new phone controller, this is a test!', $responseData['message']);
        $this->assertEquals('src/Controller/PhoneController.php', $responseData['path']);
    }

    public function testGetAllPhones()
    {
        $client = static::createClient();

        $userRepository = static::getContainer()->get(UserRepository::class);

        $testUser = $userRepository->findOneBy(['email' => 'margesimpson@bilemo.com']);

        $client->loginUser($testUser);

        $client->request('GET', '/api/phones', [], [], ['CONTENT_TYPE' => 'application/json'], '{"page": 2, "limit": 10}');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertJson($client->getResponse()->getContent());

        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(10, $responseData);

        foreach ($responseData as $phone) {
            $this->assertArrayHasKey('id', $phone);
            $this->assertArrayHasKey('name', $phone);
        }
    }


    public function testGetPhone()
    {
        $client = static::createClient();

        $userRepository = static::getContainer()->get(UserRepository::class);

        $testUser = $userRepository->findOneBy(['email' => 'margesimpson@bilemo.com']);

        $client->loginUser($testUser);

        // Make a GET request to the /api/phones/{id} endpoint with an ID of 1
        $client->request('GET', '/api/phones/1');

        // Assert that the response status code is 200 OK
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        // Assert that the response content type is JSON
        $this->assertTrue($client->getResponse()->headers->contains('Content-Type', 'application/json'));

        $phone = json_decode($client->getResponse()->getContent(), true);

        $brands = ['Apple', 'Samsung', 'Huawei', 'Xiaomi', 'Oppo', 'Vivo'];
        $models = ['A', 'B', 'C', 'D', 'E', 'F'];

            $this->assertArrayHasKey('id', $phone);
            $this->assertArrayHasKey('name', $phone);
            $this->assertArrayHasKey('quantity', $phone);
            $this->assertArrayHasKey('model', $phone);
            $this->assertArrayHasKey('brand', $phone);

            $this->assertGreaterThanOrEqual(0, $phone['quantity']);
            $this->assertLessThanOrEqual(100, $phone['quantity']);
            $this->assertContains($phone['brand'], $brands);
            $this->assertContains($phone['model'], $models);
    }

    public function testGetPhoneNotFound()
    {
        $client = static::createClient();

        $userRepository = static::getContainer()->get(UserRepository::class);

        $testUser = $userRepository->findOneBy(['email' => 'margesimpson@bilemo.com']);

        $client->loginUser($testUser);

        // Make a GET request to the /api/phones/{id} endpoint with an ID of 1000
        $client->request('GET', '/api/phones/1000');

        // Assert that the response status code is 404 NOT FOUND
        $this->assertEquals(404, $client->getResponse()->getStatusCode());

        // Assert that the response content type is JSON
        $this->assertTrue($client->getResponse()->headers->contains('Content-Type', 'application/json'));

        $responseData = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('message', $responseData);

        $this->assertEquals('Phone not found', $responseData['message']);
    }
}
