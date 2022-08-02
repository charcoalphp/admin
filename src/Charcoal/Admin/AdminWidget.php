<?php

namespace Charcoal\Admin;

use InvalidArgumentException;
// From 'pimple/pimple'
use Pimple\Container;
// From 'charcoal-factory'
use Charcoal\Factory\FactoryInterface;
// From 'charcoal-translator'
use Charcoal\Translator\Translation;
use Charcoal\Translator\TranslatorAwareTrait;
// From 'charcoal-user'
use Charcoal\User\AuthAwareInterface;
use Charcoal\User\AuthAwareTrait;
// From 'charcoal-ui'
use Charcoal\Ui\ConditionalizableInterface;
use Charcoal\Ui\ConditionalizableTrait;
use Charcoal\Ui\PrioritizableInterface;
use Charcoal\Ui\PrioritizableTrait;
// From 'charcoal-app'
use Charcoal\App\DebugAwareTrait;
use Charcoal\App\Template\AbstractWidget;
// From 'charcoal-admin'
use Charcoal\Admin\Support\AdminTrait;
use Charcoal\Admin\Support\BaseUrlTrait;

/**
 * The base Widget for the `admin` module.
 */
class AdminWidget extends AbstractWidget implements
    AuthAwareInterface,
    PrioritizableInterface,
    ConditionalizableInterface
{
    use AdminTrait;
    use AuthAwareTrait;
    use BaseUrlTrait;
    use DebugAwareTrait;
    use PrioritizableTrait;
    use ConditionalizableTrait;
    use TranslatorAwareTrait;

    public const DATA_SOURCE_REQUEST = 'request';
    public const DATA_SOURCE_OBJECT  = 'object';
    public const DATA_SOURCE_METADATA = 'metadata';

    /**
     * @var string $widgetId
     */
    public $widgetId;

    /**
     * @var string $type
     */
    private $type;

    /**
     * @var string $template
     */
    private $template;

    /**
     * @var string $ident
     */
    private $ident = '';

    /**
     * @var Translation|string|null $label
     */
    private $label;

    /**
     * @var string $lang
     */
    private $lang;

    /**
     * @var boolean $showLabel
     */
    private $showLabel;

    /**
     * @var boolean $showActions
     */
    private $showActions;

    /**
     * The widget's conditional logic.
     *
     * @var callable|string|null
     */
    private $activeCondition;

    /**
     * Keep track if data sources were merged.
     *
     * @var boolean
     */
    protected $mergedDataSources = false;

    /**
     * Extra data sources to merge when setting data on an entity.
     *
     * @var array
     */
    private $dataSources;

    /**
     * Associative array of source identifiers and options to apply when merging.
     *
     * @var array
     */
    private $dataSourceFilters = [];

    /**
     * @var FactoryInterface $modelFactory
     */
    private $modelFactory;

    /**
     * Enable / Disable the widget.
     *
     * Accepts, as a string, a callable or renderable condition.
     *
     * @param  mixed $active The active flag or condition.
     * @return self
     */
    public function setActive($active)
    {
        if (is_callable($active) || is_string($active)) {
            $condition = $active;
        } else {
            $condition = null;
        }

        $this->activeCondition = $condition;

        return parent::setActive($active);
    }

    /**
     * @return boolean
     */
    public function active()
    {
        if ($this->activeCondition !== null) {
            return $this->parseConditionalLogic($this->activeCondition);
        }

        return parent::active();
    }

    /**
     * @param string $template The UI item's template (identifier).
     * @throws InvalidArgumentException If the template identifier is not a string.
     * @return self
     */
    public function setTemplate($template)
    {
        if ($template === null) {
            $this->template = null;
            return $this;
        }

        if (!is_string($template)) {
            throw new InvalidArgumentException(
                'The admin widget template must be a string'
            );
        }

        $this->template = $template;

        return $this;
    }

    /**
     * @return string
     */
    public function template()
    {
        if ($this->template === null) {
            return $this->type();
        }

        return $this->template;
    }

    /**
     * @param string $widgetId The widget identifier.
     * @return self
     */
    public function setWidgetId($widgetId)
    {
        $this->widgetId = $widgetId;

        return $this;
    }

    /**
     * @return string
     */
    public function widgetId()
    {
        if (!$this->widgetId) {
            $this->widgetId = 'widget_' . uniqid();
        }

        return $this->widgetId;
    }

    /**
     * @param string $type The widget type.
     * @throws InvalidArgumentException If the argument is not a string.
     * @return self
     */
    public function setType($type)
    {
        if ($type === null) {
            $this->type = null;
            return $this;
        }

        if (!is_string($type)) {
            throw new InvalidArgumentException(
                'The admin widget type must be a string'
            );
        }

        $this->type = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function type()
    {
        return $this->type;
    }

    /**
     * @param string $ident The widget ident.
     * @throws InvalidArgumentException If the ident is not a string.
     * @return AdminWidget (Chainable)
     */
    public function setIdent($ident)
    {
        if ($ident === null) {
            $this->ident = null;
            return $this;
        }

        if (!is_string($ident)) {
            throw new InvalidArgumentException(
                'The admin widget identifier must be a string'
            );
        }

        $this->ident = $ident;

        return $this;
    }

    /**
     * @return string
     */
    public function ident()
    {
        return $this->ident;
    }

    /**
     * Set extra data sources to merge when setting data on an entity.
     *
     * @param mixed $sources One or more data source identifiers to merge data from.
     *     Pass NULL to reset the entity back to default sources.
     *     Pass FALSE, an empty string or array to disable extra sources.
     * @return self
     */
    public function setDataSources($sources)
    {
        if ($sources === null) {
            $this->dataSources = null;

            return $this;
        }

        if (!is_array($sources)) {
            $sources = [ $sources ];
        }

        foreach ($sources as $ident => $filter) {
            $this->addDataSources($ident, $filter);
        }

        return $this;
    }

    /**
     * Retrieve the extra data sources to merge when setting data on an entity.
     *
     * @return string[]
     */
    public function dataSources()
    {
        if ($this->dataSources === null) {
            return $this->defaultDataSources();
        }

        return $this->dataSources;
    }

    /**
     * Retrieve the callable filter for the given data source.
     *
     * @param string $sourceIdent A data source identifier.
     * @throws InvalidArgumentException If the data source is invalid.
     * @return callable|null Returns a callable variable.
     */
    public function dataSourceFilter($sourceIdent)
    {
        if (!is_string($sourceIdent)) {
            throw new InvalidArgumentException('Data source identifier must be a string');
        }

        $filters = array_merge($this->defaultDataSourceFilters(), $this->dataSourceFilters);

        if (isset($filters[$sourceIdent])) {
            return $filters[$sourceIdent];
        }

        return null;
    }

    /**
     * Retrieve the widget's data options for JavaScript components.
     *
     * @return array
     */
    public function widgetDataForJs()
    {
        return [];
    }

    /**
     * Converts the widget's {@see self::widgetDataForJs() options} as a JSON string.
     *
     * @return string Returns data serialized with {@see json_encode()}.
     */
    final public function widgetDataForJsAsJson()
    {
        $options = (JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($this->debug()) {
            $options = ($options | JSON_PRETTY_PRINT);
        }

        return json_encode($this->widgetDataForJs(), $options);
    }

    /**
     * Converts the widget's {@see self::widgetDataForJs() options} as a JSON string, protected from Mustache.
     *
     * @return string Returns a stringified JSON object, protected from Mustache rendering.
     */
    final public function escapedWidgetDataForJsAsJson()
    {
        return '{{=<% %>=}}' . $this->widgetDataForJsAsJson() . '<%={{ }}=%>';
    }

    /**
     * @param mixed $label The label.
     * @return self
     */
    public function setLabel($label)
    {
        $this->label = $this->translator()->translation($label);

        return $this;
    }

    /**
     * @return Translation|string|null
     */
    public function label()
    {
        return $this->label;
    }

    /**
     * @return array
     */
    public function actions()
    {
        return [];
    }

    /**
     * @param boolean $show The show actions flag.
     * @return self
     */
    public function setShowActions($show)
    {
        $this->showActions = !!$show;
        return $this;
    }

    /**
     * @return boolean
     */
    public function showActions()
    {
        if ($this->showActions !== false) {
            return (count($this->actions()) > 0);
        } else {
            return false;
        }
    }

    /**
     * @param boolean $show The show label flag.
     * @return self
     */
    public function setShowLabel($show)
    {
        $this->showLabel = !!$show;
        return $this;
    }

    /**
     * @return boolean
     */
    public function showLabel()
    {
        if ($this->showLabel !== false) {
            return !!strval($this->label());
        } else {
            return false;
        }
    }

    /**
     * Set common dependencies used in all admin widgets.
     *
     * @param  Container $container DI Container.
     * @return void
     */
    protected function setDependencies(Container $container)
    {
        parent::setDependencies($container);

        // Satisfies TranslatorAwareTrait dependencies
        $this->setTranslator($container['translator']);

        // Satisfies AuthAwareInterface dependencies
        $this->setAuthenticator($container['admin/authenticator']);
        $this->setAuthorizer($container['admin/authorizer']);

        // Satisfies AdminTrait dependencies
        $this->setDebug($container['debug']);
        $this->setAppConfig($container['config']);
        $this->setAdminConfig($container['admin/config']);

        // Satisfies BaseUrlTrait dependencies
        $this->setBaseUrl($container['base-url']);
        $this->setAdminUrl($container['admin/base-url']);

        // Satisfies AdminWidget dependencies
        $this->setModelFactory($container['model/factory']);
    }

    /**
     * @param FactoryInterface $factory The factory used to create models.
     * @return void
     */
    protected function setModelFactory(FactoryInterface $factory)
    {
        $this->modelFactory = $factory;
    }

    /**
     * @return FactoryInterface The model factory.
     */
    protected function modelFactory()
    {
        return $this->modelFactory;
    }

    /**
     * Parse the widget's conditional logic.
     *
     * @param  callable|string $condition The callable or renderable condition.
     * @return boolean
     */
    protected function resolveConditionalLogic($condition)
    {
        if (is_callable([ $this, $condition ])) {
            return !!$this->{$condition}();
        } elseif (is_callable($condition)) {
            return !!$condition();
        } elseif ($this->view()) {
            return !!$this->renderTemplate($condition);
        }

        return !!$condition;
    }

    /**
     * Set extra data sources to merge when setting data on an entity.
     *
     * @param mixed $sourceIdent  The data source identifier.
     * @param mixed $sourceFilter Optional filter to apply to the source's data.
     * @throws InvalidArgumentException If the data source is invalid.
     * @return self
     */
    protected function addDataSources($sourceIdent, $sourceFilter = null)
    {
        $validSources = $this->acceptedDataSources();

        if (is_numeric($sourceIdent) && is_string($sourceFilter)) {
            $sourceIdent   = $sourceFilter;
            $sourceFilter = null;
        }

        if (!is_string($sourceIdent)) {
            throw new InvalidArgumentException('Data source identifier must be a string');
        }

        if (!in_array($sourceIdent, $validSources)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Invalid data source. Must be one of %s',
                    implode(', ', $validSources)
                )
            );
        }

        if ($this->dataSources === null) {
            $this->dataSources = [];
        }

        $this->dataSources[] = $sourceIdent;
        $this->dataSourceFilters[$sourceIdent] = $this->resolveDataSourceFilter($sourceFilter);

        return $this;
    }

    /**
     * Retrieve the available data sources (when setting data on an entity).
     *
     * @return string[]
     */
    protected function acceptedDataSources()
    {
        return [ static::DATA_SOURCE_REQUEST, static::DATA_SOURCE_OBJECT, static::DATA_SOURCE_METADATA ];
    }

    /**
     * Retrieve the default data sources (when setting data on an entity).
     *
     * @return string[]
     */
    protected function defaultDataSources()
    {
        return [];
    }

    /**
     * Retrieve the default data source filters (when setting data on an entity).
     *
     * @return array
     */
    protected function defaultDataSourceFilters()
    {
        return [];
    }

    /**
     * Retrieve the default data source filters (when setting data on an entity).
     *
     * Note: Adapted from {@see \Slim\CallableResolver}.
     *
     * @link   https://github.com/slimphp/Slim/blob/3.x/Slim/CallableResolver.php
     * @param  mixed $toResolve A callable used when merging data.
     * @return callable|null
     */
    protected function resolveDataSourceFilter($toResolve)
    {
        if (is_callable($toResolve)) {
            return $toResolve;
        }

        $resolved = $toResolve;

        if (is_string($toResolve)) {
            // Check for Slim callable
            $callablePattern = '!^([^\:]+)\:([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)$!';
            if (preg_match($callablePattern, $toResolve, $matches)) {
                $class  = $matches[1];
                $method = $matches[2];

                if ($class === 'parent') {
                    $resolved = [ $this, $class . '::' . $method ];
                } else {
                    if (!class_exists($class)) {
                        return null;
                    }
                    $resolved = [ $class, $method ];
                }
            } else {
                $resolved = [ $this, $toResolve ];
            }
        }

        if (!is_callable($resolved)) {
            return null;
        }

        return $resolved;
    }

    /**
     * Retrieve the available data sources (when setting data on an entity).
     *
     * @param array|mixed $dataset The entity data.
     * @return self
     */
    protected function mergeDataSources($dataset = null)
    {
        $sources = $this->dataSources();
        foreach ($sources as $sourceIdent) {
            $filter = $this->dataSourceFilter($sourceIdent);
            $getter = $this->camelize('data_from_' . $sourceIdent);
            $method = [ $this, $getter ];

            if (is_callable($method)) {
                $data = call_user_func($method);

                if ($data) {
                    if ($filter && $dataset) {
                        $data = call_user_func($filter, $data, $dataset);
                    }

                    parent::setData($data);
                }
            }
        }

        return $this;
    }
}
