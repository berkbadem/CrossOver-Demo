<?php

/*
 * This file is part of the Crossover Demo package.
 *
 * (c) Berk BADEM <berkbadem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\BrowserKit\Cookie;

/**
 * Functional test for the controllers defined inside NewsController.
 *
 * Execute the application tests using this command (requires PHPUnit ^5.7.21 to be installed):
 *
 *     $ cd crossover.dev/
 *     $ php phpunit.phar -c app/ -v
 */
class NewsControllerTest extends WebTestCase
{
    /**
     * Valid Username constant
     */
    const LOGIN_VALID = 'wartoghex';

    /**
     * Base test url constant for testing pdf generation
     */
    const BASE_TEST_URL = 'crossover.dev/';

    /**
     * @var Client
     *
     * Client for testing units
     *
     */
    private $client;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * Setup function for unit tests, it creates unit test client and database manager
     */
    protected function setUp()
    {
        $this->client = static::createClient();
        self::bootKernel();
        $this->em = static::$kernel->getContainer()
            ->get('doctrine')
            ->getManager();
    }

    /**
     * Test for index page. Tests page is reachable, tests is branch name is present
     */
    public function testIndex()
    {
        $crawler = $this->client->request('GET', '/');

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode(), "Homepage load failed");
        $this->assertContains('Newsstand', $crawler->filter('body > nav > a')->text(), "Home page load failed, Brand not shown");
    }

    /**
     * Test index page for items. Tests is page reachable, tests is page have items, tests details page.
     */
    public function testIndexItems()
    {
        $news = $this->em->getRepository("AppBundle:News")->findAll();

        if (count($news) == 0) {
            $this->markTestSkipped("No input data found in database. Skipping");
        }

        $crawler = $this->client->request('GET', '/');
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode(), "Homepage load failed");

        $button  = $crawler->selectLink("read more")->link();
        $crawler = $this->client->click($button);

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode(),"Details page load failed");

        $this->assertContains('Publisher', $crawler->filter('div.card-header > div > div:nth-child(1) > small')->text(), "Details page load failed, publisher not found");
    }

    /**
     * Test for add new article page. Tests is user can reach without logging in, tests file upload, tests article is added
     */
    public function testAddNewArticle()
    {
        $user = $this->em->getRepository("AppBundle:User")->findOneBy(
            ['username' => static::LOGIN_VALID]
        );

        if (!$user) {
            $this->markTestIncomplete("Valid user not found in database");
        }

        $this->client->request('GET', '/news/new');


        $this->assertRegExp('/\/login/', $this->client->getResponse()->headers->get('location'), "Login redirect failed when user not present");

        $this->loginWithUser();

        $crawler = $this->client->request('GET', '/news/new');

        $form = $crawler->selectButton('Reset')->form();

        $form['news[title]'] = "Test title for news";
        $form['news[text]'] = "Test description for news";

        $file = tempnam(sys_get_temp_dir(), 'test');
        imagepng(imagecreatetruecolor(700, 500), $file);
        $image = new UploadedFile(
            $file,
            'imagetest.png',
            'image/png'
        );

        $form['news[image]'] = $image;

        $this->client->submit($form);

        $this->assertContains('Your news has been added.', $this->client->getResponse()->getContent(),"Article inserting failed");

        $this->client->getCookieJar()->clear();
    }

    /**
     * Test for my articles page. Tests is page is valid, is user can reach anonymously, tests is article present, tests is page working correctly
     */
    public function testMyArticles()
    {
        $user = $this->em->getRepository("AppBundle:User")->findOneBy(
            ['username' => static::LOGIN_VALID]
        );

        if (!$user) {
            $this->markTestIncomplete("Valid user not found in database");
        }

        $this->client->request('GET', '/news/list');

        $this->assertRegExp('/\/login/', $this->client->getResponse()->headers->get('location'), "Login redirect failed, when user not present");

        $this->loginWithUser();

        $crawler = $this->client->request('GET', '/news/list');

        $this->assertContains('My Articles', $this->client->getResponse()->getContent(), "My articles loading failed");

        $button  = $crawler->selectLink("read more")->link();
        $crawler = $this->client->click($button);

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode(), "Details page loading failed");

        $this->assertContains('Publisher', $crawler->filter('div.card-header > div > div:nth-child(1) > small')->text(), "Details page loading failed no publisher present");

        $this->client->getCookieJar()->clear();
    }

    /**
     * Test for downloading article. Tests to download article using ui and checks its header is correct
     */
    public function testDownloadArticle() {
        $this->client = self::createClient(array(), array(
            'HTTP_HOST' => static::BASE_TEST_URL,
        ));

        $user = $this->em->getRepository("AppBundle:User")->findOneBy(
            ['username' => static::LOGIN_VALID]
        );

        if (!$user) {
            $this->markTestIncomplete("Valid user not found in database");
        }


        $news = $this->em->getRepository("AppBundle:News")->findOneBy(
            ['user' => $user, 'title' => "Test title for news"]
        );

        if (!$news) {
            $this->markTestIncomplete("Valid test article not found");
        }

        $this->client->request('GET', '/news/list');

        $this->assertTrue($this->client->getResponse()->isRedirect(), "Login page redirect failed with wrong user");

        $this->loginWithUser();

        $crawler = $this->client->request('GET', '/news/list');

        $this->assertContains('My Articles', $this->client->getResponse()->getContent(), "My Articles page loading failed My Articles not present");

        $button  = $crawler->selectLink("read more")->link();
        $crawler = $this->client->click($button);

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode(), "Details page loading failed");

        $this->assertContains('Publisher', $crawler->filter('div.card-header > div > div:nth-child(1) > small')->text(), "Details page loading failed, publisher not present");

        $button  = $crawler->selectLink("download as pdf")->link();
        $this->client->click($button);

        $this->assertEquals('application/pdf', $this->client->getResponse()->headers->get('Content-Type'), "Download failed, Content type mismatch");
    }

    /**
     * Test for deleting article. Tests is article present at my articles page, tests to delete article and verify it
     */
    public function testDeleteArticle()
    {
        $user = $this->em->getRepository("AppBundle:User")->findOneBy(
            ['username' => static::LOGIN_VALID]
        );

        if (!$user) {
            $this->markTestIncomplete("Valid user not found in database");
        }


        $news = $this->em->getRepository("AppBundle:News")->findOneBy(
            ['user' => $user, 'title' => "Test title for news"]
        );

        if (!$news) {
            $this->markTestIncomplete("Valid test article not found");
        }


        $this->client->request('GET', '/news/list');

        $this->assertRegExp('/\/login/', $this->client->getResponse()->headers->get('location'), "Login redirect failed");

        $this->loginWithUser();

        $crawler = $this->client->request('GET', '/news/list');

        $this->assertContains('My Articles', $this->client->getResponse()->getContent(), "My articles loading failed, My Articles not present");

        $button  = $crawler->selectLink("delete")->link();
        $this->client->click($button);

        $this->assertTrue($this->client->getResponse()->isRedirect(), "Delete redirection failed");

        $news = $this->em->getRepository("AppBundle:News")->findOneBy(
            ['user' => $user, 'title' => "Test title for news"]
        );

        if ($news) {
            $this->markTestIncomplete("Valid test article found");
        }
    }

    /**
     * Test for rss news page. Tests rss news page is reachable, tests its header is correct and tests its content
     */
    public function testRSSNews() {
        $this->client->request('GET', '/news/rss');

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode(), "RSS page loading failed");


        $this->assertSame(
            'text/xml; charset=UTF-8',
            $this->client->getResponse()->headers->get('Content-Type')
        );

        $this->assertContains('Highlighted Articles', $this->client->getResponse()->getContent(), "RSS loading failed, Highlighted Articles not present");
    }

    /**
     * Logging in to system with given user infomation with token generator
     */
    protected function loginWithUser()
    {
        $container = static::$kernel->getContainer();
        $session = $container->get('session');
        $user = $this->em->getRepository('AppBundle:User')->findOneBy(array('username' => static::LOGIN_VALID));

        $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
        $session->set('_security_main', serialize($token));
        $session->save();

        $this->client->getCookieJar()->set(new Cookie($session->getName(), $session->getId()));
    }


    /**
     * Closes doctrine connections and unit test client
     */
    protected function tearDown()
    {
        $this->em->close();
        $this->em = null;
        $this->client = null;
    }
}
