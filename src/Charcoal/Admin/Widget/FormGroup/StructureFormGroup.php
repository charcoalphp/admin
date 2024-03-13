<?php

namespace Charcoal\Admin\Widget\FormGroup;

use RuntimeException;
use OutOfBoundsException;
use UnexpectedValueException;
use InvalidArgumentException;
// From 'charcoal-property'
use Charcoal\Property\ModelStructureProperty;
use Charcoal\Property\PropertyInterface;
// From 'charcoal-ui'
use Charcoal\Ui\ConditionalizableInterface;
use Charcoal\Ui\FormGroup\AbstractFormGroup;
use Charcoal\Ui\FormGroup\FormGroupInterface;
use Charcoal\Ui\FormInput\FormInputInterface;
// From 'charcoal-admin'
use Charcoal\Admin\Widget\FormGroupWidget;
use Charcoal\Admin\Ui\LanguageSwitcherAwareInterface;
use Charcoal\Admin\Ui\ObjectContainerInterface;
use Charcoal\Admin\Ui\StructureContainerInterface;
use Charcoal\Admin\Ui\StructureContainerTrait;

/**
 * Form Group Structure Property
 *
 * The form group widget displays a set of form controls based on properties
 * assigned to the widget directly or a proxy structure property.
 *
 * ## Examples
 *
 * **Example #1 — Structure widget**
 *
 * ```json
 * "properties": {
 *     "extra_data": {
 *         "type": "structure",
 *         "structure_metadata": {
 *             "properties": { … },
 *             "admin": {
 *                 "form_groups": { … },
 *                 "default_form_group": "…"
 *             }
 *         }
 *     }
 * },
 * "widgets": [
 *     {
 *         "title": "Extra Data",
 *         "type": "charcoal/admin/widget/form-group/structure",
 *         "template": "charcoal/admin/widget/form-group/structure",
 *         "storage_property": "extra_data"
 *     }
 * ]
 * ```
 *
 * **Example #2 — With verbose storage declaration**
 *
 * {@todo Eventually, the form group could support other storage sources such as
 * file-based or a database such as an SQL server.}
 *
 * ```json
 * {
 *     "title": "Extra Data",
 *     "type": "charcoal/admin/widget/form-group/structure",
 *     "template": "charcoal/admin/widget/form-group/structure",
 *     "storage": {
 *         "type": "property",
 *         "property": "extra_data"
 *     }
 * }
 * ```
 *
 */
class StructureFormGroup extends FormGroupWidget implements
    FormInputInterface,
    LanguageSwitcherAwareInterface,
    StructureContainerInterface
{
    use StructureContainerTrait;

    /**
     * The structure entry identifier.
     *
     * @var string
     */
    private $structId;

    /**
     * The form group's storage model.
     *
     * @var \Charcoal\Model\ModelInterface|null
     */
    protected $obj;

    /**
     * The form group's storage medium.
     *
     * @var array|PropertyInterface|\Charcoal\Source\SourceInterface|null
     */
    protected $storage;

    /**
     * The form group's storage target. {@deprecated In favor of $storage.}
     *
     * @var ModelStructureProperty|null
     */
    protected $storageProperty;

    /**
     * The form group the input belongs to.
     *
     * @var FormGroupInterface|null
     */
    private $formGroup;

    /**
     * Whether the form is ready.
     *
     * @var boolean
     */
    protected $isStructureFinalized = false;

    /**
     * Whether to automatically use all available properties
     * if a form group is not defined.
     *
     * @var boolean
     */
    protected $autoFormGroup;

    /**
     * The form group's raw data.
     *
     * @var array|null
     */
    protected $rawData;

    /**
     * @return string
     */
    public function type()
    {
        return 'charcoal/admin/widget/form-group/structure';
    }

    /**
     * @param  string $structId The structure entry identifier.
     * @return self
     */
    public function setStructId($structId)
    {
        $this->structId = $structId;
        return $this;
    }

    /**
     * @return string
     */
    public function structId()
    {
        if (!$this->structId) {
            $this->structId = uniqid();
        }

        return $this->structId;
    }

    /**
     * @param  array $data Widget data.
     * @return self
     */
    public function setData(array $data)
    {
        if ($this->rawData === null) {
            $this->rawData = $data;
        }

        parent::setData($data);

        return $this;
    }

    /**
     * Determine if the header is to be displayed.
     *
     * @return boolean If TRUE or unset, check if there is a title.
     */
    public function showHeader()
    {
        if ($this->display() === self::SEAMLESS_STRUCT_DISPLAY) {
            return false;
        } else {
            return parent::showHeader();
        }
    }

    /**
     * Determine if the footer is to be displayed.
     *
     * @return boolean If TRUE or unset, check if there are notes.
     */
    public function showFooter()
    {
        if ($this->display() === self::SEAMLESS_STRUCT_DISPLAY) {
            return false;
        } else {
            return parent::showFooter();
        }
    }

    /**
     * Retrieve the UI item's template.
     *
     * This method updates the dynamic template "structure_template".
     *
     * @return string If unset, returns the UI item type.
     */
    public function template()
    {
        $this->setDynamicTemplate('structure_template', $this->displayTemplate());

        return parent::template();
    }

    /**
     * Retrieve the property's display layout template.
     *
     * @return string|null
     */
    public function displayTemplate()
    {
        $display = $this->display();

        return 'charcoal/admin/widget/form-group/structure/container-' . $display;
    }

    /**
     * Retrieve the form's object.
     *
     * @throws RuntimeException If the form doesn't have a model.
     * @return \Charcoal\Model\ModelInterface
     */
    public function obj()
    {
        if ($this->obj === null) {
            $formGroup = $this->formGroup();
            if ($formGroup instanceof self) {
                $prop = $formGroup->storageProperty();
                $val  = $formGroup->obj()->propertyValue($prop->ident());

                $this->obj = $prop->structureVal($val, [ 'default_data' => true ]);
                if ($this->obj === null) {
                    $this->obj = clone $prop->structureProto();
                }
            } elseif ($this->form() instanceof ObjectContainerInterface) {
                $this->obj = $this->form()->obj();
            }

            if ($this->obj === null) {
                throw new RuntimeException(sprintf(
                    'The [%1$s] widget has no data model.',
                    static::class
                ));
            }
        }

        return $this->obj;
    }

    /**
     * Set the form input's parent group.
     *
     * @param  FormGroupInterface $formGroup The parent form group object.
     * @return self
     */
    public function setFormGroup(FormGroupInterface $formGroup)
    {
        $this->formGroup = $formGroup;

        return $this;
    }

    /**
     * Retrieve the input's parent group.
     *
     * @return FormGroupInterface|null
     */
    public function formGroup()
    {
        return $this->formGroup;
    }

    /**
     * Clear the group's parent group.
     *
     * @return self
     */
    public function clearFormGroup()
    {
        $this->formGroup = null;

        return $this;
    }

    /**
     * Set the form group's storage target.
     *
     * Must be a property of the form's object model that will receive an associative array
     * of the form group's data.
     *
     * @param  string|ModelStructureProperty $propertyIdent The property identifier—or instance—of a storage property.
     * @throws InvalidArgumentException If the property identifier is not a string.
     * @throws UnexpectedValueException If a property is invalid.
     * @return self
     */
    public function setStorageProperty($propertyIdent)
    {
        $property = null;
        if ($propertyIdent instanceof PropertyInterface) {
            $property      = $propertyIdent;
            $propertyIdent = $property->ident();
        } elseif (!is_string($propertyIdent)) {
            throw new InvalidArgumentException(
                'Storage Property identifier must be a string'
            );
        }

        $obj = $this->obj();
        if (!$obj->hasProperty($propertyIdent)) {
            throw new UnexpectedValueException(sprintf(
                'The "%1$s" property is not defined on [%2$s]',
                $propertyIdent,
                get_class($obj)
            ));
        }

        if ($property === null) {
            $property = $obj->property($propertyIdent);
        }

        if ($this->form() && $this->form()->obj() === $obj) {
            $this->form()->addFormProperty($propertyIdent, []);
        }

        if ($property instanceof ModelStructureProperty) {
            $this->storageProperty = $property;
        } else {
            throw new UnexpectedValueException(sprintf(
                '"%s" [%s] is not a model structure property on [%s].',
                $propertyIdent,
                (is_object($property) ? get_class($property) : gettype($property)),
                (is_object($obj) ? get_class($obj) : gettype($obj))
            ));
        }

        return $this;
    }

    /**
     * Retrieve the form group's storage property master.
     *
     * @throws RuntimeException If the storage property was not previously set.
     * @return ModelStructureProperty
     */
    public function storageProperty()
    {
        if ($this->storageProperty === null) {
            throw new RuntimeException(sprintf(
                'Storage property owner is not defined for "%s"',
                get_class($this)
            ));
        }

        return $this->storageProperty;
    }

    /**
     * Retrieve the properties from the storage property's structure.
     *
     * @return array
     */
    public function structProperties()
    {
        $property = $this->storageProperty();

        if ($property) {
            $struct = $property->getStructureMetadata();

            if (isset($struct['properties'])) {
                return $struct['properties'];
            }
        }

        return [];
    }

    /**
     * Determine if the form group should use all available properties.
     *
     * @return boolean
     */
    protected function autoFormGroup()
    {
        if ($this->autoFormGroup === null) {
            $property = $this->storageProperty();
            $struct   = $property->getStructureMetadata();

            if (isset($struct['admin']['auto_form_group'])) {
                $this->autoFormGroup = $struct['admin']['auto_form_group'];
            } else {
                $this->autoFormGroup = true;
            }
        }

        return $this->autoFormGroup;
    }

    /**
     * Finalize the form group's properies, entries, and layout.
     *
     * @param  boolean $reload Rebuild the form group's structure.
     * @return void
     */
    protected function finalizeStructure($reload = false)
    {
        if ($reload || !$this->isStructureFinalized) {
            $this->isStructureFinalized = true;

            $formGroup = $this->findStructureFormGroup();
            if ($formGroup) {
                if (is_array($this->rawData)) {
                    $widgetData = array_replace($formGroup, $this->rawData);
                    $this->setData($widgetData);
                } else {
                    $this->setData($formGroup);
                }
            }
        }
    }

    /**
     * @return ?array<string, mixed>
     */
    protected function findStructureFormGroup(): ?array
    {
        $struct = $this->storageProperty()->getStructureMetadata();

        $formGroup = null;
        if (isset($struct['admin']['form_group'])) {
            if (\is_string($struct['admin']['form_group'])) {
                $groupName = $struct['admin']['form_group'];
                if (isset($struct['admin']['form_groups'][$groupName])) {
                    return $struct['admin']['form_groups'][$groupName];
                }
            } else {
                return $struct['admin']['form_group'];
            }
        }

        if (isset($struct['admin']['default_form_group'])) {
            $groupName = $struct['admin']['default_form_group'];
            if (
                \is_string($groupName) &&
                isset($struct['admin']['form_groups'][$groupName])
            ) {
                return $struct['admin']['form_groups'][$groupName];
            }
        }

        return null;
    }

    /**
     * Parse the form group and model properties.
     *
     * @return array
     */
    protected function parsedFormProperties()
    {
        if ($this->parsedFormProperties === null) {
            $this->finalizeStructure();

            $groupProperties     = $this->groupProperties();
            $availableProperties = $this->structProperties();

            $structProperties = [];
            if (!empty($groupProperties)) {
                foreach ($groupProperties as $propertyIdent => $propertyMetadata) {
                    if (is_string($propertyMetadata)) {
                        $propertyIdent    = $propertyMetadata;
                        $propertyMetadata = null;
                    }

                    $propertyIdent = $this->camelize($propertyIdent);

                    if (!isset($availableProperties[$propertyIdent])) {
                        continue;
                    }

                    if (is_array($propertyMetadata)) {
                        $propertyMetadata = array_merge($propertyMetadata, $availableProperties[$propertyIdent]);
                    } else {
                        $propertyMetadata = $availableProperties[$propertyIdent];
                    }

                    $structProperties[$propertyIdent] = $propertyMetadata;
                }
            }

            if (empty($structProperties) && $this->autoFormGroup() === true) {
                $structProperties = $availableProperties;
            }

            $this->parsedFormProperties = $structProperties;
        }

        return $this->parsedFormProperties;
    }

    /**
     * Retrieve the object's properties from the form.
     *
     * @todo   Add support to StructureProperty and StructureFormGroup for multiple-values:
     *         `($store->multiple() ? '%1$s['.uniqid().'][%2$s]' : '%1$s[%2$s]' )`.
     * @throws UnexpectedValueException If a property data is invalid.
     * @return \Charcoal\Admin\Widget\FormPropertyWidget[]|\Generator
     */
    public function formProperties()
    {
        if ($this instanceof ConditionalizableInterface) {
            if ($this->condition() !== null && !$this->resolvedCondition()) {
                return [];
            }
        }

        $this->finalizeStructure();

        $store = $this->storageProperty();
        $form  = $this->form();
        $obj   = $this->obj();
        $entry = $obj[$store->ident()];

        if (is_string($entry)) {
            $entry = $store->parseVal($entry);
        }

        $entry = $store->structureVal($entry, [ 'default_data' => true ]);
        if (!$entry) {
            $entry = clone $store->structureProto();
        }

        $propertyIdentPattern = '%1$s[%2$s]';

        $propPreferences  = $this->propertiesOptions();
        $structProperties = $this->parsedFormProperties();

        if (!$this->layout()) {
            // Ensure a layout is present and matches the number
            // of properties to be rendered.
            $this->setLayout([
                'structure' => [
                    [ 'columns' => [ 1 ], 'loop' => count($structProperties) ],
                ],
            ]);
        }

        foreach ($structProperties as $propertyIdent => $propertyMetadata) {
            if (is_array($propertyMetadata)) {
                $propertyMetadata['ident'] = $propertyIdent;
            }

            if (method_exists($entry, 'filterPropertyMetadata')) {
                $propertyMetadata = $entry->filterPropertyMetadata(
                    $propertyMetadata,
                    $propertyIdent
                );
            }

            if (method_exists($obj, 'filterPropertyMetadata')) {
                $propertyMetadata = $obj->filterPropertyMetadata(
                    $propertyMetadata,
                    $store->ident() . '.' . $propertyIdent
                );
            }

            if (is_bool($propertyMetadata) && $propertyMetadata === false) {
                continue;
            }

            if (!is_array($propertyMetadata)) {
                throw new UnexpectedValueException(sprintf(
                    'Invalid property data for "%1$s", received %2$s',
                    $propertyIdent,
                    (is_object($propertyMetadata) ? get_class($propertyMetadata) : gettype($propertyMetadata))
                ));
            }

            if (isset($propertyMetadata['active']) && $propertyMetadata['active'] === false) {
                continue;
            }

            $subPropertyIdent = sprintf(
                $propertyIdentPattern,
                ($store['input_name'] ?: $store->ident()),
                $propertyIdent
            );
            $propertyMetadata['input_name'] = $subPropertyIdent;

            $formProperty = $form->createFormProperty();
            $formProperty->setViewController($this->viewController());
            $formProperty->setPropertyIdent($subPropertyIdent);
            $formProperty->setData($propertyMetadata);

            if (!empty($propPreferences[$propertyIdent])) {
                $propertyOptions = $propPreferences[$propertyIdent];

                if (is_array($propertyOptions)) {
                    $formProperty->merge($propertyOptions);
                }
            }

            if ($entry && isset($entry[$propertyIdent])) {
                $val = $entry[$propertyIdent];
                $formProperty->setPropertyVal($val);
            }

            if (!$formProperty->l10nMode()) {
                $formProperty->setL10nMode($this->l10nMode());
            }

            if ($formProperty instanceof FormInputInterface) {
                $formProperty->setFormGroup($this);
            }

            if ($formProperty->hidden()) {
                $form->addHiddenProperty($subPropertyIdent, $formProperty);
            } else {
                yield $propertyIdent => $formProperty;
            }

            if ($formProperty instanceof FormInputInterface) {
                $formProperty->clearFormGroup();
            }
        }
    }

    public function supportsLanguageSwitch(): bool
    {
        $groupProperties     = $this->groupProperties();
        $availableProperties = $this->structProperties();

        foreach ($groupProperties as $propertyIdent => $propertyMetadata) {
            if (is_string($propertyMetadata)) {
                $propertyIdent    = $propertyMetadata;
                $propertyMetadata = null;
            }

            $propertyIdent = $this->camelize($propertyIdent);

            if (isset($availableProperties[$propertyIdent])) {
                if (is_array($propertyMetadata)) {
                    $propertyMetadata = array_merge($propertyMetadata, $availableProperties[$propertyIdent]);
                } else {
                    $propertyMetadata = $availableProperties[$propertyIdent];
                }
            }

            if (isset($propertyMetadata['l10n']) && $propertyMetadata['l10n']) {
                return true;
            }
        }

        return false;
    }
}
