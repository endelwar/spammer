<?php

namespace EndelWar\Spammer;

use Symfony\Component\Console\Input\InputInterface;

class Validator
{
    /** @var InputInterface $input */
    private $input;

    public function __construct(InputInterface $input)
    {
        $this->input = $input;
    }

    /**
     * @throws \InvalidArgumentException
     * @return array
     */
    public function validateInput(): array
    {
        $validInput = [];
        $validInput['smtpServerIp'] = $this->input->getOption('server');
        $this->validateInputServerIP($validInput['smtpServerIp']);

        $validInput['smtpServerPort'] = $this->input->getOption('port');
        $this->validateInputServerPort($validInput['smtpServerPort']);

        $validInput['corpusPath'] = $this->validateInputCorpusPath($this->input->getOption('set-corpus-path'));

        $validInput['count'] = false;
        $validInput['locale'] = false;
        if (false === $validInput['corpusPath']) {
            $validInput['count'] = (int)$this->input->getOption('count');
            $this->validateInputCount($validInput['count']);

            $validInput['locale'] = $this->input->getOption('locale');
        }

        $validInput['to'] = $this->validateInputToFrom($this->input->getOption('to'));

        $validInput['from'] = $this->validateInputToFrom($this->input->getOption('from'));

        if (false !== $validInput['corpusPath'] && (false !== $validInput['count'] || false !== $validInput['locale'])) {
            throw new \InvalidArgumentException('Cannot set both corpus path and count or locale');
        }

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
    private function validateInputToFrom($string): string
    {
        if (null === $string) {
            return '';
        }

        $string = strtolower($string);
        if (strpos($string, '@') !== false) {
            if (filter_var($string, FILTER_VALIDATE_EMAIL)) {
                return $string;
            }
        } elseif ($this->isValidDomain($string)) {
            return $string;
        }

        throw new \InvalidArgumentException('To and from must be a valid email address or a FQDN');
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

    /**
     * @param string $path
     * @throws \InvalidArgumentException
     * @return bool|string
     */
    private function validateInputCorpusPath($path)
    {
        if (null === $path) {
            return false;
        }

        $path = realpath($path);
        if (!is_dir($path)) {
            throw new \InvalidArgumentException('Set a valid directory as corpus path');
        }

        return $path;
    }
}
