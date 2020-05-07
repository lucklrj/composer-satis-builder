<?php

namespace AOE\Composer\Satis\Generator\Command;

use AOE\Composer\Satis\Generator\Builder\SatisBuilder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class BuilderCommand extends Command
{
    /**
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('build')
            ->setDescription('Generate satis.json file from composer.json')
            ->addArgument(
                'satis',
                InputArgument::REQUIRED,
                'Path to satis.json file'
            )
            ->addArgument(
                'composer',
                InputArgument::OPTIONAL,
                'Path to composer.json file'
            )
            ->addOption(
                'require-dev-dependencies',
                'rdd',
                InputOption::VALUE_REQUIRED,
                'sets "require-dev-dependencies"'
            )
            ->addOption(
                'require-dependencies',
                'rd',
                InputOption::VALUE_REQUIRED,
                'sets "require-dependencies"'
            )
            ->addOption(
                'add-requirements',
                'rc',
                InputOption::VALUE_NONE,
                'sets "require-dependencies"'
            )
            ->addOption(
                'merge-requirements',
                'mc',
                InputOption::VALUE_NONE,
                'sets "require-dependencies"'
            )
            ->addOption(
                'merge-repositories',
                'mr',
                InputOption::VALUE_REQUIRED,
                'merge "repositories"'
            )
            ->addOption(
                'add-dev-requirements',
                'drc',
                InputOption::VALUE_NONE,
                'sets "require-dependencies"'
            )
            ->addOption(
                'reset-requirements',
                'rr',
                InputOption::VALUE_NONE,
                'sets "require-dependencies"'
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $satisFile = $input->getArgument('satis');
        if (false === file_exists($satisFile)) {
            throw new \InvalidArgumentException(sprintf('required file does not exists: "%s"', $satisFile), 1446115325);
        }
        $composerFile = $input->getArgument('composer');
        $composer = new \stdClass();
        if (true === file_exists($composerFile)) {
            if (is_file($composerFile) == false) {
                throw new \InvalidArgumentException(sprintf('required file does not exists: "%s"', $composerFile),
                    1446115336);
            } else {
                $composer = json_decode(file_get_contents($composerFile));
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \RuntimeException(
                        sprintf(
                            'An error has occurred while decoding "%s". Error code: %s. Error message: "%s".',
                            $composerFile,
                            json_last_error(),
                            json_last_error_msg()
                        ),
                        1447257260
                    );
                }
            }

        }

        $satis = json_decode(file_get_contents($satisFile));
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException(
                sprintf(
                    'An error has occurred while decoding "%s". Error code: %s. Error message: "%s".',
                    $satisFile,
                    json_last_error(),
                    json_last_error_msg()
                ),
                1447257223
            );
        }


        $builder = new SatisBuilder($composer, $satis);

        if ($input->getOption('reset-requirements')) {
            $builder->resetSatisRequires();
        }

        if ($input->getOption('require-dependencies')) {
            $builder->setRequireDependencies($input->getOption('require-dependencies'));
        }

        if ($input->getOption('require-dev-dependencies')) {
            $builder->setRequireDevDependencies($input->getOption('require-dependencies'));
        }

        if ($input->getOption('add-requirements')) {
            $builder->addRequiresFromComposer();
        }

        if ($input->getOption('merge-requirements')) {
            $builder->mergeRequiresFromComposer();
        }
        $new_repositories = $input->getOption('merge-repositories');
        if ($new_repositories) {
            list($type, $url) = explode(":", $new_repositories);
            $support_types = ["git"];
            if (in_array($type, $support_types) == false) {
                throw new \InvalidArgumentException(sprintf('repositoriy"s type is not error.supports: "%s"',
                    join(",", $support_types)), 0);
            }
            if ($url == "") {
                throw new \InvalidArgumentException("repositoriy\"s url is required ", 0);
            }
            //todo 检查$url格式
            $builder->mergeRepositoriesFromComposer($type, $url);
        }

        if ($input->getOption('add-dev-requirements')) {
            $builder->addDevRequiresFromComposer();
        }

        file_put_contents($satisFile, json_encode($builder->build(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
