<?php

namespace Charcoal\Admin\Script\Object;

// From PSR-7
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
// From Pimple
use Pimple\Container;
// From 'charcoal-core'
use Charcoal\Loader\CollectionLoader;
// From 'charcoal-factory'
use Charcoal\Factory\FactoryInterface;
// From 'charcoal-app'
use Charcoal\App\Script\CronScriptInterface;
use Charcoal\App\Script\CronScriptTrait;
// From 'charcoal-object'
use Charcoal\Object\ObjectSchedule;
// From 'charcoal-admin'
use Charcoal\Admin\AdminScript;

/**
 * Process object schedules.
 */
class ProcessSchedulesScript extends AdminScript implements CronScriptInterface
{
    use CronScriptTrait;

    /**
     * @var FactoryInterface $scheduleFactory
     */
    private $scheduleFactory;

    /**
     * @return array
     */
    public function defaultArguments()
    {
        $arguments = [
            'obj-type' => [
                'longPrefix'   => 'obj-type',
                'description'  => 'Object type. Leave empty to process all objects.',
                'defaultValue' => ''
            ],
            'obj-id' => [
                'longPrefix'   => 'obj-id',
                'description'  => 'Object ID. Must have "obj-type" set to have any effect. ' .
                                  'Leave empty to process all objects.',
                'defaultValue' => ''
            ]
        ];

        $arguments = array_merge(parent::defaultArguments(), $arguments);
        return $arguments;
    }

    /**
     * @param RequestInterface  $request  A PSR-7 compatible Request instance.
     * @param ResponseInterface $response A PSR-7 compatible Response instance.
     * @return ResponseInterface
     */
    public function run(RequestInterface $request, ResponseInterface $response)
    {
        unset($request);

        $this->startLock();

        $climate = $this->climate();

        $objType = $climate->arguments->get('obj-type');
        $objId = $climate->arguments->get('obj-id');

        $scheduled = $this->loadSchedules($objType, $objId);

        $callback = function ($obj) use ($climate) {
            // No default callback
        };

        $successCallback = function ($obj) use ($climate) {
            $climate->green()->out(
                sprintf('Object %s : %s schedule was successfully ran.', $obj->targetType(), $obj->targetId())
            );
        };

        $failureCallback = function ($obj) use ($climate) {
            $climate->red()->out(
                sprintf('Object %s : %s schedule could not be ran.', $obj->targetType(), $obj->targetId())
            );
        };

        foreach ($scheduled as $schedule) {
            $schedule->setModelFactory($this->modelFactory());
            $schedule->process($callback, $successCallback, $failureCallback);
        }

        $this->stopLock();

        return $response;
    }

    /**
     * @param Container $container Pimple DI container.
     * @return void
     */
    protected function setDependencies(Container $container)
    {
        parent::setDependencies($container);

        $this->setScheduleFactory($container['model/factory']);
    }

    /**
     * @return FactoryInterface
     */
    protected function scheduleFactory()
    {
        return $this->scheduleFactory;
    }

    /**
     * @param FactoryInterface $factory The factory used to create queue items.
     * @return void
     */
    private function setScheduleFactory(FactoryInterface $factory)
    {
        $this->scheduleFactory = $factory;
    }

    /**
     * @return ObjectSchedule
     */
    private function scheduleProto()
    {
        return $this->modelFactory()->create(ObjectSchedule::class);
    }

    /**
     * @param string $objType Optional object type to load.
     * @param string $objId   Optional object id to loader.
     * @return \Charcoal\Model\Collection|array
     */
    private function loadSchedules($objType = null, $objId = null)
    {
        $loader = new CollectionLoader([
            'logger' => $this->logger,
            'factory' => $this->scheduleFactory()
        ]);
        $loader->setModel($this->scheduleProto());
        if ($objType) {
            $loader->addFilter([
                'property' => 'target_type',
                'val'      => $objType
            ]);

            if ($objId) {
                $loader->addFilter([
                    'property' => 'target_id',
                    'val'      => $objId
                ]);
            }
        }
        $loader->addFilter([
            'property' => 'processed',
            'val'      => 0
        ]);
        $loader->addFilter([
            'property' => 'scheduled_date',
            'val'      => date('Y-m-d H:i:s'),
            'operator' => '<'
        ]);

        $loader->addOrder([
            'property' => 'scheduled_date',
            'mode'     => 'asc'
        ]);
        $schedules = $loader->load();
        return $schedules;
    }
}
