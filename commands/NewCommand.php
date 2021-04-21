<?php

namespace Apiato\Installer\Commands;

use Apiato\Installer\Traits\CommandTrait;
use Apiato\Installer\Traits\FileTrait;
use Apiato\Installer\Traits\GitTrait;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class NewCommand extends Command
{

    use GitTrait;
    use CommandTrait;
    use FileTrait;

    protected static $defaultName = 'new';

    protected function configure()
    {
        $this
            ->setDescription('Create a new Apiato application')
            ->addArgument('name', InputArgument::REQUIRED)
            ->addOption('dev', null, InputOption::VALUE_NONE, 'Installs the latest "development" release')
            ->addOption('git', null, InputOption::VALUE_NONE, 'Initialize a Git repository')
            ->addOption('branch', null, InputOption::VALUE_REQUIRED, 'The branch that should be created for a new repository', 'main')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Forces install even if the directory already exists')
            ->setHelp('This command only shows a message to welcome user and nothing more.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $output->writeln([
            '<fg=red>' . PHP_EOL . PHP_EOL . PHP_EOL,
            "     ___      .______    __       ___   .___________.  ______   ",
            "    /   \     |   _  \  |  |     /   \  |           | /  __  \  ",
            "   /  ^  \    |  |_)  | |  |    /  ^  \ `---|  |----`|  |  |  | ",
            "  /  /_\  \   |   ___/  |  |   /  /_\  \    |  |     |  |  |  | ",
            " /  _____  \  |  |      |  |  /  _____  \   |  |     |  `--'  | ",
            "/__/     \__\ | _|      |__| /__/     \__\  |__|      \______/  ",
            '</>' . PHP_EOL . PHP_EOL . PHP_EOL,
        ]);

        sleep(1);

        $name = $input->getArgument('name');

        $directory = $name !== '.' ? getcwd() . '/' . $name : '.';

        $version = $this->getVersion($input);

        if (!$input->getOption('force')) {
            $this->verifyApplicationDoesntExist($directory);
        }

        if ($input->getOption('force') && $directory === '.') {
            throw new RuntimeException('Cannot use --force option when using current directory for installation!');
        }

        $composer = $this->findComposer();

        $commands = [
            $composer . " create-project apiato/apiato \"$directory\" $version --remove-vcs --prefer-dist",
        ];

        if ($directory != '.' && $input->getOption('force')) {
            if (PHP_OS_FAMILY == 'Windows') {
                array_unshift($commands, "rd /s /q \"$directory\"");
            } else {
                array_unshift($commands, "rm -rf \"$directory\"");
            }
        }

        if (PHP_OS_FAMILY != 'Windows') {
            $commands[] = "chmod 755 \"$directory/artisan\"";
        }

        if (($process = $this->runCommands($commands, $input, $output))->isSuccessful()) {

            //  TODO: Remove github checks if don't want to implement github support
            if ($input->getOption('git')/* || $input->getOption('github') !== false*/) {
                $this->createRepository($directory, $input, $output);
            }

            $output->writeln(PHP_EOL . '<comment>Apiato ready! Build something amazing.</comment>');
        }

        return $process->getExitCode();
    }

}
