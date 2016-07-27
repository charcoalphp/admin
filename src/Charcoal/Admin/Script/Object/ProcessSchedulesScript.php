<?php

namespace Charcoal\Admin\Script\Object;

// PSR-7 (http messaging) dependencies
use \Psr\Http\Message\RequestInterface;
use \Psr\Http\Message\ResponseInterface;

// Pimple (DI container) dependencies
use \Pimple\Container;

// From `charcoal-core`
use \Charcoal\Loader\CollectionLoader;

// From `charcoal-factory`
use \Charcoal\Factory\FactoryInterface;

use \Charcoal\Admin\AdminScript;

// Module `charcoal-app` dependencies
use \Charcoal\App\Script\CronScriptInterface;
use \Charcoal\App\Script\CronScriptTrait;

// Module `charcoal-base` dependencies
use \Charcoal\Object\ObjectSchedule;

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
     * @param Container $container Pimple DI container.
     * @return void
     */
    public function setDependencies(Container $container)
    {
        parent::setDependencies($container);
        $this->setScheduleFactory($container['model/factory']);
    }

    /**
     * @param FactoryInterface $factory The factory used to create queue items.
     * @return ScheduleInterface Chainable
     */
    protected function setScheduleFactory(FactoryInterface $factory)
    {
        $this->scheduleFactory = $factory;
        return $this;
    }

    /**
     * @return FactoryInterface
     */
    protected function scheduleFactory()
    {
        return $this->scheduleFactory;
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

        $loader = new CollectionLoader([
            'logger' => $this->logger,
            'factory' => $this->scheduleFactory()
        ]);
        $loader->setModel($this->scheduleProto());
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
        $scheduled = $loader->load();

        $callback = function($obj) use ($climate) {
            // No default callback
        };

        $successCallback = function($obj) use ($climate) {
            $climate->green()->out(
                sprintf('Object %s : %s schedule was successfully ran.', $obj->targetType(), $obj->targetId())
            );
        };

        $failureCallback = function($obj) use ($climate) {
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
     * @return ObjectSchedule
     */
    private function scheduleProto()
    {
        return $this->modelFactory()->create(ObjectSchedule::class);
    }
}
