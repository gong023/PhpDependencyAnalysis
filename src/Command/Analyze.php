<?php
/**
 * The MIT License (MIT)
 *
 * Copyright (c) 2014 Marco Muths
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace PhpDA\Command;

use PhpDA\Command\MessageInterface as Message;
use PhpDA\Command\Strategy\StrategyInterface;
use PhpDA\Plugin\LoaderInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Parser;

/**
 * @SuppressWarnings("PMD.CouplingBetweenObjects")
 */
class Analyze extends Command
{
    /** @var string */
    private $configFilePath;

    /** @var Parser */
    private $configParser;

    /** @var LoaderInterface */
    private $strategyLoader;

    /**
     * @param Parser $parser
     */
    public function setConfigParser(Parser $parser)
    {
        $this->configParser = $parser;
    }

    /**
     * @param LoaderInterface $loader
     */
    public function setStrategyLoader(LoaderInterface $loader)
    {
        $this->strategyLoader = $loader;
    }

    protected function configure()
    {
        $this->addArgument('config', InputArgument::OPTIONAL, Message::ARGUMENT_CONFIG, './phpda.yml');
        $this->addOption('source', 's', InputOption::VALUE_OPTIONAL, Message::OPTION_SOURCE);
        $this->addOption('filePattern', 'p', InputOption::VALUE_OPTIONAL, Message::OPTION_FILE_PATTERN);
        $this->addOption('ignore', 'i', InputOption::VALUE_OPTIONAL, Message::OPTION_IGNORE);
        $this->addOption('formatter', 'f', InputOption::VALUE_OPTIONAL, Message::OPTION_FORMATTER);
        $this->addOption('target', 't', InputOption::VALUE_OPTIONAL, Message::OPTION_TARGET);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config = $this->createConfigBy($input);

        $output->writeln($this->getDescription() . PHP_EOL);
        $output->writeln(Message::READ_CONFIG_FROM . $this->configFilePath . PHP_EOL);

        $this->loadStrategy('Overall', array('config' => $config, 'output' => $output))->execute();
    }

    /**
     * @param InputInterface $input
     * @throws \InvalidArgumentException
     * @return Config
     */
    private function createConfigBy(InputInterface $input)
    {
        $this->configFilePath = realpath($input->getArgument('config'));
        $options = $input->getOptions();

        $config = $this->configParser->parse(file_get_contents($this->configFilePath));

        if (!is_array($config)) {
            throw new \InvalidArgumentException('Configuration is invalid');
        }

        if (isset($options['ignore'])) {
            $options['ignore'] = explode(',', $options['ignore']);
        }

        return new Config(array_merge($config, array_filter($options)));
    }

    /**
     * @param string $type
     * @param array  $options
     * @throws \RuntimeException
     * @return StrategyInterface
     */
    private function loadStrategy($type, array $options = null)
    {
        $fqn = 'PhpDA\\Command\\Strategy\\' . ucfirst($type);
        $strategy = $this->strategyLoader->get($fqn, $options);

        if (!$strategy instanceof StrategyInterface) {
            throw new \RuntimeException(
                sprintf('Strategy \'%s\' must implement PhpDA\\Command\\Strategy\\StrategyInterface', $fqn)
            );
        }

        return $strategy;
    }
}
