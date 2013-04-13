<?php
namespace Hitch\Demo\Entity;

/**
 * @xml:XmlObject
 */
class ModelAttributeComplex
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
     * @xml:XmlAttribute
     */
    protected $group;
    /**
     * @xml:XmlAttribute
     */
    protected $creator;
    
    /**
     * @xml:XmlValue
     */
//     protected $value;
    
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
    
    public function getGroup() 
    {
        return $this->group;
    }
    
    public function setGroup($group)
    {
        $this->group = $group;
    }
    
    public function getCreator()
    {
        return $this->creator;
    }
    
    public function setCreator($creator)
    {
        $this->creator = $creator;
    }
    
    public function getValue() 
    {
        return $this->value;
    }
    
    public function setValue($value) 
    {
        $this->value = trim($value);
    }
}
