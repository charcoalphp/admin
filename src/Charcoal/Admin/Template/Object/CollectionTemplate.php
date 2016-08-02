<?php

namespace Charcoal\Admin\Template\Object;

// Dependencies from `PHP`
use \Exception;
use \InvalidArgumentException;

// Dependencies from `pimple`
use \Pimple\Container;

// Dependencies from `charcoal-translation`
use \Charcoal\Translation\TranslationString;

// From `charcoal-factory`
use \Charcoal\Factory\FactoryInterface;

// From `charcoal-ui`
use \Charcoal\Ui\DashboardBuilder;

// Intra-module (`charcoal-admin`) dependencies
use \Charcoal\Admin\AdminTemplate;
use \Charcoal\Admin\Ui\CollectionContainerInterface;
use \Charcoal\Admin\Ui\CollectionContainerTrait;
use \Charcoal\Admin\Ui\DashboardContainerInterface;
use \Charcoal\Admin\Ui\DashboardContainerTrait;

/**
 * Object collection template (table with a list of objects).
 */
class CollectionTemplate extends AdminTemplate implements
    CollectionContainerInterface,
    DashboardContainerInterface
{
    use CollectionContainerTrait;
    use DashboardContainerTrait;

    /**
     * @var SidemenuWidgetInterface $sidemenu
     */
    private $sidemenu;

    /**
     * @var FactoryInterface $widgetFactory
     */
    private $widgetFactory;

    /**
     * @var DashboardBuilder $dashboardBuilder
     */
    private $dashboardBuilder;

    /**
     * @param Container $container DI Container.
     * @return void
     */
    public function setDependencies(Container $container)
    {
        parent::setDependencies($container);

        $this->setModelFactory($container['model/factory']);
        $this->setCollectionLoader($container['model/collection/loader']);

        // Required dependencies.
        $this->setWidgetFactory($container['widget/factory']);
        $this->dashboardBuilder = $container['dashboard/builder'];
    }

    /**
     * @return void
     */
    public function createObjTable()
    {
        $obj = $this->proto();
        if (!$obj) {
            return;
        }
        if ($obj->source()->tableExists() === false) {
            $obj->source()->createTable();
            $this->addFeedback('success', 'A new table was created for object.');
        }
    }

    /**
     * @param FactoryInterface $factory The widget factory, to create the dashboard and sidemenu widgets.
     * @return CollectionTemplate Chainable
     */
    protected function setWidgetFactory(FactoryInterface $factory)
    {
        $this->widgetFactory = $factory;
        return $this;
    }

    /**
     * Safe Widget Factory getter.
     * Create the widget factory if it was not preiously injected / set.
     *
     * @throws Exception If the widget factory dependency was not previously set / injected.
     * @return FactoryInterface
     */
    protected function widgetFactory()
    {
        if ($this->widgetFactory === null) {
            throw new Exception(
                'Widget factory was not set.'
            );
        }
        return $this->widgetFactory;
    }

    /**
     * @param DashboardBuilder $builder The builder, to create customized dashboard objects.
     * @return CollectionTemplate Chainable
     *
     */
    public function setDashboardBuilder(DashboardBuilder $builder)
    {
        $this->dashboardBuilder = $builder;
        return $this;
    }

    /**
     * @throws Exception If the dashboard builder dependency was not previously set / injected.
     * @return DashboardBuilder
     */
    public function dashboardBuilder()
    {
        if ($this->dashboardBuilder === null) {
            throw new Exception(
                'Dashboard builder was not set.'
            );
        }
        return $this->dashboardBuilder;
    }

    /**
     * @param array $data Optional Dashboard data.
     * @return Dashboard
     * @see DashboardContainerTrait::createDashboard()
     */
    public function createDashboard(array $data = null)
    {
        unset($data);
        $dashboardConfig = $this->objCollectionDashboardConfig();
        $dashboard = $this->dashboardBuilder->build($dashboardConfig);
        return $dashboard;
    }

    /**
     * @return SidemenuWidgetInterface
     */
    public function sidemenu()
    {
        $dashboardConfig = $this->objCollectionDashboardConfig();
        ;
        if (!isset($dashboardConfig['sidemenu'])) {
            return null;
        }

        $sidemenuConfig = $dashboardConfig['sidemenu'];

        $GLOBALS['widget_template'] = 'charcoal/admin/widget/sidemenu';
        if (isset($sidemenuConfig['widget_type'])) {
            $widgetType = $sidemenuConfig['widget_type'];
        } else {
            $widgetType = 'charcoal/admin/widget/sidemenu';
        }
        $sidemenu = $this->widgetFactory()->create($widgetType);

        if (isset($sidemenuConfig['widget_options'])) {
            $sidemenu->setData($sidemenuConfig['widget_options']);
        }
        return $sidemenu;
    }

    /**
     * Sets the search widget accodingly
     * Uses the "default_search_list" ident that should point
     * on ident in the "lists"
     *
     * @see charcoal/admin/widget/search
     * @return widget
     */
    public function searchWidget()
    {
        $widget = $this->widgetFactory()->create('charcoal/admin/widget/search');
        $widget->setObjType($this->objType());

        $obj = $this->proto();
        $metadata = $obj->metadata();

        $adminMetadata = $metadata['admin'];
        $lists = $adminMetadata['lists'];

        $listIdent = ( isset($adminMetadata['default_search_list']) ) ? $adminMetadata['default_search_list'] : '';

        if (!$listIdent) {
            $listIdent = ( isset($adminMetadata['default_list']) ) ? $adminMetadata['default_list'] : '';
        }

        if (!$listIdent) {
            $listIdent = 'default';
        }

        // Note that if the ident doesn't match a list,
        // it will return basicly every properties of the object
        $widget->setCollectionIdent($listIdent);
        return $widget;
    }

    /**
     * @throws Exception If the dashboard config can not be loaded.
     * @return array
     */
    private function objCollectionDashboardConfig()
    {
        $obj = $this->proto();

        $objMetadata     = $obj->metadata();
        $dashboardIdent  = $this->dashboardIdent();
        $dashboardConfig = $this->dashboardConfig();

        $adminMetadata = isset($objMetadata['admin']) ? $objMetadata['admin'] : null;
        if ($adminMetadata === null) {
            throw new Exception(
                sprintf(
                    'No dashboard for %s (no admin metadata).',
                    $obj->type()
                )
            );
        }

        if ($dashboardIdent === false || $dashboardIdent === null || $dashboardIdent === '') {
            $dashboardIdent = filter_input(INPUT_GET, 'dashboard_ident', FILTER_SANITIZE_STRING);
        }

        if ($dashboardIdent === false || $dashboardIdent === null || $dashboardIdent === '') {
            if (!isset($adminMetadata['default_collection_dashboard'])) {
                throw new Exception(
                    sprintf(
                        'No default collection dashboard defined in admin metadata for %s.',
                        $obj->type()
                    )
                );
            }

            $dashboardIdent = $adminMetadata['default_collection_dashboard'];
        }

        if (empty($dashboardConfig)) {
            if (!isset($adminMetadata['dashboards']) || !isset($adminMetadata['dashboards'][$dashboardIdent])) {
                throw new Exception(
                    'Dashboard config is not defined.'
                );
            }

            $dashboardConfig = $adminMetadata['dashboards'][$dashboardIdent];
        }

        return $dashboardConfig;
    }

    /**
     * @return string|TranslationString
     */
    public function title()
    {
        $config = $this->objCollectionDashboardConfig();

        if (isset($config['title'])) {
            return new TranslationString($config['title']);
        } else {
            $obj      = $this->proto();
            $objLabel = $this->objType();
            $metadata = $obj->metadata();

            if (isset($metadata['admin'])) {
                $metadata    = $metadata['admin'];
                $formIdent   = ( isset($metadata['default_list']) ? $metadata['default_list'] : '' );
                $objFormData = ( isset($metadata['lists'][$formIdent]) ? $metadata['lists'][$formIdent] : [] );

                if (isset($objFormData['label']) && TranslationString::isTranslatable($objFormData['label'])) {
                    $objLabel = new TranslationString($objFormData['label']);
                }
            }

            $this->title = sprintf('List: %s', $objLabel);
        }

        return $this->title;
    }
}
