<?php

namespace Charcoal\Admin\Action\Widget\Table;

use \Exception;

// PSR-7 (http messaging) dependencies
use \Psr\Http\Message\RequestInterface;
use \Psr\Http\Message\ResponseInterface;

use \Pimple\Container;

// Dependency from 'charcoal-factory'
use \Charcoal\Factory\FactoryInterface;

// Intra-module (`charcoal-admin`) dependencies
use \Charcoal\Admin\AdminAction;
use \Charcoal\Admin\Widget\ObjectFormWidget;
use \Charcoal\Admin\Widget\FormPropertyWidget;

/**
 * Inline action: Return the inline edit properties HTML from an object
 *
 * ## Required parameters
 * - `objType`
 * - `objId`
 */
class InlineAction extends AdminAction
{
    /**
     * @var array $inlineProperties
     */
    protected $inlineProperties;

    /**
     * @var FactoryInterface $widgetFactory
     */
    private $widgetFactory;

    /**
     * @param Container $container DI container.
     * @return void
     */
    public function setDependencies(Container $container)
    {
        parent::setDependencies($container);

        // Required ObjectContainerInterface dependencies
        $this->setWidgetFactory($container['widget/factory']);
    }

    /**
     * @param FactoryInterface $factory The widget factory, to create the dashboard and sidemenu widgets.
     * @return InlineAction Chainable
     */
    protected function setWidgetFactory(FactoryInterface $factory)
    {
        $this->widgetFactory = $factory;
        return $this;
    }

    /**
     * @throws Exception If the factory is not set.
     * @return FactoryInterface
     */
    protected function widgetFactory()
    {
        if ($this->widgetFactory === null) {
            throw new Exception(
                'Widget factory is not set on inline action widget.'
            );
        }
        return $this->widgetFactory;
    }
    /**
     * @param RequestInterface  $request  A PSR-7 compatible Request instance.
     * @param ResponseInterface $response A PSR-7 compatible Response instance.
     * @return ResponseInterface
     */
    public function run(RequestInterface $request, ResponseInterface $response)
    {
        $objType = $request->getParam('obj_type');
        $objId = $request->getParam('obj_id');

        if (!$objType || !$objId) {
            $this->setSuccess(false);
            return $response->withStatus(404);
        }

        try {
            $obj = $this->modelFactory()->create($objType);
            $obj->load($objId);
            if (!$obj->id()) {
                $this->setSuccess(false);
                return $response->withStatus(404);
            }

            $objForm = $this->widgetFactory()->create(ObjectFormWidget::class);

            $objForm->setObjType($objType);
            $objForm->setObjId($objId);
            $formProperties = $objForm->formProperties();
            foreach ($formProperties as $propertyIdent => $property) {
                // Safeguard type
                if (!($property instanceof FormPropertyWidget)) {
                    continue;
                }

                $p = $obj->p($propertyIdent);
                $property->setPropertyVal($p->val());
                $property->setProp($p);
                $inputType = $property->inputType();
                $this->inlineProperties[$propertyIdent] = $property->renderTemplate($inputType);
            }
            $this->setSuccess(true);
            return $response;
        } catch (Exception $e) {
            $this->setSuccess(false);
            return $response->withStatus(404);
        }
    }

    /**
     * @return array
     */
    public function results()
    {
        return [
            'success'           => $this->success(),
            'inline_properties' => $this->inlineProperties,
            'feedbacks'         => $this->feedbacks()
        ];
    }
}