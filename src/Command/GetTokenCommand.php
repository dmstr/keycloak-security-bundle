<?php
// file generated with AI assistance: Claude Code - 2026-05-16 22:00:00 UTC

declare(strict_types=1);

namespace Dmstr\KeycloakSecurity\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;

#[AsCommand(
    name: 'za:auth:token',
    description: 'Fetch a JWT access token from Keycloak (password grant) for development/testing'
)]
class GetTokenCommand extends Command
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'username',
                'u',
                InputOption::VALUE_REQUIRED,
                'Keycloak username (e.g. za7-admin, za7-user). Prompted if omitted.'
            )
            ->addOption(
                'password',
                'p',
                InputOption::VALUE_REQUIRED,
                'Keycloak password. Prompted hidden if omitted.'
            )
            ->addOption(
                'realm-url',
                null,
                InputOption::VALUE_REQUIRED,
                'Keycloak realm URL (without trailing slash). Defaults to internal docker host.',
                'http://keycloak:8080/realms/herzog'
            )
            ->addOption(
                'client-id',
                'c',
                InputOption::VALUE_REQUIRED,
                'Keycloak client ID (public client, directAccessGrantsEnabled=true).',
                'za7'
            )
            ->addOption(
                'format',
                'f',
                InputOption::VALUE_REQUIRED,
                'Output format: token (raw access_token), json (full response), curl (paste-ready -H header), env (export ACCESS_TOKEN=...)',
                'token'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $username = $input->getOption('username');
        if ($username === null) {
            $question = new Question('Username: ');
            $username = $io->askQuestion($question);
        }
        if ($username === null || $username === '') {
            $io->error('Username is required.');
            return Command::FAILURE;
        }

        $password = $input->getOption('password');
        if ($password === null) {
            $question = new Question('Password: ');
            $question->setHidden(true);
            $question->setHiddenFallback(false);
            $password = $io->askQuestion($question);
        }
        if ($password === null || $password === '') {
            $io->error('Password is required.');
            return Command::FAILURE;
        }

        $realmUrl = rtrim((string) $input->getOption('realm-url'), '/');
        $clientId = (string) $input->getOption('client-id');
        $tokenUrl = $realmUrl . '/protocol/openid-connect/token';

        $httpClient = HttpClient::create();

        try {
            $response = $httpClient->request('POST', $tokenUrl, [
                'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
                'body' => [
                    'grant_type' => 'password',
                    'client_id' => $clientId,
                    'username' => $username,
                    'password' => $password,
                ],
            ]);

            $statusCode = $response->getStatusCode();
            $data = $response->toArray(throw: false);
        } catch (ExceptionInterface $e) {
            $io->error(sprintf('Token request failed: %s', $e->getMessage()));
            $io->note(sprintf('Endpoint: %s', $tokenUrl));
            return Command::FAILURE;
        }

        if ($statusCode !== 200 || !isset($data['access_token'])) {
            $io->error(sprintf(
                'Keycloak returned %d: %s',
                $statusCode,
                $data['error_description'] ?? $data['error'] ?? 'unknown error'
            ));
            $io->note(sprintf('Endpoint: %s, client_id: %s', $tokenUrl, $clientId));
            return Command::FAILURE;
        }

        $format = $input->getOption('format');
        $accessToken = $data['access_token'];

        switch ($format) {
            case 'json':
                $output->writeln(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                break;
            case 'curl':
                $output->writeln(sprintf('-H "Authorization: Bearer %s"', $accessToken));
                break;
            case 'env':
                $output->writeln(sprintf('export ACCESS_TOKEN=%s', $accessToken));
                break;
            case 'token':
            default:
                $output->writeln($accessToken);
                break;
        }

        return Command::SUCCESS;
    }
}
