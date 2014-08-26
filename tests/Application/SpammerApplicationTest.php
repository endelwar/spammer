<?php
namespace EndelWar\Spammer\Test;

use EndelWar\Spammer\Application;
use Symfony\Component\Console\Tester\ApplicationTester;

class SpammerTest extends \PHPUnit_Framework_TestCase
{
    private $spammer;

    protected function setUp()
    {
        $this->spammer = new Application\SpammerApplication();
        $this->spammer->setAutoExit(false); // Set autoExit to false when testing
    }

    public function testHelp()
    {
        $spammerTester = new ApplicationTester($this->spammer);
        $spammerTester->run(array('--help' => true));
        $output = $spammerTester->getDisplay();
        $this->assertContains('--server', $output);
        $this->assertContains('--port', $output);
        $this->assertContains('--count', $output);
    }

    public function testExecute()
    {
        $spammerTester = new ApplicationTester($this->spammer);
        $spammerTester->run(
            array(
                '-p' => '2500',
                '-c' => '3'
            )
        );
        $output = $spammerTester->getDisplay();
        $this->assertContains('Sending 3 email to server 127.0.0.1:2500', $output);
        $this->assertContains('Sent 3 messages', $output);
    }

    /* testing with option: -p 2500 (used by smtp-sink on travis-ci) */
    public function testSendUsingPort2500()
    {
        $spammerTester = new ApplicationTester($this->spammer);
        $spammerTester->run(
            array('-p' => '2500')
        );

        $this->assertContains('Sending 10 email to server 127.0.0.1:2500', $spammerTester->getDisplay());
        $this->assertContains('Sent 10 messages', $spammerTester->getDisplay());
    }

    /* testing with option: -s 127.0.0.1 */
    public function testSendUsingLocalIP()
    {
        $spammerTester = new ApplicationTester($this->spammer);
        $spammerTester->run(
            array(
                '-s' => '127.0.0.1',
                '-p' => '2500'
            )
        );

        $this->assertContains('Sending 10 email to server 127.0.0.1:2500', $spammerTester->getDisplay());
        $this->assertContains('Sent 10 messages', $spammerTester->getDisplay());
    }

    /* testing with option: -c 1 */
    public function testSendOneEmail()
    {
        $spammerTester = new ApplicationTester($this->spammer);
        $spammerTester->run(
            array(
                '-s' => '127.0.0.1',
                '-p' => '2500',
                '-c' => '1'
            )
        );

        $this->assertContains('Sending 1 email', $spammerTester->getDisplay());
        $this->assertContains('Sent 1 messages', $spammerTester->getDisplay());
    }

    /* testing with option: -c 5 */
    public function testSendFiveEmail()
    {
        $spammerTester = new ApplicationTester($this->spammer);
        $spammerTester->run(
            array(
                '-s' => '127.0.0.1',
                '-p' => '2500',
                '-c' => '5'
            )
        );

        $this->assertContains('Sending 5 email', $spammerTester->getDisplay());
        $this->assertContains('Sent 5 messages', $spammerTester->getDisplay());
    }

    public function testExecuteLocalePl()
    {
        $spammerTester = new ApplicationTester($this->spammer);
        $spammerTester->run(
            array(
                '-p' => '2500',
                '-c' => '1',
                '-l' => 'pl_PL'
            )
        );
        $output = $spammerTester->getDisplay();
        $this->assertContains('Sending 1 email to server 127.0.0.1:2500 using locale pl_PL', $output);
        $this->assertContains('Sent 1 messages', $output);
    }

    public function testWrongServerOptionLiteral()
    {
        $spammerTester = new ApplicationTester($this->spammer);
        $spammerTester->run(
            array(
                '-s' => 'localhost',
            )
        );
        $output = $spammerTester->getDisplay();
        $this->assertContains('InvalidArgumentException', $output);
        $this->assertContains('server option is not a valid IP', $output);
    }

    public function testWrongServerOptionIP()
    {
        $spammerTester = new ApplicationTester($this->spammer);
        $spammerTester->run(
            array(
                '-s' => '256.289.100.587',
            )
        );
        $output = $spammerTester->getDisplay();
        $this->assertContains('InvalidArgumentException', $output);
        $this->assertContains('server option is not a valid IP', $output);
    }

    public function testWrongCountOption()
    {
        $spammerTester = new ApplicationTester($this->spammer);
        $spammerTester->run(
            array(
                '-c' => 'Lorem',
            )
        );
        $output = $spammerTester->getDisplay();
        $this->assertContains('InvalidArgumentException', $output);
        $this->assertContains('count must be equal or greater than 1 (you want to send email, right?)', $output);
    }

    public function testWrongPortOptionTooLow()
    {
        $spammerTester = new ApplicationTester($this->spammer);
        $spammerTester->run(
            array(
                '-p' => '-123',
            )
        );
        $output = $spammerTester->getDisplay();
        $this->assertContains('InvalidArgumentException', $output);
        $this->assertContains('server port must be a number between 0 and 65536', $output);
    }

    public function testWrongPortOptionTooHigh()
    {
        $spammerTester = new ApplicationTester($this->spammer);
        $spammerTester->run(
            array(
                '-p' => '66000',
            )
        );
        $output = $spammerTester->getDisplay();
        $this->assertContains('InvalidArgumentException', $output);
        $this->assertContains('server port must be a number between 0 and 65536', $output);
    }

    public function testWrongPortOptionNaN()
    {
        $spammerTester = new ApplicationTester($this->spammer);
        $spammerTester->run(
            array(
                '-p' => 'Lorem',
            )
        );
        $output = $spammerTester->getDisplay();
        $this->assertContains('InvalidArgumentException', $output);
        $this->assertContains('server port must be a number between 0 and 65536', $output);
    }

    public function testMailerError()
    {
        $spammerTester = new ApplicationTester($this->spammer);
        $spammerTester->run(
            array(
                '-p' => '2501',
            )
        );
        $output = $spammerTester->getDisplay();
        $this->assertContains('Connection refused', $output);
        $this->assertContains('Connection could not be established with host 127.0.0.1', $output);
    }
}
