<?php

namespace Apiato\Installer\Traits;

use Symfony\Component\Console\Input\InputInterface;

trait GitTrait
{

    /**
     * Get the version that should be downloaded.
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @return string
     */
    protected function getVersion(InputInterface $input)
    {
        if ($input->getOption('dev')) {
            return 'dev-master';
        }

        return '';
    }

}
