<?php

namespace Apiato\Installer\Traits;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

trait CommandTrait
{

    /**
     * Get the composer command for the environment.
     *
     * @return string
     */
    protected function findComposer()
    {
        $composerPath = getcwd() . '/composer.phar';

        if (file_exists($composerPath)) {
            return '"' . PHP_BINARY . '" ' . $composerPath;
        }

        return 'composer';
    }

    /**
     * Run the given commands.
     *
     * @param array $commands
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param array $env
     * @return \Symfony\Component\Process\Process
     */
    protected function runCommands($commands, InputInterface $input, OutputInterface $output, array $env = [])
    {
        if ($input->getOption('no-ansi')) {
            $commands = array_map(function ($value) {
                if (substr($value, 0, 5) === 'chmod') {
                    return $value;
                }

                return $value . ' --no-ansi';
            }, $commands);
        }

        if ($input->getOption('quiet')) {
            $commands = array_map(function ($value) {
                if (substr($value, 0, 5) === 'chmod') {
                    return $value;
                }

                return $value . ' --quiet';
            }, $commands);
        }

        $process = Process::fromShellCommandline(implode(' && ', $commands), null, $env, null, null);

        if ('\\' !== DIRECTORY_SEPARATOR && file_exists('/dev/tty') && is_readable('/dev/tty')) {
            try {
                $process->setTty(true);
            } catch (RuntimeException $e) {
                $output->writeln('Warning: ' . $e->getMessage());
            }
        }

        $process->run(function ($type, $line) use ($output) {
            $output->write('    ' . $line);
        });

        return $process;
    }

    /**
     * Create a Git repository and commit the base Apiato skeleton.
     *
     * @param string $directory
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return void
     */
    protected function createRepository(string $directory, InputInterface $input, OutputInterface $output)
    {
        chdir($directory);

        $branch = $input->getOption('branch') ?: 'main';

        $commands = [
            "git init -q -b {$branch} .",
            'git add .',
            'git commit -q -m "Set up a fresh Apiato app"',
        ];

        $this->runCommands($commands, $input, $output);
    }

}
