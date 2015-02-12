<?php

namespace Creads\Api2SymfonyBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Question\Question;

class UpdateRamlCommand extends RamlCommand
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('api2symfony:raml:update')
            ->setDescription('Update Symfony controllers after comparing to RAML specifications')
            ->addArgument('new_raml_file', InputArgument::REQUIRED, 'New RAML specification file')
            ->addArgument('old_raml_file', InputArgument::REQUIRED, 'Old RAML specification file')
            ->addOption('backwards-compatible', 'bc', InputOption::VALUE_NONE, 'Will create new controller in a sub-namespace to maintain the old API working')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command will update Symfony controllers according to differences between two RAML specifications.

  <info>php %command.full_name% "Acme\\DemoBundle\\Controller" path/to/new_file.raml path/to/old_file.raml [--destination=force/another/destination/path]</info>
EOT
            );
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $newFile = $input->getArgument('new_raml_file');
        $oldFile = $input->getArgument('old_raml_file');

        $destination = $this->getDestination($input, $output);

        $namespace = $this->getNamespace($input);

        $dialog = $this->getHelperSet()->get('question');

        $controllers = $this->getContainer()->get('api2symfony.converter.raml')->update($newFile, $oldFile, $namespace);

        $this->store($input, $output, $controllers);
    }
}
