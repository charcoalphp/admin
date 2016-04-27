<?php

namespace Charcoal\Admin\Widget;

use \InvalidArgumentException;

use \Pimple\Container;

// Dependencies from `charcoal-ui`
use \Charcoal\Ui\AbstractUiItem;
use \Charcoal\Ui\FormGroup\FormGroupInterface;
use \Charcoal\Ui\FormGroup\FormGroupTrait;
use \Charcoal\Ui\Layout\LayoutAwareInterface;
use \Charcoal\Ui\Layout\LayoutAwareTrait;

/**
 * Form Group Widget Controller
 */
class FormGroupWidget extends AbstractUiItem implements
    FormGroupInterface,
    LayoutAwareInterface
{
    use FormGroupTrait;
    use LayoutAwareTrait;

    /**
     * @var array $groupProperties
     */
    private $groupProperties = [];

    /**
     * @param array|\ArrayAccess $data Dependencies.
     */
    public function __construct($data)
    {
        $this->setForm($data['form']);
        $this->setFormInputBuilder($data['form_input_builder']);

        // Set up layout builder (to fulfill LayoutAware Interface)
        $this->setLayoutBuilder($data['layout_builder']);

    }

    /**
     * @param Container $container The DI container.
     * @return void
     */
    public function setDependencies(Container $container)
    {
        // Fill LayoutAwareInterface dependencies
        $this->setLayoutBuilder($container['layout/builder']);
    }

    /**
     * @param array|ArrayInterface $data Class data.
     * @return FormGroupWidget Chainable
     */
    public function setData($data)
    {
        parent::setData($data);

        if (isset($data['properties']) && $data['properties'] !== null) {
            $this->setGroupProperties($data['properties']);
        }

        return $this;
    }

    /**
     * @return string
     */
    public function type()
    {
        return 'charcoal/admin/widget/form-group-widget';
    }


    /**
     * @param array $properties The group properties.
     * @return FormGroupWidget Chainable
     */
    public function setGroupProperties(array $properties)
    {
        $this->groupProperties = $properties;
        return $this;
    }

    /**
     * @return array
     */
    public function groupProperties()
    {
        return $this->groupProperties;

    }

    /**
     * @return void This method is a generator.
     */
    public function formProperties()
    {
        $groupProperties = $this->groupProperties();
        $formProperties = $this->form()->formProperties($groupProperties);

        $ret = [];
        foreach ($formProperties as $property_ident => $property) {
            if (in_array($property_ident, $groupProperties)) {
                if (is_callable([$this->form(), 'obj'])) {
                    $obj = $this->form()->obj();
                    $val = $obj[$property_ident];
                    $property->setPropertyVal($val);
                }
                if (!$property->l10nMode()) {
                    $property->setL10nMode($this->l10nMode());
                }
                yield $property_ident => $property;
            }
        }
    }
}
