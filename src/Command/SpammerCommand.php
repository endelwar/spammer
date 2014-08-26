<?php
namespace EndelWar\Spammer\Command;

use Faker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SpammerCommand extends Command
{
    protected function configure()
    {
        $count = 10;
        $smtpServerIp = '127.0.0.1';
        $smtpServerPort = '25';
        $locale = 'en_US';

        $this
            ->setName('spammer')
            ->setDescription('Send random content email')
            ->setDefinition(
                array(
                    new InputOption('server', 's', InputOption::VALUE_OPTIONAL, 'SMTP Server ip to send email to', $smtpServerIp),
                    new InputOption('port', 'p', InputOption::VALUE_OPTIONAL, 'SMTP Server port to send email to', $smtpServerPort),
                    new InputOption('count', 'c', InputOption::VALUE_OPTIONAL, 'Number of email to send', $count),
                    new InputOption('locale', 'l', InputOption::VALUE_OPTIONAL, 'Locale to use', $locale)
                )
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $smtpServerIp = $input->getOption('server');
        if (!filter_var($smtpServerIp, FILTER_VALIDATE_IP)) {
            throw new \InvalidArgumentException('server option is not a valid IP');
        }
        $smtpServerPort = intval($input->getOption('port'));
        if ($smtpServerPort < 0 || $smtpServerPort > 65535) {
            throw new \InvalidArgumentException('server port must be a number between 0 and 65536');
        }

        $count = intval($input->getOption('count'));
        if ($count < 1) {
            throw new \InvalidArgumentException('count must be equal or greater of 1 (you want to send email, right?)');
        }

        $locale = $input->getOption('locale');

        try {
            $style = new OutputFormatterStyle('green', null, array('bold'));
            $output->getFormatter()->setStyle('bold', $style);
            $output->writeln('<comment>Spammer starting up</comment>');
            $output->write('<info>Sending </info>');
            $output->write('<bold>' . $count . '</bold>');
            $output->write('<info> email to server </info>');
            $output->write('<bold>' . $smtpServerIp . '</bold>');
            $output->write('<info>:</info>');
            $output->write('<bold>' . $smtpServerPort . '</bold>');
            $output->writeln('<info> using locale '.$locale.'</info>');

            $faker = Faker\Factory::create($locale);
            $faker->seed(mt_rand());

            $transport = \Swift_SmtpTransport::newInstance($smtpServerIp, $smtpServerPort);
            $mailer = \Swift_Mailer::newInstance($transport);
            $logger = new \Swift_Plugins_Loggers_ArrayLogger();
            $mailer->registerPlugin(new \Swift_Plugins_LoggerPlugin($logger));

            $numSent = 0;
            for ($i = 0; $i < $count; $i++) {
                $output->writeln("Sending email nr. " . ($i + 1));
                $emaiText = $faker->realText(mt_rand(200, 1000));
                $email_subject = implode(' ', $faker->words(mt_rand(3, 7)));
                $message = \Swift_Message::newInstance($email_subject)
                    ->setFrom(array($faker->safeEmail => $faker->name))
                    ->setTo(array($faker->safeEmail => $faker->name))
                    ->setBody($emaiText, 'text/plain')
                    ->addPart('<p>' . $emaiText . '</p>', 'text/html');

                $numSent += $mailer->send($message);
                if (!$numSent) {
                    //email not sent
                    $email_log[$i]['raw'] = $logger->dump();
                    $logger->clear();
                }
            }

            $output->writeln("Sent " . $numSent . " messages");
            return 0;
        } catch (\Exception $e) {
            $output->writeLn('<error>' . $e->getMessage() . '</error>');
            return 1;
        }
    }
}
