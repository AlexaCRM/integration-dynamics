<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 * (c) AlexaCRM
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with Twig source code.
 */

namespace AlexaCRM\WordpressCRM\Cache;

/**
 * Implements a cache on the filesystem.
 *
 * On WPEngine, creating .php files results in strict 600 permissions for the new file,
 * which breaks the Twig cache. Other extensions, such as .html, work fine.
 *
 * @author Andrew Tch <andrew@noop.lv>
 */
class TwigCache extends \Twig_Cache_Filesystem {

    private $directory;
    private $options;

    public function __construct( $directory, $options = 0 ) {
        $this->directory = rtrim($directory, '\/').'/';
        $this->options = $options;
    }

    public function generateKey( $name, $className ) {
        $hash = hash('sha256', $className);

        return $this->directory.$hash[0].$hash[1].'/'.$hash.'.html';
    }

}
