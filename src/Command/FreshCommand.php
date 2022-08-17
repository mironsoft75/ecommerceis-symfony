<?php

namespace App\Command;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;

class FreshCommand extends Command
{
    protected static $defaultName = 'doctrine:migrations:fresh';
    protected static $defaultDescription = '';

    protected function configure(): void
    {
        $this
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    private $kernel;

    public function __construct(string $name = null, KernelInterface $kernel)
    {
        $this->kernel = $kernel;
        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $application = new Application($this->kernel);
        $application->setAutoExit(false);

        $io->info('doctrine:migrations:migrate first running...');
        $input = new ArrayInput([
            'command' => 'doctrine:migrations:migrate',
            'version' => 'first',
            '--no-interaction' => ''
        ]);

        $output = new BufferedOutput();
        $application->run($input, $output);
        $content = $output->fetch();
        $io->success($content);

        $io->info('doctrine:migrations:migrate running...');

        $input = new ArrayInput([
            'command' => 'doctrine:migrations:migrate',
            '--no-interaction' => ''
        ]);

        $application->run($input, $output);
        $content = $output->fetch();
        $io->success($content);

        return Command::SUCCESS;
    }
}
