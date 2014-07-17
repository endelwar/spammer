<?php
namespace EndelWar\Spammer\Test\Command;

use EndelWar\Spammer\Application;
use EndelWar\Spammer\Command;
use Symfony\Component\Console\Tester\CommandTester;

class SpammerCommandTest extends \PHPUnit_Framework_TestCase
{
    protected $application;
    protected $command;

    public function setUp()
    {
        parent::setUp();

        $this->application = new Application\SpammerApplication();
        $this->command = $this->application->find('spammer');
    }

    /* testing without options */
    public function testExecute()
    {
        $commandTester = new CommandTester($this->command);
        $commandTester->execute(array());

        $this->assertRegExp('/Sending 10 email/', $commandTester->getDisplay());
        $this->assertRegExp('/Sent 10 messages/', $commandTester->getDisplay());
    }

    /* testing with option: -c 1 */
    public function testSendOneEmail()
    {
        $commandTester = new CommandTester($this->command);
        $commandTester->execute(
            array('-c' => '1')
        );

        $this->assertRegExp('/Sending 1 email/', $commandTester->getDisplay());
        $this->assertRegExp('/Sent 1 messages/', $commandTester->getDisplay());
    }

    /* testing with option: -c 5 */
    public function testSendFiveEmail()
    {
        $commandTester = new CommandTester($this->command);
        $commandTester->execute(
            array('-c' => '5')
        );

        $this->assertRegExp('/Sending 5 email/', $commandTester->getDisplay());
        $this->assertRegExp('/Sent 5 messages/', $commandTester->getDisplay());
    }

    /* testing with option: -s 127.0.0.1 */
    public function testSendUsingLocalhostIP()
    {
        $commandTester = new CommandTester($this->command);
        $commandTester->execute(
            array('-s' => '127.0.0.1')
        );

        $this->assertRegExp('/Sending 10 email using server 127.0.0.1:25/', $commandTester->getDisplay());
        $this->assertRegExp('/Sent 10 messages/', $commandTester->getDisplay());
    }

    /* testing with option: -s 192.168.1.51 */
    public function testSendUsingLocalIP()
    {
        $commandTester = new CommandTester($this->command);
        $commandTester->execute(
            array('-s' => '192.168.1.51')
        );

        $this->assertRegExp('/Sending 10 email using server 192.168.1.51:25/', $commandTester->getDisplay());
        $this->assertRegExp('/Sent 10 messages/', $commandTester->getDisplay());
    }

    /* testing with option: -p 1025 (used by mailcatcher) */
    public function testSendUsingPort1025()
    {
        $commandTester = new CommandTester($this->command);
        $commandTester->execute(
            array('-p' => '1025')
        );

        $this->assertRegExp('/Sending 10 email using server 127.0.0.1:1025/', $commandTester->getDisplay());
        $this->assertRegExp('/Sent 10 messages/', $commandTester->getDisplay());
    }
}