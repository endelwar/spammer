<?php

namespace EndelWar\Spammer\Command;

use EndelWar\Spammer\Validator;
use Faker;
use Swift_Mailer;
use Swift_Message;
use Swift_SmtpTransport;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SpammerCommand extends Command
{
    /**
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    protected function configure()
    {
        $count = 10;
        $smtpServerIp = '127.0.0.1';
        $smtpServerPort = '25';
        $locale = 'en_US';

        $this
            ->setName('spammer')
            ->setDescription('Send random content email or email from a corpus')
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
     * @throws \Exception
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $validator = new Validator($input);
        $validInput = $validator->validateInput();

        $style = new OutputFormatterStyle('green', null, ['bold']);
        $output->getFormatter()->setStyle('bold', $style);
        $output->writeln('<comment>Spammer starting up</comment>');

        return $this->sendFakeEmail($output, $validInput);
    }

    /**
     * @param OutputInterface $output
     * @param array $validInput
     * @throws \Exception
     * @return int
     */
    private function sendFakeEmail(OutputInterface $output, array $validInput): int
    {
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

        $transport = new Swift_SmtpTransport($validInput['smtpServerIp'], $validInput['smtpServerPort']);
        $mailer = new Swift_Mailer($transport);

        $numSent = 0;
        for ($i = 0; $i < $validInput['count']; $i++) {
            $emaiText = $faker->realText(random_int(200, 1000));
            $emailSubject = implode(' ', $faker->words(random_int(3, 7)));
            $message = new Swift_Message($emailSubject);

            $from = $this->getFromTo($faker, $validInput['from']);
            $message->setFrom($from);

            $to = $this->getFromTo($faker, $validInput['to']);
            $message->setTo($to);

            $message->setBody($emaiText, 'text/plain');
            $message->addPart('<p>' . $emaiText . '</p>', 'text/html');

            $output->writeln('Sending email nr. ' . ($i + 1) . ': ' . key($from) . ' => ' . key($to));
            try {
                $numSent += $mailer->send($message);
            } catch (\Exception $swe) {
                $output->writeln('<error>' . $swe->getMessage() . '</error>');

                return 1;
            }
        }

        $output->writeln('Sent ' . $numSent . ' messages');

        unset($faker);

        return 0;
    }

    /**
     * @param Faker\Generator $faker
     * @param string $validInputFromTo
     * @return array
     */
    private function getFromTo(Faker\Generator $faker, $validInputFromTo): array
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
}
