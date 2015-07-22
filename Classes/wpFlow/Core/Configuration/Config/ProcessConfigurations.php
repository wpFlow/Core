<?php
/**
 * Created by PhpStorm.
 * User: Dennis
 * Date: 05.07.15
 * Time: 20:40
 */

namespace wpFlow\Configuration\Config;


use Symfony\Component\Config\Definition\Processor;

class ProcessConfigurations {

    /**
     * An instance of Symfony Processor Class which is found in the config component
     * @var Processor
     */
    protected $processor;

    /**
     * An instance of the own written Configuration Class which is the validation of the
     * Yaml content. The Class implements Symfonys ConfigurationInterface
     * @var
     */
    protected $configuration;



    /**
     * The Constructor
     * @param array $configFileContent
     * @param $configurationClass
     */
    public function __construct($configuration){
        $this->processor = new Processor();
        $this->configuration = $configuration;
    }

    /**
     * Process the content and validate it before
     * @param array $configFileContent
     * @return array
     */
    public function process(array $configFileContent){
        $processor =  $this->processor;
        $configuration = $this->configuration;

        $processedConfiguration = $processor->processConfiguration(
            $configuration,
            $configFileContent
        );

        return $processedConfiguration;
    }
}