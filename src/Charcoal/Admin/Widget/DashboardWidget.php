<?php

namespace Charcoal\Admin\Widget;

// From Pimple
use Pimple\Container;
// From 'charcoal-ui'
use Charcoal\Ui\Dashboard\DashboardInterface;
use Charcoal\Ui\Dashboard\DashboardTrait;
use Charcoal\Ui\Layout\LayoutAwareInterface;
use Charcoal\Ui\Layout\LayoutAwareTrait;
use Charcoal\Ui\UiItemTrait;
use Charcoal\Ui\UiItemInterface;
// From 'charcoal-admin'
use Charcoal\Admin\AdminWidget;

/**
 * The dashboard widget is a simple dashboard interface / layout aware object.
 */
class DashboardWidget extends AdminWidget implements
    DashboardInterface
{
    use DashboardTrait;
    use LayoutAwareTrait;
    use UiItemTrait;

    /**
     * @param Container $container The DI container.
     * @return void
     */
    protected function setDependencies(Container $container)
    {
        parent::setDependencies($container);

        // Satisfies DashboardInterface dependencies
        $this->setWidgetBuilder($container['widget/builder']);

        // Satisfies LayoutAwareInterface dependencies
        $this->setLayoutBuilder($container['layout/builder']);
    }
}
