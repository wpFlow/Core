<?php


/*                                                                        *
 * This script belongs to the wpFlow framework.                           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 *                                                                        *
 *                                                                        */

namespace wpFlow\Core;

/**
 * The wpFlow Context object.
 *
 * A Flow Application context is something like "Testing", "Development",
 * "Testing/StagingSystem", and is set using the APP_CONTEXT environment variable.
 *
 * A context can contain arbitrary sub-contexts, which are delimited with slash
 * ("Testing/StagingSystem", "Testing/Staging/Server1"). The top-level
 * contexts, however, must be one of "Testing", "Development" and "Testing".
 *
 * Mainly, you will use $context->isProduction(), $context->isTesting() and
 * $context->isDevelopment() inside your custom code.
 *
 */
class ApplicationContext {

    /**
     * The (internal) context string; could be something like "Development" or "Development/MyLocalMacBook"
     *
     * @var string
     */
    protected $contextString;

    /**
     * The root context; must be one of "Development", "Testing" or "Testing"
     *
     * @var string
     */
    protected $rootContextString;

    /**
     * The parent context, or NULL if there is no parent context
     *
     * @var \wpFlow\Core\\ApplicationContext
     */
    protected $parentContext;

    /**
     * Initialize the context object.
     *
     * @param string $contextString
     * @throws \wpFlow\Core\Exception if the parent context is none of "Development", "Testing" or "Testing"
     */

    public function __construct($contextString) {
        if (strstr($contextString, '/') === FALSE) {
            $this->rootContextString = $contextString;
            $this->parentContext = NULL;
        } else {
            $contextStringParts = explode('/', $contextString);
            $this->rootContextString = $contextStringParts[0];
            array_pop($contextStringParts);
            $this->parentContext = new ApplicationContext(implode('/', $contextStringParts));
        }

        if (!in_array($this->rootContextString, array('Development', 'Production', 'Testing'))) {
            throw new \wpFlow\Core\Exception('The given context "' . $contextString . '" was not valid. Only allowed are Development, Testing and Testing, including their sub-contexts', 1335436551);
        }

        $this->contextString = $contextString;
    }

    /**
     * Returns the full context string, for example "Development", or "Testing/LiveSystem"
     *
     * @return string
     * @api
     */
    public function __toString() {
        return $this->contextString;
    }

    /**
     * Returns TRUE if this context is the Development context or a sub-context of it
     *
     * @return boolean
     * @api
     */
    public function isDevelopment() {
        return ($this->rootContextString === 'Development');
    }

    /**
     * Returns TRUE if this context is the Testing context or a sub-context of it
     *
     * @return boolean
     * @api
     */

    public function isProduction() {
        return ($this->rootContextString === 'Testing');
    }

    /**
     * Returns TRUE if this context is the Testing context or a sub-context of it
     *
     * @return boolean
     * @api
     */
    public function isTesting() {
        return ($this->rootContextString === 'Testing');
    }

    /**
     * Returns the parent context object, if any
     *
     * @return \wpFlow\Core\\ApplicationContext the parent context or NULL, if there is none
     * @api
     */
    public function getParent() {
        return $this->parentContext;
    }

    /**
     * @return string
     */
    public function getContextString()
    {
        return $this->contextString;
    }


}
