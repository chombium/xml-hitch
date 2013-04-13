<?php
namespace Hitch\Demo\Entity;

/**
 * @xml:XmlObject
 */
class ModelCategory
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
}
