<?php

/**
 * This file is part of the Hitch Demo package
 *
 * (c) Marc Roulias <marc@lampjunkie.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Cache\ArrayCache;

use Hitch\HitchManager;
use Hitch\Mapping\ClassMetadataFactory;
use Hitch\Mapping\Loader\AnnotationLoader;

// include the demo class loader
include 'ClassLoader.php';

// set path to doctrine-common lib
$DOCTRINE_COMMON_LIB = '/opt/development/projects/ostec/web-projects/ws-api/test-project/vendor';

// make sure doctrine-common exists
if(!is_dir($DOCTRINE_COMMON_LIB)){
  die('<span style="color: red;">Make sure to download and install the doctrine-common (<a href="https://github.com/doctrine/common">https://github.com/doctrine/common</a>) library to: ' . $DOCTRINE_COMMON_LIB . ' !!!</span>');
}

// register namespaces for demo
$loader = new ClassLoader();
$loader->registerNamespaces(array(
    'Hitch'           	=> __DIR__.'/../lib',	                  // main Hitch lib
    'Hitch\\Demo'     	=> __DIR__.'/src',	                      // Hitch demo package
    'Doctrine\\Common' 	    => $DOCTRINE_COMMON_LIB,  // Doctrine common library
));

// register the autoloading
$loader->register();

// create our new HitchManager
$hitch = new HitchManager();
$hitch->setClassMetaDataFactory(new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()), new ArrayCache()));

// pre-build the class meta data cache
$hitch->registerRootClass('Hitch\\Demo\\Entity\\ModelComplex');
// $hitch->registerRootClass('Hitch\\Demo\\Entity\\ModelAttributeComplex');
// $hitch->registerRootClass('Hitch\\Demo\\Entity\\ModelCategory');
$hitch->buildClassMetaDatas();

// load XML file to parse
$xml = file_get_contents("modelcomplex.xml");

print_r($xml);
echo "\n===========\n";

// parse the xml into a Catalog object
$model = $hitch->unmarshall($xml, 'Hitch\\Demo\\Entity\\ModelComplex');
// print_r($model);

$result = $hitch->marshall($model);
print_r($result);