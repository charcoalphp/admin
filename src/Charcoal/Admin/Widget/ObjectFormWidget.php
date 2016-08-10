<?php

namespace Charcoal\Admin\Widget;

use \InvalidArgumentException;
use \Exception;

use \Pimple\Container;

use \Charcoal\Admin\Widget\FormWidget;
use \Charcoal\Admin\Widget\FormPropertyWidget;

use \Charcoal\Admin\Ui\ObjectContainerInterface;
use \Charcoal\Admin\Ui\ObjectContainerTrait;

/**
 *
 */
class ObjectFormWidget extends FormWidget implements ObjectContainerInterface
{
    use ObjectContainerTrait;

    /**
     * @var string
     */
    protected $formIdent;

    /**
     * @var string
     */
    protected $groupDisplayMode;

    /**
     * @var array
     */
    protected $formData;

    /**
     * @param Container $container The DI container.
     * @return void
     */
    public function setDependencies(Container $container)
    {
        parent::setDependencies($container);

        // Fill ObjectContainerInterface dependencies
        $this->setModelFactory($container['model/factory']);
    }

    /**
     * @return string
     */
    public function widgetType()
    {
        return 'charcoal/admin/widget/objectForm';
    }

    /**
     * @param array|ArrayInterface $data The widget data.
     * @return ObjectForm Chainable
     */
    public function setData($data)
    {
        parent::setData($data);

        $fromRequest = $this->dataFromRequest();
        if ($fromRequest) {
            parent::setData($fromRequest);
        }

        $fromModel = $this->dataFromObject();
        if ($fromModel) {
            $data = array_merge_recursive($fromModel, $data);
            parent::setData($data);
        }

        return $this;
    }

    /**
     * Fetch metadata from the current request.
     *
     *
     * @return array
     */
    public function dataFromRequest()
    {
        return array_intersect_key($_GET, array_flip($this->acceptedRequestData()));
    }

    /**
     * Retrieve the accepted metadata from the current request.
     *
     * @return array
     */
    public function acceptedRequestData()
    {
        return [ 'obj_type','obj_id', 'template', 'form_data', 'next_url', 'l10n_mode', 'group_display_mode' ];
    }

    /**
     * Fetch metadata from the current object type.
     *
     * @return array
     */
    public function dataFromObject()
    {
        $objMetadata   = $this->obj()->metadata();
        $adminMetadata = (isset($objMetadata['admin']) ? $objMetadata['admin'] : null);
        $formIdent     = $this->formIdent();
        if (!$formIdent) {
            $formIdent = (isset($adminMetadata['default_form']) ? $adminMetadata['default_form'] : '');
        }

        $objFormData = (isset($adminMetadata['forms'][$formIdent]) ? $adminMetadata['forms'][$formIdent] : []);

        if (isset($objFormData['groups']) && isset($adminMetadata['form_groups'])) {
            $extraFormGroups = array_intersect(
                array_keys($adminMetadata['form_groups']),
                array_keys($objFormData['groups'])
            );
            foreach ($extraFormGroups as $groupIdent) {
                $objFormData['groups'][$groupIdent] = array_merge(
                    $adminMetadata['form_groups'][$groupIdent],
                    $objFormData['groups'][$groupIdent]
                );
            }
        }

        return $objFormData;
    }

    /**
     * @param string $formIdent The form ident.
     * @throws InvalidArgumentException If the argument is not a string.
     * @return ObjectForm Chainable
     */
    public function setFormIdent($formIdent)
    {
        if (!is_string($formIdent)) {
            throw new InvalidArgumentException(
                'Form ident must be a string'
            );
        }
        $this->formIdent = $formIdent;
        return $this;
    }

    /**
     * @return string
     */
    public function formIdent()
    {
        return $this->formIdent;
    }

     /**
      * @param string $url The next URL.
      * @throws InvalidArgumentException If argument is not a string.
      * @return ActionInterface Chainable
      */
    public function setNextUrl($url)
    {
        if (!is_string($url)) {
            throw new InvalidArgumentException(
                'URL needs to be a string'
            );
        }

        if (!$this->obj()) {
            $this->nextUrl = $url;
            return $this;
        }

        $this->nextUrl = $this->obj()->render($url);
        return $this;
    }

    /**
     * Form action (target URL)
     *
     * @return string Relative URL
     */
    public function action()
    {
        $action = parent::action();
        if (!$action) {
            $obj = $this->obj();
            $objId = $obj->id();
            if ($objId) {
                return 'action/object/update';
            } else {
                return 'action/object/save';
            }
        } else {
            return $action;
        }
    }



    /**
     * Group display mode. Could be "tab" or nothing.
     * @param string $mode Group display mode.
     * @return ObjectFormWidget Chainable.
     */
    public function setGroupDisplayMode($mode)
    {
        $this->groupDisplayMode = $mode;
        return $this;
    }

    /**
     * Group display mode.
     * @return string Group display mode.
     */
    public function groupDisplayMode()
    {
        return $this->groupDisplayMode;
    }

    /**
     * Used in mustache templates to define if we're in
     * tab display mode or not.
     * @return boolean Tab display mode or not.
     */
    public function isTab()
    {
        return ( $this->groupDisplayMode() === 'tab' );
    }

    /**
     * Retrieve the object's properties as form controls.
     *
     * @param array $group An optional group to use.
     * @throws Exception If a property data is invalid.
     * @return FormPropertyWidget[]|Generator
     */
    public function formProperties(array $group = null)
    {
        $obj   = $this->obj();
        $props = $obj->metadata()->properties();

        // We need to sort form properties by form group property order if a group exists
        if (!empty($group)) {
            $props = array_merge(array_flip($group), $props);
        }

        foreach ($props as $propertyIdent => $property) {
            if (!is_array($property)) {
                throw new Exception(
                    sprintf(
                        'Invalid property data for "%1$s", received %2$s',
                        $propertyIdent,
                        (is_object($property) ? get_class($property) : gettype($property))
                    )
                );
            }

            $p = $this->widgetFactory()->create('charcoal/admin/widget/form-property');
            $p->setViewController($this->viewController());
            $p->setPropertyIdent($propertyIdent);
            $p->setData($property);

            yield $propertyIdent => $p;
        }
    }

    /**
     * Retrieve an object property as a form control.
     *
     * @param string $propertyIdent An optional group to use.
     * @throws InvalidArgumentException If the property identifier is not a string.
     * @throws Exception If a property data is invalid.
     * @return FormPropertyWidget
     */
    public function formProperty($propertyIdent)
    {
        if (!is_string($propertyIdent)) {
            throw new InvalidArgumentException(
                'Property ident must be a string'
            );
        }

        $obj = $this->obj();
        $property = $obj->metadata()->property($propertyIdent);

        if (!is_array($property)) {
            throw new Exception(
                sprintf(
                    'Invalid property data for "%1$s", received %2$s',
                    $propertyIdent,
                    (is_object($property) ? get_class($property) : gettype($property))
                )
            );
        }

        $p = $this->widgetFactory()->create('charcoal/admin/widget/form-property');
        $p->setViewController($this->viewController());
        $p->setPropertyIdent($propertyIdent);
        $p->setData($property);

        return $p;
    }

    /**
     * Not really a SETTER, but using the setter
     * to pass by the $_GET var (@see setData()).
     * @param array $data Data.
     * @return ObjectFormWidget Chainable.
     */
    public function setFormData(array $data)
    {
        $objData = $this->objData();
        $merged = array_replace_recursive($objData, $data);

        // Remove null values
        $merged = array_filter($merged, function ($val) {
            if ($val === null) {
                return false;
            }
            return true;
        });

        $this->formData = $merged;
        $this->obj()->setData($merged);
        return $this;
    }

    /**
     * Object data merged with whatever data were set
     * in the process.
     * This appears to be unused.
     * @return array Object data.
     */
    public function formData()
    {
        if (!$this->formData) {
            $this->formData = $this->objData();
        }
        return $this->formData;
    }

    /**
     * Object data.
     * @return array Object data.
     */
    public function objData()
    {
        return $this->obj()->data();
    }
}