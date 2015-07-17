<?php
/**
 * Created by PhpStorm.
 * User: Dennis
 * Date: 02.07.15
 * Time: 15:45
 */

namespace wpFlow\Core\Resource;
use stdClass;

/**
 * ResourceMerger Class needed if you're looking for something to merge your resource files and output the data
 */
class ResourceMerger {

    // Directory of Resource files
    public $directory = '';
    // Array of loaded Resource files
    public $resource = array();
    // In case of errors
    public $errors = array();
    // List of the things you want to strip
    public static $strips = array(
        '#\s*(?<!:)//.*+#',
        "!/\*[^*]*\*+([^/][^*]*\*+)*/!",
        '/(\s)+/s',
        '/[\t\r\n]/',
    );

    /**
     * Add resource to the queue
     * @param filename $filename
     * @param path $path path of the file, if not set then the class will use the directory defined
     * @return \ResourceMerger
     */
    public function addResource($filename, $path = null, $ranking) {

        $rankingKey = $ranking . strtolower($filename);

        // Start an stdClass
        $this->resource[$rankingKey] = new stdClass();

        // Add filename to the file stdClass
        $this->resource[$rankingKey]->filename = $filename;

        if ($path) {
            // If Path is set, use the specified path
            $this->resource[$rankingKey]->path = $path . $filename;
        } else {
            // If not, Use the predefined path, see line 10
            $this->resource[$rankingKey]->path = $this->directory . $filename;
        }

        return $this;

    }

    /**
     * Remove a resource file from the queue
     * @param type $filename
     * @return \ResourceMerger
     */
    public function removeResource($filename) {
        unset($this->resource[$filename]);
        return $this;
    }

    public function sortResources($boolean = true){
        if($boolean){
            ksort($this->resource);
        }
    }

    /**
     * Render the final results
     * @param boolean $echo Default TRUE, if set to false there will be no output
     * @return Resource output Will render the final result
     */
    public function render($compress = true) {

        // Initiate the output
        $output = null;

        // Start
        ob_start();

        // Loop throught $resource array
        foreach ($this->resource as $resource) {

            // Check if resource file exists && it's not a directory or give error
            if (file_exists($resource->path) && !is_dir($resource->path)) {
                // Include the Resource File
                include_once($resource->path);
            } else {
                // Log the error, file does not exist
                $this->errors['not_found'][$resource->filename] = $resource->filename . ' was not found in: ' . $resource->path;
            }
        }

        // Get Content
        $output = ob_get_clean();

        // If $compress is set to true(default) then we strip the elements you want to remove
        if ($compress === true) {
            // Remove the elements
            $output = preg_replace(self::$strips, '', $output);
        }

            // Return the output
            return $output;
    }

}

