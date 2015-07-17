<?php
/**
 * Created by PhpStorm.
 * User: Dennis
 * Date: 02.07.15
 * Time: 21:59
 */

namespace wpFlow\Core\Resource;


use wpFlow\Core\Bootstrap;
use wpFlow\Core\Exception;
use wpFlow\Core\Utilities\Debug;
use wpFlow\Core\Utilities\Files;

class ResourceManager {

    const PUBLICFOLDER = 'Public';
    const JAVASCRIPT_RESOURCES = 'js';
    const STYLESHEETS = 'css';
    const SASSSTYLESHEETS = 'scss';
    const IMAGES = 'img';

    protected $bootstrap;
    protected $activePackages;
    protected $configManagementEnabledPackages;
    protected $registeredResources;
    protected $resourceEntities = array();

    public function initialize($activePackages, Bootstrap $bootstrap){
        $this->bootstrap = $bootstrap;
        $this->activePackages = $activePackages;

        // filter active packages by ConfigManagement flag
        foreach ($this->activePackages as $activePackage){
            if($activePackage->isConfigManagementEnabled()){
                $configManagementEnabledPackages[$activePackage->getPackageKey()] = $activePackage;
            }
        }
        $this->configManagementEnabledPackages = $configManagementEnabledPackages;

        $packages = $this->configManagementEnabledPackages;

        $resourceFactory = new ResourceFactory();
        foreach($packages as $package){
            $this->registeredResources[$package->getPackageKey()] = $package->getResources();
            $path = $package->getResourcesPath() . self::PUBLICFOLDER;

            $registeredResources = $this->registeredResources[$package->getPackageKey()]['Public'];

            if(!$registeredResources == NULL) {
                $this->resolveResourceType($registeredResources, $path, $package->getPackageKey());
                $this->resolvePathByType($path,$this->registeredResources[$package->getPackageKey()]['Public'] ,$package->getPackageKey());

                $this->loadResourceContent($this->registeredResources[$package->getPackageKey()]['Public'], $package->getPackageKey());

                $handle = $this->registeredResources[$package->getPackageKey()]['Public'];

                foreach ($handle as $handleName => $values) {
                    $type = $values['Type'];
                    $fileName = $values['Filename'];
                    $ranking = $values['Ranking'];
                    $position = $values['Position'];
                    $minify = $values['Minify'];
                    $resourcePath = $values['Path'];
                    $content = $values['Content'];
                    $compile = $values['Compile'];
                    $expression = $values['Options']['Expression'];

                    $this->resourceEntities[$handleName] = $resourceFactory->create($handleName, $type, $fileName, $ranking, $position, $minify, $resourcePath, $content, $compile, $expression);
                }
            }
        }

        // embed the main compiled javascript file with the HEADER position
        $headerContent = $this->buildCompiledJS('Header');
        $mainHeaderJS = $resourceFactory->create($handle = 'mainHeaderJS', $type = 'local', $fileName = 'mainHeader.js', $ranking = 1, $position = 'header', $minify = false, $resourcePath = '', $headerContent, $compile = false ,$expression = '');

        $this->registerResources($mainHeaderJS);
        $this->enqueueScriptResources($mainHeaderJS->getHandle(), $expression = !is_admin());

        // embed the main compiled javascript file with the FOOTER position
        $footerContent = $this->buildCompiledJS('Footer');
        $mainFooterJS = $resourceFactory->create($handle = 'mainFooterJS', $type = 'local', $fileName = 'mainFooter.js', $ranking = 1, $position = 'footer', $minify = false, $resourcePath = '', $footerContent, $compile = false ,$expression = '');

        $this->registerResources($mainFooterJS);
        $this->enqueueScriptResources($mainFooterJS->getHandle(), $expression = !is_admin());

        // embed the main compiled CSS file
        $cssContent = $this->buildCompiledCSS();
        $mainCSS = $resourceFactory->create($handle = 'mainCSS', $type = 'local', $fileName = 'main.css', $ranking = 1, $position = 'header', $minify = false, $resourcePath = '', $cssContent, $compile = false ,$expression = '');

        $this->registerResources($mainCSS);
        $this->enqueueStyleResources($mainCSS->getHandle(), $expression = !is_admin());

        $sortedJS = $this->sortAndFilterResourceEntitiesByRanking('js');

        foreach($sortedJS as $jsResource){
            if($jsResource->isNotCompileEnabled()){
                $this->registerResources($jsResource);
                $this->enqueueScriptResources($jsResource->getHandle(), $jsResource->getExpression());
            }
        }

        $sortedCSS = $this->sortAndFilterResourceEntitiesByRanking('css');

        foreach($sortedCSS as $cssResource){
            if($cssResource->isNotCompileEnabled()){
                $this->registerResources($cssResource);
                $this->enqueueStyleResources($cssResource->getHandle(), $cssResource->getExpression());
            }
        }
    }

    protected function sortAndFilterResourceEntitiesByRanking($filter){

        switch($filter){

            case 'js':
                foreach($this->resourceEntities as $handle => $resource){
                    if($resource->isJavascript()){
                        $sorted[$resource->getRanking(). strtolower($handle)] = $resource;
                    }
                }
                break;

            case 'css':
                foreach($this->resourceEntities as $handle => $resource){
                    if($resource->isStylesheet()){
                        $sorted[$resource->getRanking(). strtolower($handle)] = $resource;
                    }
                }
                break;
        }

        //sort the entities by theire ranking.
        ksort($sorted);

        return $sorted;


    }

    protected function enqueueStyleResources($handle, $expression = NULL){

        switch(isset($expression)){

            case true:

                if($expression){
                    wp_enqueue_style($handle);
                }
                break;

            case false:
                wp_enqueue_style($handle);
                break;
        }
    }

    protected function enqueueScriptResources($handle, $expression = NULL){

        switch(isset($expression)){

            case true:
                if($expression){
                    wp_enqueue_script($handle);
                }
                break;

            case false:
                wp_enqueue_script($handle);
                break;
        }
    }

    protected function registerResources($resource){

        $handle = $resource->getHandle();
        $fileName = $resource->getFileName();
        $content = $resource->getContent();
        $resourceType = $resource->getFileType();
        $type = $resource->getType();
        $position = $resource->getPosition();

        switch($resourceType){
            case 'js':
                $resourceFolder = '/' . self::JAVASCRIPT_RESOURCES;
                break;

            case 'css':
                $resourceFolder = '/' . self::STYLESHEETS;
                break;
        }

        switch($position){

            case 'header';
                $position = false;
                break;

            case 'footer':
                $position = true;
                break;
        }

        $context = $this->bootstrap->getContext();

        $register = new RegisterResource($handle, $fileName, $context);

        switch($type){
            case 'cdn':
                $src = $resource->getResourcePath() . $resource->getFileName();
                break;

            case 'local' || 'localCDN':

                $src = $resource->getPublicURI();
                if ($context->isDevelopment() || $context->isTesting() || !file_exists($resource->getPublicPath())) {
                    $register->dumpReadable($content, NULL, $resourceFolder);
                }
                break;
        }


        $register->registerResource($resourceType, $src, $position);
    }

    protected function buildCompiledCSS(){
        $mergedHeaderResources = new ResourceMerger();

        foreach($this->resourceEntities as $name => $resource){

            if($resource->isTypeLocal() && $resource->isCompileEnabled() && $resource->embedInHeader() &&  $resource->isStylesheet()){
                $mergedHeaderResources->addResource($resource->getfileName(), $resource->getResourcePath() . '/', $resource->getRanking());
            }

            if($resource->isTypeLocal() && $resource->isCompileEnabled() && $resource->embedInFooter() &&  $resource->isStylesheet()){
                throw new Exception('Sorry but ' . $resource->getFileName() . ' could not be merged! Please check your position settings in the Resource.yaml. Footer is not supported for CSS Files.');
            }
        }

        $mergedHeaderResources->sortResources(true);
        $output = $mergedHeaderResources->render();

        return $output;
    }

    protected function buildCompiledJS($position){

        $mergedHeaderResources = new ResourceMerger();

        foreach($this->resourceEntities as $name => $resource){

            switch($position){

                case 'Header':
                    if($resource->isCompileEnabled() && $resource->embedInHeader() &&  $resource->isJavascript()){
                        $mergedHeaderResources->addResource($resource->getfileName(), $resource->getResourcePath() . '/', $resource->getRanking());
                    }
                    break;

                case 'Footer':
                    if($resource->isCompileEnabled() && $resource->embedInFooter() &&  $resource->isJavascript()){
                        $mergedHeaderResources->addResource($resource->getfileName(), $resource->getResourcePath() . '/', $resource->getRanking());
                    }
                    break;
            }
        }

        $mergedHeaderResources->sortResources(true);
        $output = $mergedHeaderResources->render();

        return $output;
    }


    protected function loadResourceContent($resources, $packageKey){

        foreach($resources as $handle => $resource){

            switch($resources[$handle]['Type']){

                case 'local':
                    $path = $resources[$handle]['Path'];
                    $file = $resources[$handle]['Filename'];

                    // check is the file exists
                    $this->isResourceAvailable($path, $file);
                    $content = Files::getFileContents($path . '/' . $file);
                    $resources[$handle]['Content'] = $content;
                    break;

                case 'localCDN':
                    $path = $resources[$handle]['Path'];
                    $file = $resources[$handle]['Filename'];
                    $fileType = pathinfo($file, PATHINFO_EXTENSION);

                    if($fileType == 'js'){
                        $fileExists = file_exists(get_template_directory() . '/js/' . $file);

                    } elseif($fileType == 'css'){

                        $fileExists = file_exists(get_template_directory() . '/css/' . $file);
                    }

                    if(!$fileExists) {
                        $cdnContent = file_get_contents($path . $file);
                        $resources[$handle]['Content'] = $cdnContent;
                    }
                    break;
            }

            $this->registeredResources[$packageKey]['Public'][$handle] = $resources[$handle];
        }
    }


    /**
     * Resolves the local path by file type. If it´s an .js file for example,
     * the file has to reside in the Resources/Public/js directory.
     * @param $path
     * @param $resources
     * @param $packageKey
     */
    protected function resolvePathByType($path, $resources, $packageKey){

        foreach($resources as $handle => $resource){

            if($resources[$handle]['Type'] == 'local') {
                $file = $resources[$handle]['Filename'];
                $input = pathinfo($file, PATHINFO_EXTENSION);

                switch ($input) {

                    case 'css':
                        $resources[$handle]['Path'] = $path . '/' . self::STYLESHEETS;
                        break;

                    case 'scss':
                        $resources[$handle]['Path'] = $path . '/' . self::SASSSTYLESHEETS;
                        break;

                    case 'js':
                        $resources[$handle]['Path'] = $path . '/' . self::JAVASCRIPT_RESOURCES;
                        break;

                    default:
                        $resources[$handle]['Path'] = $path;
                }
                $this->registeredResources[$packageKey]['Public'][$handle] = $resources[$handle];
            }
        }
    }

    /**
     * Resolves the right type and passes the path to the array
     * If its an local resource the path ist build from the packages resource dir
     * If its an external resource, the path is the uri from the cdn, which had to be registered in the Resource.yaml file.
     *
     * @param array $resources
     * @param $path
     * @param $packageKey
     * @return void
     */
    protected function resolveResourceType($resources = array(), $path, $packageKey){

        foreach($resources as $handle => $resource){

            switch($resources[$handle]['Type']){

                case 'local':
                    $resources[$handle]['Path'] = $path;
                    break;

                case 'cdn'|| 'CDN':
                    $resources[$handle]['Path'] = $resources[$handle]['CDN'];
                    unset($resources[$handle]['CDN']);
                    break;

                default:
                    $resources[$handle]['Path'] = $path;
            }
            $this->registeredResources[$packageKey]['Public'][$handle] = $resources[$handle];
        }
    }


    /**
     * Checks if the registered resource really exists in the resource directory
     * @return bool
     */
    protected function isResourceAvailable($path, $file){
        if(!file_exists($path.'/'.$file)){
            echo "The <strong style='color: red'>$file</strong> File you registered could not be found in the resource dir!" . '</br>' ;
        }
    }
}