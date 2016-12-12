<?php

namespace Charcoal\Admin\Widget\FormGroup;

use \RuntimeException;
use \UnexpectedValueException;
use \InvalidArgumentException;

// From 'charcoal-property'
use \Charcoal\Property\PropertyInterface;

// From 'charcoal-ui'
use \Charcoal\Ui\FormGroup\AbstractFormGroup;

// From 'charcoal-admin'
use \Charcoal\Admin\Widget\FormGroupWidget;
use \Charcoal\Admin\Ui\ObjectContainerInterface;

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
class StructureFormGroup extends FormGroupWidget
{
    /**
     * The form group's storage medium.
     *
     * @var array|PropertyInterface|SourceInterface|null
     */
    protected $storage;

    /**
     * The form group's storage target. {@deprecated In favor of $storage.}
     *
     * @var PropertyInterface|null
     */
    protected $storageProperty;

    /**
     * Whether the form is ready.
     *
     * @var boolean
     */
    protected $isStructureFinalized = false;

    /**
     * Retrieve the form's object.
     *
     * @throws RuntimeException If the form doesn't have a model.
     * @return \Charcoal\Model\ModelInterface
     */
    public function obj()
    {
        if (!$this->form() instanceof ObjectContainerInterface) {
            throw new RuntimeException(
                sprintf('The form must implement %s', ObjectContainerInterface::class)
            );
        }

        return $this->form()->obj();
    }

    /**
     * Set the form group's storage target.
     *
     * Must be a property of the form's object model that will receive an associative array
     * of the form group's data.
     *
     * @param  string|PropertyInterface $propertyIdent The property identifier—or instance—of a storage property.
     * @throws InvalidArgumentException If the property identifier is not a string.
     * @throws UnexpectedValueException If a property data is invalid.
     * @return StructureFormGroup
     */
    public function setStorageProperty($propertyIdent)
    {
        $property = null;
        if ($propertyIdent instanceof PropertyInterface) {
            $property      = $propertyIdent;
            $propertyIdent = $property->ident();
        } elseif (!is_string($propertyIdent)) {
            throw new InvalidArgumentException(
                'Property identifier must be a string'
            );
        }

        $obj = $this->obj();
        if (!$obj->hasProperty($propertyIdent)) {
            throw new UnexpectedValueException(
                sprintf(
                    'The "%1$s" property is not defined on [%2$s]',
                    $propertyIdent,
                    get_class($this->obj())
                )
            );
        }

        if ($property === null) {
            $property = $obj->property($propertyIdent);
        }

        $this->storageProperty = $property;

        return $this;
    }

    /**
     * Retrieve the form group's storage property master.
     *
     * @throws RuntimeException If the storage property was not previously set.
     * @return PropertyInterface
     */
    public function storageProperty()
    {
        if ($this->storageProperty === null) {
            throw new RuntimeException(
                sprintf('Storage property owner is not defined for "%s"', get_class($this))
            );
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
            $struct = $property->structureMetadata();

            if (isset($struct['properties'])) {
                return $struct['properties'];
            }
        }

        return [];
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

            $property = $this->storageProperty();

            if ($property) {
                $struct = $property->structureMetadata();
                $formGroup = null;
                if (isset($struct['admin']['default_form_group'])) {
                    $groupName = $struct['admin']['default_form_group'];
                    if (isset($struct['admin']['form_groups'][$groupName])) {
                        $formGroup = $struct['admin']['form_groups'][$groupName];
                    }
                } elseif (isset($struct['admin']['form_group'])) {
                    $formGroup = $struct['admin']['form_group'];
                }

                if ($formGroup) {
                    $widgetData = array_replace($formGroup, $this->data());
                    $this->setData($widgetData);
                }
            }
        }
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

            $groupProperties  = $this->groupProperties();
            $structProperties = $this->structProperties();

            if ($groupProperties) {
                if (is_string(key($groupProperties))) {
                    $structProperties = $groupProperties;
                } else {
                    $structProperties = array_merge(array_flip($groupProperties), $structProperties);
                }
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
        $this->finalizeStructure();

        $store = $this->storageProperty();
        $form  = $this->form();
        $obj   = $this->obj();
        $entry = $obj[$store->ident()];

        if (is_string($entry)) {
            $entry = $store->parseVal($entry);
        }

        $propertyIdentPattern = '%1$s[%2$s]';

        $propPreferences  = $this->propertiesOptions();
        $structProperties = $this->parsedFormProperties();

        foreach ($structProperties as $propertyIdent => $propertyMetadata) {
            if (method_exists($obj, 'filterPropertyMetadata')) {
                $propertyMetadata = $obj->filterPropertyMetadata($propertyMetadata, $propertyIdent);
            }

            if (is_bool($propertyMetadata) && $propertyMetadata === false) {
                continue;
            }

            if (!is_array($propertyMetadata)) {
                throw new UnexpectedValueException(
                    sprintf(
                        'Invalid property data for "%1$s", received %2$s',
                        $propertyIdent,
                        (is_object($propertyMetadata) ? get_class($propertyMetadata) : gettype($propertyMetadata))
                    )
                );
            }

            if (isset($propertyMetadata['active']) && $propertyMetadata['active'] === false) {
                continue;
            }

            $subPropertyIdent = sprintf($propertyIdentPattern, $store->ident(), $propertyIdent);

            $formProperty = $form->createFormProperty();
            $formProperty->setViewController($this->viewController());
            $formProperty->setPropertyIdent($subPropertyIdent);
            $formProperty->setData($propertyMetadata);

            if (!empty($propPreferences[$propertyIdent])) {
                $propertyOptions = $propPreferences[$propertyIdent];

                if (is_array($propertyOptions)) {
                    $formProperty->setData($propertyOptions);
                }
            }

            if (!empty($entry)) {
                $val = $entry[$propertyIdent];
                $formProperty->setPropertyVal($val);
            }

            if (!$formProperty->l10nMode()) {
                $formProperty->setL10nMode($this->l10nMode());
            }

            yield $propertyIdent => $formProperty;
        }
    }
}
