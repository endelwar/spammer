<?php

namespace EndelWar\Spammer\Test;

use EndelWar\Spammer\Application;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\ApplicationTester;

class SpammerApplicationTest extends TestCase
{
    /** @var Application\SpammerApplication $spammer */
    private $spammer;

    protected function setUp()
    {
        $this->spammer = new Application\SpammerApplication();
        $this->spammer->setAutoExit(false); // Set autoExit to false when testing
    }

    protected function tearDown()
    {
        $this->spammer = null;
    }

    public function testHelp()
    {
        $spammerTester = new ApplicationTester($this->spammer);
        $spammerTester->run(['--help' => true]);
        $output = $spammerTester->getDisplay();
        $this->assertContains('--server', $output);
        $this->assertContains('--port', $output);
        $this->assertContains('--count', $output);
        $this->assertContains('--to', $output);
        $this->assertContains('--from', $output);
    }

    public function testExecute()
    {
        $spammerTester = new ApplicationTester($this->spammer);
        $spammerTester->run(
            [
                '-p' => '2500',
                '-c' => '3',
            ]
        );
        $output = $spammerTester->getDisplay();
        $this->assertContains('Sending 3 email to server 127.0.0.1:2500', $output);
        $this->assertContains('Sent 3 messages', $output);
    }

    /* testing with option: -p 2500 (used by postfix smtp-sink on travis-ci) */
    public function testSendUsingPort2500()
    {
        $spammerTester = new ApplicationTester($this->spammer);
        $spammerTester->run(
            ['-p' => '2500']
        );

        $this->assertContains('Sending 10 email to server 127.0.0.1:2500', $spammerTester->getDisplay());
        $this->assertContains('Sent 10 messages', $spammerTester->getDisplay());
    }

    /* testing with option: -s 127.0.0.1 */
    public function testSendUsingLocalIP()
    {
        $spammerTester = new ApplicationTester($this->spammer);
        $spammerTester->run(
            [
                '-s' => '127.0.0.1',
                '-p' => '2500',
            ]
        );

        $this->assertContains('Sending 10 email to server 127.0.0.1:2500', $spammerTester->getDisplay());
        $this->assertContains('Sent 10 messages', $spammerTester->getDisplay());
    }

    /* testing with option: -c 1 */
    public function testSendOneEmail()
    {
        $spammerTester = new ApplicationTester($this->spammer);
        $spammerTester->run(
            [
                '-s' => '127.0.0.1',
                '-p' => '2500',
                '-c' => '1',
            ]
        );

        $this->assertContains('Sending 1 email', $spammerTester->getDisplay());
        $this->assertContains('Sent 1 messages', $spammerTester->getDisplay());
    }

    /* testing with option: -c 5 */
    public function testSendFiveEmail()
    {
        $spammerTester = new ApplicationTester($this->spammer);
        $spammerTester->run(
            [
                '-s' => '127.0.0.1',
                '-p' => '2500',
                '-c' => '5',
            ]
        );

        $this->assertContains('Sending 5 email', $spammerTester->getDisplay());
        $this->assertContains('Sent 5 messages', $spammerTester->getDisplay());
    }

    public function testExecuteLocalePl()
    {
        $spammerTester = new ApplicationTester($this->spammer);
        $spammerTester->run(
            [
                '-p' => '2500',
                '-c' => '1',
                '-l' => 'pl_PL',
            ]
        );
        $output = $spammerTester->getDisplay();
        $this->assertContains('Sending 1 email to server 127.0.0.1:2500 using locale pl_PL', $output);
        $this->assertContains('Sent 1 messages', $output);
    }

    public function testWrongServerOptionLiteral()
    {
        $spammerTester = new ApplicationTester($this->spammer);
        $spammerTester->run(
            [
                '-s' => 'localhost',
            ]
        );
        $output = $spammerTester->getDisplay();
        $this->assertContains(\InvalidArgumentException::class, $output);
        $this->assertContains('server option is not a valid IP', $output);
    }

    public function testWrongServerOptionIP()
    {
        $spammerTester = new ApplicationTester($this->spammer);
        $spammerTester->run(
            [
                '-s' => '256.289.100.587',
            ]
        );
        $output = $spammerTester->getDisplay();
        $this->assertContains(\InvalidArgumentException::class, $output);
        $this->assertContains('server option is not a valid IP', $output);
    }

    public function testWrongCountOption()
    {
        $spammerTester = new ApplicationTester($this->spammer);
        $spammerTester->run(
            [
                '-c' => 'Lorem',
            ]
        );
        $output = $spammerTester->getDisplay();
        $this->assertContains(\InvalidArgumentException::class, $output);
        $this->assertContains('count must be equal or greater than 1 (you want to send email, right?)', $output);
    }

    public function testWrongPortOptionTooLow()
    {
        $spammerTester = new ApplicationTester($this->spammer);
        $spammerTester->run(
            [
                '-p' => '-123',
            ]
        );
        $output = $spammerTester->getDisplay();
        $this->assertContains(\InvalidArgumentException::class, $output);
        $this->assertContains('server port must be a number between 0 and 65536', $output);
    }

    public function testWrongPortOptionTooHigh()
    {
        $spammerTester = new ApplicationTester($this->spammer);
        $spammerTester->run(
            [
                '-p' => '66000',
            ]
        );
        $output = $spammerTester->getDisplay();
        $this->assertContains(\InvalidArgumentException::class, $output);
        $this->assertContains('server port must be a number between 0 and 65536', $output);
    }

    public function testWrongPortOptionNaN()
    {
        $spammerTester = new ApplicationTester($this->spammer);
        $spammerTester->run(
            [
                '-p' => 'Lorem',
            ]
        );
        $output = $spammerTester->getDisplay();
        $this->assertContains(\InvalidArgumentException::class, $output);
        $this->assertContains('server port must be a number between 0 and 65536', $output);
    }

    public function testMailerError()
    {
        $spammerTester = new ApplicationTester($this->spammer);
        $spammerTester->run(
            [
                '-p' => '2501',
            ]
        );
        $output = $spammerTester->getDisplay();
        $this->assertContains('Connection refused', $output);
        $this->assertContains('Connection could not be established with host 127.0.0.1', $output);
    }

    public function testToAddress()
    {
        $spammerTester = new ApplicationTester($this->spammer);
        $spammerTester->run(
            [
                '-c' => '1',
                '-p' => '2500',
                '-t' => 'user@example.org',
            ]
        );
        $output = $spammerTester->getDisplay();
        $this->assertContains('Sending 1 email to server 127.0.0.1:2500 to user@example.org using locale en_US', $output);
        $this->assertContains('Sent 1 messages', $output);
    }

    public function testToFqdn()
    {
        $spammerTester = new ApplicationTester($this->spammer);
        $spammerTester->run(
            [
                '-c' => '1',
                '-p' => '2500',
                '-t' => 'example.org',
            ]
        );
        $output = $spammerTester->getDisplay();

        $this->assertContains('Sending 1 email to server 127.0.0.1:2500 to example.org using locale en_US', $output);
        $this->assertRegExp('/Sending email nr. 1: \S+@\S+\.\S{2,} => \S+@example.org/', $output);
        $this->assertContains('Sent 1 messages', $output);
    }

    public function testFromAddress()
    {
        $spammerTester = new ApplicationTester($this->spammer);
        $spammerTester->run(
            [
                '-c' => '1',
                '-p' => '2500',
                '-f' => 'user@example.org',
            ]
        );
        $output = $spammerTester->getDisplay();
        $this->assertContains('Sending 1 email to server 127.0.0.1:2500 from user@example.org using locale en_US', $output);
        $this->assertContains('Sent 1 messages', $output);
    }

    public function testFromFqdn()
    {
        $spammerTester = new ApplicationTester($this->spammer);
        $spammerTester->run(
            [
                '-c' => '1',
                '-p' => '2500',
                '-f' => 'example.org',
            ]
        );
        $output = $spammerTester->getDisplay();

        $this->assertContains('Sending 1 email to server 127.0.0.1:2500 from example.org using locale en_US', $output);
        $this->assertRegExp('/Sending email nr. 1: \S+@example.org => \S+@\S+\.\S{2,}/', $output);
        $this->assertContains('Sent 1 messages', $output);
    }

    public function testFromAddressToAddress()
    {
        $spammerTester = new ApplicationTester($this->spammer);
        $spammerTester->run(
            [
                '-c' => '1',
                '-p' => '2500',
                '-f' => 'user1@example.org',
                '-t' => 'user2@example.com',
            ]
        );
        $output = $spammerTester->getDisplay();
        $this->assertContains('Sending 1 email to server 127.0.0.1:2500 from user1@example.org to user2@example.com using locale en_US', $output);
        $this->assertContains('Sending email nr. 1: user1@example.org => user2@example.com', $output);
        $this->assertContains('Sent 1 messages', $output);
    }

    /**
     * @param $invalidFromTo
     *
     * @dataProvider badFromToProvider
     */
    public function testBadFromTo($invalidFromTo)
    {
        $spammerTester = new ApplicationTester($this->spammer);
        $spammerTester->run(
            [
                '-c' => '1',
                '-p' => '2500',
                '-f' => $invalidFromTo,
            ]
        );
        $output = $spammerTester->getDisplay();
        $this->assertContains(\InvalidArgumentException::class, $output);
        $this->assertContains('to and from must be a valid email address or a FQDN', $output);
    }

    public function badFromToProvider()
    {
        return [
            'boolean' => [true],
            'string' => ['abcd'],
            'email: single char' => ['@'],
            'email: only domain' => ['@test'],
            'email: localhost' => ['me@localhost'],
            'email: double dot' => ['test@example..com'],
            'email: dot at end' => ['test@example.com.'],
            'email: dot at start' => ['.test@example.com'],
            'email: double @' => ['test@test@example.com'],
            'domain: dot at end' => ['notvalid.com.'],
            'domain: dot at start' => ['.notvalid.com'],
            'domain: minus at start' => ['-notvalid.com'],
            'domain: minus at end' => ['notvalid.com-'],
        ];
    }
}
