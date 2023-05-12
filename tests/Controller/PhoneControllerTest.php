<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

// use advanced test case


class PhoneControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $client = static::createClient();
        $client->request('GET', '/phone/test');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertJson($client->getResponse()->getContent());
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertArrayHasKey('path', $responseData);
        $this->assertEquals('Welcome to your new phone controller, this is a test!', $responseData['message']);
        $this->assertEquals('src/Controller/BookController.php', $responseData['path']);
    }

    public function testGetAllPhones()
    {
        $client = static::createClient();
        $client->request('GET', '/phone');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        // dump($client->getResponse()->getContent());

        $this->assertTrue(true);
    }
}