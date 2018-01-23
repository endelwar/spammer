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
                [
                    new InputOption('server', 's', InputOption::VALUE_OPTIONAL, 'SMTP Server ip to send email to', $smtpServerIp),
                    new InputOption('port', 'p', InputOption::VALUE_OPTIONAL, 'SMTP Server port to send email to', $smtpServerPort),
                    new InputOption('count', 'c', InputOption::VALUE_OPTIONAL, 'Number of email to send', $count),
                    new InputOption('locale', 'l', InputOption::VALUE_OPTIONAL, 'Locale to use', $locale),
                    new InputOption('to', 't', InputOption::VALUE_OPTIONAL, 'To address or domain'),
                    new InputOption('from', 'f', InputOption::VALUE_OPTIONAL, 'From address or domain'),
                    new InputOption('set-corpus-path', null, InputOption::VALUE_OPTIONAL, 'Directory containing email corpus'),
                ]
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws \InvalidArgumentException
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $validInput = $this->validateInput($input);

        $style = new OutputFormatterStyle('green', null, ['bold']);
        $output->getFormatter()->setStyle('bold', $style);
        $output->writeln('<comment>Spammer starting up</comment>');
        $message = '<info>Sending </info><bold>' . $validInput['count'] . '</bold>' .
            '<info> email to server </info><bold>' . $validInput['smtpServerIp'] . '</bold>' .
            '<info>:</info><bold>' . $validInput['smtpServerPort'] . '</bold>';
        if ($validInput['from'] !== '') {
            $message .= '<info> from </info><bold>' . $validInput['from'] . '</bold>';
        }
        if ($validInput['to'] !== '') {
            $message .= '<info> to </info><bold>' . $validInput['to'] . '</bold>';
        }
        $output->write($message);
        $output->writeln('<info> using locale </info><bold>' . $validInput['locale'] . '</bold>');

        $faker = Faker\Factory::create($validInput['locale']);
        $faker->seed(mt_rand());

        $transport = \Swift_SmtpTransport::newInstance()->setHost($validInput['smtpServerIp'])->setPort(
            $validInput['smtpServerPort']
        );
        $mailer = \Swift_Mailer::newInstance($transport);

        $numSent = 0;
        for ($i = 0; $i < $validInput['count']; $i++) {
            $emaiText = $faker->realText(mt_rand(200, 1000));
            $emailSubject = implode(' ', $faker->words(mt_rand(3, 7)));
            $message = \Swift_Message::newInstance($emailSubject);

            $from = $this->getFromTo($faker, $validInput['from']);
            $message->setFrom($from);

            $to = $this->getFromTo($faker, $validInput['to']);
            $message->setTo($to);

            $message->setBody($emaiText, 'text/plain');
            $message->addPart('<p>' . $emaiText . '</p>', 'text/html');

            $output->writeln('Sending email nr. ' . ($i + 1) . ': ' . key($from) . ' => ' . key($to));
            try {
                $numSent += $mailer->send($message);
            } catch (\Swift_TransportException $swe) {
                $output->writeln('<error>' . $swe->getMessage() . '</error>');

                return 1;
            }
        }

        $output->writeln('Sent ' . $numSent . ' messages');

        unset($faker);

        return 0;
    }

    /**
     * @param $faker
     * @param $validInputFromTo
     * @return array
     */
    private function getFromTo($faker, $validInputFromTo)
    {
        // generate fake address and name if null
        if ($validInputFromTo === '') {
            return [$faker->safeEmail => $faker->name];
        }

        // use user submitted email if is email
        if (filter_var($validInputFromTo, FILTER_VALIDATE_EMAIL)) {
            return [$validInputFromTo => $faker->name];
        }

        // get a random username and attach it to domain supplied by user
        $user = strstr($faker->safeEmail, '@', true);

        return [$user . '@' . $validInputFromTo => $faker->name];
    }

    /**
     * @param InputInterface $input
     * @throws \InvalidArgumentException
     * @return array
     */
    protected function validateInput(InputInterface $input)
    {
        $validInput = [];
        $validInput['smtpServerIp'] = $input->getOption('server');
        $this->validateInputServerIP($validInput['smtpServerIp']);

        $validInput['smtpServerPort'] = $input->getOption('port');
        $this->validateInputServerPort($validInput['smtpServerPort']);

        $validInput['count'] = (int)$input->getOption('count');
        $this->validateInputCount($validInput['count']);

        $validInput['locale'] = $input->getOption('locale');

        $validInput['to'] = $this->validateInputToFrom($input->getOption('to'));

        $validInput['from'] = $this->validateInputToFrom($input->getOption('from'));

        return $validInput;
    }

    /**
     * @param string $ip
     * @throws \InvalidArgumentException
     */
    private function validateInputServerIP($ip)
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            throw new \InvalidArgumentException('server option is not a valid IP');
        }
    }

    /**
     * @param int $port
     * @throws \InvalidArgumentException
     */
    private function validateInputServerPort($port)
    {
        if (!is_numeric($port) || ($port < 0 || $port > 65535)) {
            throw new \InvalidArgumentException('server port must be a number between 0 and 65536');
        }
    }

    /**
     * @param int $count
     * @throws \InvalidArgumentException
     */
    private function validateInputCount($count)
    {
        if ($count < 1) {
            throw new \InvalidArgumentException('count must be equal or greater than 1 (you want to send email, right?)');
        }
    }

    /**
     * @param $string
     * @throws \InvalidArgumentException
     * @return string
     */
    private function validateInputToFrom($string)
    {
        if (null === $string) {
            return '';
        }

        $string = strtolower($string);
        if (strpos($string, '@') !== false) {
            if (filter_var($string, FILTER_VALIDATE_EMAIL)) {
                return $string;
            }
        } else {
            if ($this->isValidDomain($string)) {
                return $string;
            }
        }

        throw new \InvalidArgumentException('to and from must be a valid email address or a FQDN');
    }

    /**
     * @param $domain
     * @return bool|mixed
     */
    private function isValidDomain($domain)
    {
        $domain = strtolower($domain);
        $regex = "/^((?=[a-z0-9-]{1,63}\.)(xn--)?[a-z0-9]+(-[a-z0-9]+)*\.)+[a-z]{2,63}$/";

        return preg_match($regex, $domain);
    }
}
