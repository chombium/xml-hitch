<?php
namespace Hitch\Demo\Entity;

/**
 * @xml:XmlObject
 */
class ModelAttributeWithValue
{
    /**
     * @xml:XmlElement
     */
    protected $id;
    
    /**
     * @xml:XmlElement
     */
    protected $name;
    
    /**
     * @xml:XmlElement
     */
    protected $description;
    
    /**
     * @xml:XmlValue
     */
    protected $value;
    
    public function getId()
    {
        return $this->id;      
    }
    
    public function setId($id)
    {
        $this->id = $id;
    }
    
    public function getName()
    {
        return $this->name;      
    }
    
    public function setName($name)
    {
        $this->name = $name;
    }
    
    public function getDescription()
    {
        return $this->description;
    }
    
    public function setDescription($description)
    {
        $this->description = $description;
    }
    
    public function getValue() 
    {
        return $this->value;
    }
    
    public function setValue($value) {
        $this->value = $value;
    }
}
