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
                    new InputOption('locale', 'l', InputOption::VALUE_OPTIONAL, 'Locale to use', $locale),
                )
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return integer
     * @throws \InvalidArgumentException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $validInput = $this->validateInput($input);

        $style = new OutputFormatterStyle('green', null, array('bold'));
        $output->getFormatter()->setStyle('bold', $style);
        $output->writeln('<comment>Spammer starting up</comment>');
        $output->write(
            '<info>Sending </info><bold>' . $validInput['count'] .
            '</bold><info> email to server </info><bold>' . $validInput['smtpServerIp'] .
            '</bold><info>:</info><bold>' . $validInput['smtpServerPort'] . '</bold>'
        );
        $output->writeln('<info> using locale </info><bold>' . $validInput['locale'] . '</bold>');

        $faker = Faker\Factory::create($validInput['locale']);
        $faker->seed(mt_rand());

        $transport = \Swift_SmtpTransport::newInstance()->setHost($validInput['smtpServerIp'])->setPort(
            $validInput['smtpServerPort']
        );
        $mailer = \Swift_Mailer::newInstance($transport);

        $numSent = 0;
        for ($i = 0; $i < $validInput['count']; $i++) {
            $output->writeln('Sending email nr. ' . ($i + 1));
            $emaiText = $faker->realText(mt_rand(200, 1000));
            $email_subject = implode(' ', $faker->words(mt_rand(3, 7)));
            $message = \Swift_Message::newInstance($email_subject)
                ->setFrom(array($faker->safeEmail => $faker->name))
                ->setTo(array($faker->safeEmail => $faker->name))
                ->setBody($emaiText, 'text/plain')
                ->addPart('<p>' . $emaiText . '</p>', 'text/html');

            try {
                $numSent += $mailer->send($message);
            } catch (\Swift_TransportException $swe) {
                $output->writeln('<error>' . $swe->getMessage() . '</error>');

                return 1;
            }
        }

        $output->writeln('Sent ' . $numSent . ' messages');

        return 0;
    }

    /**
     * @param InputInterface $input
     * @throws \InvalidArgumentException
     * @return array
     */
    protected function validateInput(InputInterface $input)
    {
        $validInput = array();
        $validInput['smtpServerIp'] = $input->getOption('server');
        $this->validateInputServerIP($validInput['smtpServerIp']);

        $validInput['smtpServerPort'] = $input->getOption('port');
        $this->validateInputServerPort($validInput['smtpServerPort']);

        $validInput['count'] = (int)$input->getOption('count');
        $this->validateInputCount($validInput['count']);

        $validInput['locale'] = $input->getOption('locale');

        return $validInput;
    }

    private function validateInputServerIP($ip)
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            throw new \InvalidArgumentException('server option is not a valid IP');
        }
    }

    private function validateInputServerPort($port)
    {
        if (!is_numeric($port) || ($port < 0 || $port > 65535)) {
            throw new \InvalidArgumentException('server port must be a number between 0 and 65536');
        }
    }

    private function validateInputCount($count)
    {
        if ($count < 1) {
            throw new \InvalidArgumentException('count must be equal or greater than 1 (you want to send email, right?)');
        }
    }
}
