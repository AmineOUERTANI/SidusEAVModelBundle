<?php

namespace Sidus\EAVModelBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use LogicException;
use Sidus\EAVModelBundle\Model\AttributeInterface;
use Sidus\EAVModelBundle\Model\FamilyInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * @\Sidus\EAVModelBundle\Validator\Constraints\Data()
 */
abstract class Data
{
    /*
    * THE FOLLOWING FIELDS NEED TO BE REDECLARED IN YOUR MAIN CLASS
    * Because they are not matching any real entities
    */

    /**
     * @var Data
     * @ORM\ManyToOne(targetEntity="Sidus\EAVModelBundle\Entity\Data", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="cascade")
     */
    protected $parent;

    /**
     * @var Data[]
     * @ORM\OneToMany(targetEntity="Sidus\EAVModelBundle\Entity\Data", mappedBy="parent", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    protected $children;

    /**
     * @var Value[]|Collection
     * @ORM\OneToMany(targetEntity="Sidus\EAVModelBundle\Entity\Value", mappedBy="data", cascade={"persist", "remove"}, fetch="EAGER", orphanRemoval=true)
     * @ORM\OrderBy({"position" = "ASC"})
     */
    protected $values;

    /*
     * END OF WHAT YOU HAVE TO REDECLARE IN YOUR MAIN CLASS
     */

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var \DateTime
     * @ORM\Column(name="created_at", type="datetime")
     */
    protected $createdAt;

    /**
     * @var \DateTime
     * @ORM\Column(name="updated_at", type="datetime")
     */
    protected $updatedAt;

    /**
     * @var string
     * @ORM\Column(name="family_code", type="string", length=255)
     */
    protected $familyCode;

    /** @var FamilyInterface */
    protected $family;

    /**
     * Initialize the data with an optional (but recommended family code)
     *
     * @param FamilyInterface $family
     * @throws LogicException
     */
    public function __construct(FamilyInterface $family)
    {
        if (!$family->isInstantiable()) {
            throw new LogicException("Family {$family->getCode()} is not instantiable");
        }
        $this->family = $family;
        $this->familyCode = $family->getCode();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        $this->values = new ArrayCollection();
        $this->children = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @param \DateTime $createdAt
     * @return Data
     */
    public function setCreatedAt(\DateTime $createdAt = null)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $updatedAt
     * @return Data
     */
    public function setUpdatedAt(\DateTime $updatedAt = null)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param Data $parent
     * @return Data
     */
    public function setParent(Data $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return Data
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @param string $familyCode
     * @return Data
     */
    public function setFamilyCode($familyCode)
    {
        $this->familyCode = $familyCode;

        return $this;
    }

    /**
     * @return string
     */
    public function getFamilyCode()
    {
        return $this->familyCode;
    }

    /**
     * Return all values matching the attribute code
     *
     * @param string|null $attributeCode
     * @return Collection|Value[]
     */
    public function getValues($attributeCode = null)
    {
        if (null === $attributeCode) {
            return $this->values;
        }
        $values = new ArrayCollection();
        foreach ($this->values as $value) {
            if ($value->getAttributeCode() === $attributeCode) {
                $values->add($value);
            }
        }
        return $values;
    }

    /**
     * Return first value found for attribute code in value collection
     *
     * @param string|null $attributeCode
     * @return Value|null
     */
    public function getValue($attributeCode)
    {
        return $this->getValues($attributeCode)->first();
    }

    /**
     * Get the value data of the value matching the attribute
     *
     * @param AttributeInterface $attribute
     * @return mixed
     * @throws \Exception
     */
    public function getValueData(AttributeInterface $attribute)
    {
        $value = $this->getValue($attribute->getCode());
        if (!$value) {
            return null;
        }
        $accessor = PropertyAccess::createPropertyAccessor();
        return $accessor->getValue($value, $attribute->getType()->getDatabaseType());
    }

    /**
     * Get the values data of multiple values for a given attribute
     *
     * @param AttributeInterface $attribute
     * @return mixed
     * @throws \Exception
     */
    public function getValuesData(AttributeInterface $attribute)
    {
        $accessor = PropertyAccess::createPropertyAccessor();
        $values = $this->getValues($attribute->getCode());
        if (0 === count($values)) {
            return [];
        }
        $valuesData = new ArrayCollection();
        foreach ($values as $value) {
            $valuesData->add($accessor->getValue($value, $attribute->getType()->getDatabaseType()));
        }

        return $valuesData;
    }

    /**
     * Set the value's data of a given attribute
     *
     * @param AttributeInterface $attribute
     * @param $data
     * @return mixed
     * @throws Exception
     */
    public function setValueData(AttributeInterface $attribute, $data)
    {
        $value = $this->getValue($attribute->getCode());
        if (!$value) {
            $value = $this->createValue($attribute);
        }
        $accessor = PropertyAccess::createPropertyAccessor();
        $accessor->setValue($value, $attribute->getType()->getDatabaseType(), $data);
    }

    /**
     * Set the value's datas of a given attribute for multiple fields
     *
     * @param AttributeInterface $attribute
     * @param array|\Traversable $datas
     * @return mixed
     * @throws Exception
     */
    public function setValuesData(AttributeInterface $attribute, $datas)
    {
        $accessor = PropertyAccess::createPropertyAccessor();
        $values = $this->getValues($attribute->getCode());
        /** @var Value $value */
        foreach ($values as $value) {
            $this->removeValue($value);
        }

        $position = 0;
        foreach ($datas as $data) {
            /** @noinspection DisconnectedForeachInstructionInspection */
            $value = $this->createValue($attribute);
            $value->setPosition($position++);
            $accessor->setValue($value, $attribute->getType()->getDatabaseType(), $data);
        }
    }

    /**
     * @param Value $value
     * @return $this
     */
    public function addValue(Value $value)
    {
        $value->setData($this);
        return $this;
    }

    /**
     * @param Value $value
     * @return $this
     */
    public function removeValue(Value $value)
    {
        $this->values->removeElement($value);
        $value->setData(null);
        return $this;
    }

    protected function getLabelValue()
    {
        if (!$this->getFamily()) {
            throw new \UnexpectedValueException('Missing family code');
        }
        return (string)$this->getValueData($this->getFamily()->getAttributeAsLabel());
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        try {
            return $this->getLabelValue();
        } catch (Exception $e) {
            return "[{$this->getId()}]";
        }
    }

    /**
     * @return string
     */
    public function __toString()
    {
        try {
            return $this->getLabelValue();
        } catch (Exception $e) {
            return '';
        }
    }

    public function __call($methodName, $arguments)
    {
        if (substr($methodName, 0, 3) === 'get') {
            return $this->__get(substr($methodName, 3));
        }
        $attribute = $this->getAttribute($methodName);
        if ($attribute) {
            return $this->__get($methodName);
        }
        $class = get_class($this);
        throw new \BadMethodCallException("Method '{$methodName}' for object '{$class}' with family '{$this->getFamilyCode()}' does not exist");
    }

    /**
     * Used to seemingly get values as if they were normal properties of this class
     *
     * @param string $name
     * @return mixed|null|Value
     * @throws \Exception
     */
    public function __get($name)
    {
        $attributeCode = $name;
        $returnData = true;
        if (substr($name, -5) === 'Value') {
            $returnData = false;
            $attributeCode = substr($name, 0, -5);
        }
        $attribute = $this->getAttribute($attributeCode);
        if (!$attribute) {
            throw new \BadMethodCallException("No attribute or method named {$name}");
        }

        if ($attribute->isMultiple()) {
            if ($returnData) {
                return $this->getValuesData($attribute);
            }
            return $this->getValues($attributeCode);
        }
        if ($returnData) {
            return $this->getValueData($attribute);
        }
        return $this->getValue($attributeCode);
    }

    /**
     * Used to seemingly set values as if they were normal properties of this class
     *
     * @param string $name
     * @param mixed|null|Value $value
     * @throws \Exception
     */
    public function __set($name, $value)
    {
        $attributeCode = $name;
        $setData = true;
        if (substr($name, -5) === 'Value') {
            $setData = false;
            $attributeCode = substr($name, 0, -5);
        }
        $attribute = $this->getAttribute($attributeCode);
        if (!$attribute) {
            throw new \BadMethodCallException("No attribute or method named {$name}");
        }

        if ($attribute->isMultiple()) {
            if ($setData) {
                $this->setValuesData($attribute, $value);
                return;
            }
            foreach ($value as $v) {
                $this->addValue($v);
            }
            return;
        }
        if ($setData) {
            $this->setValueData($attribute, $value);
            return;
        }
        $this->addValue($value);
    }

    /**
     * @param $attributeCode
     * @return AttributeInterface
     */
    protected function getAttribute($attributeCode)
    {
        return $this->getFamily()->getAttribute($attributeCode);
    }

    /**
     * @return FamilyInterface
     */
    public function getFamily()
    {
        return $this->family;
    }

    /**
     * @param FamilyInterface $family
     * @return $this
     */
    public function setFamily(FamilyInterface $family)
    {
        $this->family = $family;
        return $this;
    }

    /**
     * @param AttributeInterface $attribute
     * @return Value
     */
    public function createValue(AttributeInterface $attribute)
    {
        return $this->getFamily()->createValue($this, $attribute);
    }
}
