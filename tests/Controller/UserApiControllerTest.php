<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserApiControllerTest extends WebTestCase
{
    public function testDeleteUser()
    {
        $client = static::createClient();
        
        $client->request(
            'DELETE',
            '/api/users/1',
            array(),
            array(),
            array('X-AUTH-TOKEN' => 'abcdefg32143')
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertContains('users', $client->getResponse()->getContent());
    }
    
    public function testUpdateUser()
    {
        $client = static::createClient();
        
        $client->request(
            'PUT',
            '/api/users/1',
            array(),
            array(),
            array('X-AUTH-TOKEN' => 'abcdefg32143'),
            '{"email":"b@test.com","roles":["ROLE_ADMIN","ROLE_USER"],"name":"jewel","userGroups":[]}'
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertContains('users', $client->getResponse()->getContent());
    }
    
    public function testFetchAllUsers()
    {
        $client = static::createClient();
        $client->request('GET', '/api/users');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertContains('users', $client->getResponse()->getContent());
    }
    
    public function testFetchUser()
    {
        $client = static::createClient();
        $client->request('GET', '/api/users/1');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertContains('user', $client->getResponse()->getContent());
    }       
}