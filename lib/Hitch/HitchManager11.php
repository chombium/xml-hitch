<?php

/**
 * This file is part of the Hitch package
 * 
 * (c) Marc Roulias <marc@lampjunkie.com>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Hitch;

use Hitch\Mapping\ClassMetadata;
use Hitch\Mapping\ClassMetaDataFactory;
use Doctrine\Common\Cache\Cache;
use \SimpleXmlElement;
use \DOMDocument;
use \DOMNode;

/**
 * HitchManager manages all operations of Hitch
 * 
 * @author marc
 */
class HitchManager
{
  /**
   * The ClassMetadata factory
   * 
   * @var ClassMetadataFactory
   */
  protected $classMetadataFactory;

  /**
   * Array to store root class names
   * 
   * @var array
   */
  protected $rootClasses = array();

  /**
   * Register a root class name for pre-caching
   * 
   * @param string $class
   */
  public function registerRootClass($class)
  {
    $this->rootClasses[] = $class;
    return $this;
  }

  /**
   * Unmarshall an XML string into an object graph
   * 
   * @param string $xmlString
   * @param string $rootClass
   */
  public function unmarshall($xmlString, $rootClass)
  {
    $metadata = $this->classMetadataFactory->getClassMetadata($rootClass);
    $xml = new SimpleXMLElement($xmlString);

    return $this->parseObject($xml, $metadata);
  }

  /**
   * Parse an object from a SimpleXml node
   * 
   * @param SimpleXmlElement $xml
   * @param ClassMetadata $metadata
   */
  protected function parseObject(\SimpleXmlElement $xml, ClassMetadata $metadata)
  {
    $class = $metadata->getClassName();
    $obj = new $class();

    $this->parseAttributes($xml, $metadata, $obj);
    $this->parseElements($xml, $metadata, $obj);
    $this->parseEmbeds($xml, $metadata, $obj);
    $this->parseLists($xml, $metadata, $obj);
    $this->parseValue($xml, $metadata, $obj);

    return $obj;
  }

  /**
   * Parse all of the xml attributes from a SimpleXml node
   * and set them to the given object
   * 
   * @param SimpleXmlElement $xml
   * @param ClassMetadata $metadata
   * @param stdClass $obj
   */
  protected function parseAttributes(\SimpleXmlElement $xml, ClassMetadata $metadata, $obj)
  {
    foreach($metadata->getAttributes() as $name => $info){

      $property = $info[0];
      $nodeName = $info[1];

      if(!is_null($nodeName)){

        $node = $xml->$nodeName;
        $value = (string) $node[$name];

      } else {
        $value = (string) $xml[$name];
      }

      $setter = 'set' . ucfirst($property);
      $obj->$setter($value);
    }
  }

  /**
   * Parse simple elements from a SimpleXml node
   * and set them to the given object
   * 
   * @param SimpleXmlElement $xml
   * @param ClassMetadata $metadata
   * @param stdClass $obj
   */
  protected function parseElements(\SimpleXmlElement $xml, ClassMetadata $metadata, $obj)
  {
    foreach($metadata->getElements() as $name => $property){
      $value = (string) $xml->$name;
      $setter = 'set' . ucfirst($property);
      $obj->$setter($value);
    }
  }
  
  /**
   * Parse embedded objects from a SimpleXml node
   * and set them to the given object
   * 
   * @param SimpleXmlElement $xml
   * @param ClassMetadata $metadata
   * @param stdClass $obj
   */
  protected function parseEmbeds(\SimpleXmlElement $xml, ClassMetadata $metadata, $obj)
  {
    foreach($metadata->getEmbeds() as $nodeName => $info){

      $property = $info[0];
      $tempMetaData = $info[1];

      $tempXml = $xml->$nodeName;
      $tempObj = $this->parseObject($tempXml, $tempMetaData);
      $setter = 'set' . ucfirst($property);
      $obj->$setter($tempObj);
    }
  }

  /**
   * Parse arrays from a SimpleXml node
   * and set them to the given object
   * 
   * @param SimpleXmlElement $xml
   * @param ClassMetadata $metadata
   * @param stdClass $obj
   */
  protected function parseLists(\SimpleXmlElement $xml, ClassMetadata $metadata, $obj)
  {
    foreach($metadata->getLists() as $nodeName => $info){
       
      $property = $info[0];
      $wrapperNode = $info[1];
      $listMetadata = $info[2];

      $tempList = array();

      if(!is_null($wrapperNode)){

        if(isset($xml->$wrapperNode)){
          foreach($xml->$wrapperNode->$nodeName as $item){
            if(!is_null($listMetadata)){
              $tempObj = $this->parseObject($item, $listMetadata);
              $tempList[] = $tempObj;
            } else {
              $tempList[] = (string) $item;
            }
          }
        }

      } else {

        foreach($xml->$nodeName as $item){
          $tempObj = $this->parseObject($item, $listMetadata);
          $tempList[] = $tempObj;
        }
      }

      $setter = 'set' . ucfirst($property);
      $obj->$setter($tempList);
    }
  }

  /**
   * Parse the value from a SimpleXml node
   * 
   * @param SimpleXmlElement $xml
   * @param ClassMetadata $metadata
   * @param stdClass $obj
   */
  protected function parseValue(\SimpleXmlElement $xml, ClassMetadata $metadata, $obj)
  {
    if(!is_null($metadata->getValue())){
      $setter = 'set' . ucfirst($metadata->getValue());
      $value = (string)$xml;
      $obj->$setter($value);
    }
  }

  /**
   * Marshall an object into an xml string
   * 
   * @param stdClass $object
   * @throws Exception
   */
  public function marshall($object)
  {
  
    $rootClass = get_class($object);
    $metadata = $this->classMetadataFactory->getClassMetadata($rootClass);
    $rootElement = strtolower($this->getClassNameWithoutNamespace($rootClass));

    //FIXME remove me
    //var_dump($metadata);

    $xml = $this->createXml($object, $metadata, $rootClass);


   return $xml->asXML();
  }
  
  /**
   * Extracts the class name from a class given with a namespace
   * If the namespace is not present than the class name is returned
   * @param String $class The class name with or without namespace
   * @return String The extracted class name
   */
  private function getClassNameWithoutNamespace($class)
  {
      if (strpos($class, '\\') == false)
      {
          return $class;
      }
  	  $parts = explode('\\', $class);
      return $parts[count($parts) - 1];
  }
  
  /**
   * Examines an object and creates an xml representation
   * @param mixed $object the object to be examined
   * @return \DOMNode the generated node
   */
  private function createXml($object, $metadata = null, $rootClass = null)
  {
      if (!$rootClass)
      {
          $rootClass = get_class($object);
          $metadata = $this->classMetadataFactory->getClassMetadata($rootClass);
      }
      
      $element = strtolower($this->getClassNameWithoutNamespace($rootClass));
      $valueProperty = $metadata->getValue();
      
      // create element
      $xml = $this->createXmlElement($object, $element, $valueProperty);

      // add attributes
      $xml = $this->createAttribites($xml, $object, $metadata);

      // add elements
      $xml = $this->createElements($xml, $object, $metadata);

      // process embedds
      $xml = $this->createEmbeds($xml, $object, $metadata);
      // process lists
      
      return $xml;
  }
  
  /**
   * Creates an XML element
   * @param unknown $object
   * @param string $metadata
   * @param string $rootClass
   * @return unknown
   */
  private function createXmlDocument($xmlDoc, $object, $metadata = null, $rootClass = null)
  {
      if (!$rootClass)
      {
          $rootClass = get_class($object);
          $metadata = $this->classMetadataFactory->getClassMetadata($rootClass);
      }
      
      $rootElement = strtolower($this->getClassNameWithoutNamespace($rootClass));
      $valueProperty = $metadata->getValue();

      $xmlDoc = $this->createXmlElementWithValue($xmlDoc, $object, $rootElement, $valueProperty);
      
//       $xmlDoc = $this->createAttribites($xmlDoc, $object, $metadata);
//       $xmlDoc = $this->createElements($xmlDoc, $object, $metadata);
//       $xmlDoc = $this->createEmbeds($xmlDoc, $object, $metadata);
      
      return $xmlDoc;
  }
  
  /**
   * Creates the child elements of an XML node
   * @param unknown $xml
   * @param unknown $object
   * @param unknown $metadata
   * @return unknown
   */
  private function createElements($xml, $object, $metadata) 
  {
      foreach ($metadata->getElements() as $property)
      {
          $xml->addChild($property, $this->getPropertyValue($object, $property));
      }

      return $xml;
  }
  
  /**
   * Creates node attributes
   * @param unknown $xml
   * @param unknown $object
   * @param unknown $metadata
   * @return unknown
   */
  private function createAttribites($xml, $object, $metadata) 
  {
      foreach ($metadata->getAttributes() as $attribute)
      {
          $xml->addAttribute($attribute[0], $this->getPropertyValue($object, $attribute[0]));
          
      }
      return $xml;
  }

  /**
   * Creates embedded objects
   * @param unknown $xml
   * @param unknown $object
   * @param unknown $metadata
   * @return unknown
   */
  private function createEmbeds($xml, $object, $metadata)
  {
      var_dump($xml->asXml());
         
          
      foreach ($metadata->getEmbeds() as $embed)
      {
          $embeddedObject = $this->getPropertyValue($object, $embed[0]);
          var_dump($embeddedObject);
          
          $embeddedNode = $xml->addChild($embed[0]);
          
          $xml = $this->createElements($embeddedNode, $embeddedObject, $metadata)
          
          var_dump($xml->asXml());
          
      }
      
      return $xml;
  }
    
  /**
   * Creates an XML element
   * @param unknown $object
   * @param unknown $rootElement
   * @param string $valueProperty
   */
  private function createXmlElement($object, $element, $valueProperty = null)
  {
      if (! is_null($valueProperty))
      {
          $xml = new SimpleXMLElement("<$element>" .
                     $this->getPropertyValue($object, $valueProperty) .
                     "</$element>");
      }
      else
      {
          $xml = new SimpleXMLElement("<$element></$element>");
      }
      
      return $xml;
  }
  
  private function getPropertyValue($object, $property)
  {
      $methodName = 'get'. ucfirst($property);
      return call_user_func(array($object, $methodName));
  }
  
  /**
   * Set the ClassMetadataFactory
   * 
   * @param ClassMetadataFactory $classMetadataFactory
   */
  public function setClassMetadataFactory(ClassMetadataFactory $classMetadataFactory)
  {
    $this->classMetadataFactory = $classMetadataFactory;
  }

  /**
   * Pre-build the ClassMetadata for all the registered root classes
   */
  public function buildClassMetadatas()
  {
    foreach($this->rootClasses as $class){
      $this->classMetadataFactory->getClassMetadata($class);
    }
  }

}
