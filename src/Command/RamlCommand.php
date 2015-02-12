<?php

namespace Creads\Api2SymfonyBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Question\Question;

abstract class RamlCommand extends ContainerAwareCommand
{
    private $namespace;
    private $destination;


    protected function configure()
    {
        $this
            ->addArgument('bundle_namespace', InputArgument::REQUIRED, 'Namespace of the bundle where controllers will be dumped')
            ->addOption('destination', 'd', InputOption::VALUE_OPTIONAL, 'Force another destination for controllers')
        ;
    }

    /**
     * Returns namespace input
     *
     * @return string
     */
    protected function getNamespace(InputInterface $input)
    {
        //memory cache
        if (!$this->namespace) {
            $this->namespace = $input->getArgument('bundle_namespace');
            //be sure to remove trailing slash or \Controller from namespace
            $this->namespace = preg_replace('/(\\\Controller)?\\\?$/', '', $this->namespace);
        }

        return $this->namespace;
    }

    /**
     * Returns destination input
     *
     * @return string
     */
    protected function getDestination(InputInterface $input, OutputInterface $output)
    {
        if (!$this->destination) {
            $this->destination = $input->hasOption('destination') ? $input->getOption('destination') : null;

            if (!$this->destination) {
                //we try to guess the destination from namespace
                $namespace = $this->getNamespace($input);

                $autoload = $this->getContainer()->getParameter('kernel.root_dir') . '/autoload.php';
                if (file_exists($autoload)) {
                    $loader = require $autoload;
                    $bundles = $this->getContainer()->getParameter('kernel.bundles');
                    foreach ($bundles as $bundleName => $bundleClass) {
                        $reflection =  new \ReflectionClass($bundleClass);

                        if ($namespace === $reflection->getNamespaceName()) {
                            $bundleFile = $loader->findFile($bundleClass);
                            $this->destination = dirname($bundleFile) . '/Controller';
                        }
                    }
                }

                if (!$this->destination) {
                    throw new \RuntimeException(sprintf('Could not guess destination for namespace %s. Please  check it or use --destination to force a destination for generated controllers.', $namespace));
                }
            }

            $dialog = $this->getHelperSet()->get('question');
            $fs = new Filesystem();
            if (!$fs->exists($this->destination)) {
                $output->writeln(sprintf('<error>Destination directory %s does not exist.</error>', $this->destination));
                if (!$dialog->ask(
                    $input,
                    $output,
                    new Question('<question>Would you like to create it ? [y/N] </question>', false)
                )) {
                    exit;
                }

                $fs->mkdir($this->destination);
            }
        }

        return $this->destination;
    }
}
