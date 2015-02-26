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
            ->addArgument('namespace', InputArgument::REQUIRED, 'Namespace in a bundle where controllers should be dumped')
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
            $this->namespace = $input->getArgument('namespace');
            //be sure to remove trailing slash
            $this->namespace = preg_replace('/\\\?$/', '', $this->namespace);
        }

        return $this->namespace;
    }

    /**
     * Returns destination directory
     *
     * If destination is not provided, guess it from namespace.
     * If directory does not exist, ask to create it.
     *
     * Can be called several time, so we put the result in memory cache ($this->destination)
     *
     * @return string
     */
    protected function getDestination(InputInterface $input, OutputInterface $output)
    {
        if (!$this->destination) {
            $this->destination = $input->hasOption('destination') ? $input->getOption('destination') : null;

            if (!$this->destination) {
                //we try to guess the destination from namespace
                $namespace = preg_replace('/(\\\Controller)?$/', '', $this->getNamespace($input));

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
                    throw new \RuntimeException(sprintf('Could not guess destination for namespace %s. Please check it or use --destination to force a destination path.', $namespace));
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

    protected function store(InputInterface $input, OutputInterface $output, array $controllers)
    {
        $dialog = $this->getHelperSet()->get('question');

        $destination = $this->getDestination($input, $output);

        foreach ($controllers as $controller) {
            if ($this->getContainer()->get('api2symfony.dumper')->exists($controller, $destination)) {
                if ($input->isInteractive()) {
                    $output->writeln(sprintf('* <comment>%s</comment>: <error>EXISTS</error>', $controller->getClassName()));
                }

                $answer = $dialog->ask(
                    $input,
                    $output,
                    new Question(sprintf('<question>Overwrite this file (previous file will be renamed with extension .old) ?</question> [Y]/n ', $controller->getClassName()), false)
                );
                if ($answer === 'n' || $answer === 'N') {
                    continue;
                }
                $output->writeln(sprintf('* <comment>%s</comment>: <info>OVERWRITTEN</info>', $controller->getClassName()));
            } else {
                $output->writeln(sprintf('* <comment>%s</comment>: <info>CREATED</info>', $controller->getClassName()));
            }

            $file = $this->getContainer()->get('api2symfony.dumper')->dump($controller, $destination);
        }
    }
}
