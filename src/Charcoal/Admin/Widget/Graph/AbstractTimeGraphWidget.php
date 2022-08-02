<?php

namespace Charcoal\Admin\Widget\Graph;

use DateInterval;
use DateTime;
use DateTimeInterface;
use Exception;
use InvalidArgumentException;
use PDO;
// From 'charcoal-admin'
use Charcoal\Admin\Widget\Graph\AbstractGraphWidget;
use Charcoal\Admin\Widget\Graph\TimeGraphWidgetInterface;

/**
 * Base Time Graph widget.
 *
 * This widget implements the core feature to create a specialized
 * graph widget that is meant to display object data "over" time.
 */
abstract class AbstractTimeGraphWidget extends AbstractGraphWidget implements TimeGraphWidgetInterface
{
    /**
     * @var array $dbRows
     */
    private $dbRows;

    /**
     * The date grouping type can be "hour", "day" or "month".
     *
     * @var string $groupingType
     */
    private $groupingType;

    /**
     * @var string $dateFirnat
     */
    private $dateFormat;

    /**
     * @var string $sqlDateFormat
     */
    private $sqlDateFormat;

    /**
     * @var DateTimeInterface $startDate
     */
    private $startDate;

    /**
     * @var DateTimeInterface $endDate
     */
    private $endDate;

    /**
     * @var DateInterval $dateInterval
     */
    private $dateInterval;

    /**
     * @param string $type The group type.
     * @throws InvalidArgumentException If the group type is not a valid type.
     * @return TimeGraphWidgetInterface Chainable
     */
    public function setGroupingType($type)
    {
        if ($type === 'hour') {
            $this->groupingType = 'hour';
            return $this->setGroupingTypeByHour();
        } elseif ($type === 'day') {
            $this->groupingType = 'day';
            return $this->setGroupingTypeByDay();
        } elseif ($type === 'month') {
            $this->groupingType = 'month';
            return $this->setGroupingTypeByMonth();
        } else {
            throw new InvalidArgumentException(
                'Invalid group type: can be "hour", "day" or "month".'
            );
        }
    }

    /**
     * @return string
     */
    public function groupingType()
    {
        return $this->groupingType;
    }

    /**
     * @param string $format The date format.
     * @throws InvalidArgumentException If the format argument is not a string.
     * @return TimeGraphWidgetInterface Chainable
     */
    public function setDateFormat($format)
    {
        if (!is_string($format)) {
            throw new InvalidArgumentException(
                'Date format must be a string'
            );
        }
        $this->dateFormat = $format;
        return $this;
    }

    /**
     * @return string
     */
    public function dateFormat()
    {
        return $this->dateFormat;
    }

    /**
     * @param string $format The date format.
     * @throws InvalidArgumentException If the format argument is not a string.
     * @return TimeGraphWidgetInterface Chainable
     */
    public function setSqlDateFormat($format)
    {
        if (!is_string($format)) {
            throw new InvalidArgumentException(
                'SQL date format must be a string'
            );
        }
        $this->sqlDateFormat = $format;
        return $this;
    }

    /**
     * @return string
     */
    public function sqlDateFormat()
    {
        return $this->sqlDateFormat;
    }

    /**
     * @param string|DateTimeInterface $ts The start date.
     * @throws InvalidArgumentException If the date is not a valid datetime format.
     * @return TimeGraphWidgetInterface Chainable
     */
    public function setStartDate($ts)
    {
        if (is_string($ts)) {
            try {
                $ts = new DateTime($ts);
            } catch (Exception $e) {
                throw new InvalidArgumentException(sprintf(
                    'Invalid start date: %s',
                    $e->getMessage()
                ), $e);
            }
        }
        if (!($ts instanceof DateTimeInterface)) {
            throw new InvalidArgumentException(
                'Invalid "Start Date" value. Must be a date/time string or a DateTime object.'
            );
        }
        $this->startDate = $ts;
        return $this;
    }

    /**
     * @return DateTimeInterface
     */
    public function startDate()
    {
        return $this->startDate;
    }

    /**
     * @param string|DateTimeInterface $ts The end date.
     * @throws InvalidArgumentException If the date is not a valid datetime format.
     * @return TimeGraphWidgetInterface Chainable
     */
    public function setEndDate($ts)
    {
        if (is_string($ts)) {
            try {
                $ts = new DateTime($ts);
            } catch (Exception $e) {
                throw new InvalidArgumentException(sprintf(
                    'Invalid end date: %s',
                    $e->getMessage()
                ), $e);
            }
        }
        if (!($ts instanceof DateTimeInterface)) {
            throw new InvalidArgumentException(
                'Invalid "End Date" value. Must be a date/time string or a DateTime object.'
            );
        }
        $this->endDate = $ts;
        return $this;
    }

    /**
     * @return DateTimeInterface
     */
    public function endDate()
    {
        return $this->endDate;
    }

    /**
     * @param string|DateInterval $interval The date interval, between "categories".
     * @throws InvalidArgumentException If the argument is not a string or an interval object.
     * @return TimeGraphWidgetInterface Chainable
     */
    public function setDateInterval($interval)
    {
        if (is_string($interval)) {
            $this->dateInterval = DateInterval::createfromdatestring($interval);
        } elseif ($interval instanceof DateInterval) {
            $this->dateInterval = $interval;
        } else {
            throw new InvalidArgumentException(
                'Can not set date interval.'
            );
        }
        return $this;
    }

    /**
     * @return DateInterval
     */
    public function dateInterval()
    {
        return $this->dateInterval;
    }

    /**
     * @return TimeGraphWidgetInterface Chainable
     */
    protected function setGroupingTypeByHour()
    {
        $this->setDateFormat('Y-m-d H:i');
        $this->setSqlDateFormat('%Y-%m-%d %H:%i');
        $this->setStartDate('-24 hours');
        $this->setEndDate('now');
        $this->setDateInterval('+1 hour');

        return $this;
    }

    /**
     * @return TimeGraphWidgetInterface Chainable
     */
    protected function setGroupingTypeByDay()
    {
        $this->setDateFormat('Y-m-d');
        $this->setSqlDateFormat('%Y-%m-%d');
        $this->setStartDate('-30 days');
        $this->setEndDate('now');
        $this->setDateInterval('+1 day');

        return $this;
    }

    /**
     * @return TimeGraphWidgetInterface Chainable
     */
    protected function setGroupingTypeByMonth()
    {
        $this->setDateFormat('Y-m');
        $this->setSqlDateFormat('%Y-%m');
        $this->setStartDate('-12 months');
        $this->setEndDate('now');
        $this->setDateInterval('+1 month');

        return $this;
    }

    /**
     * @return array
     */
    protected function dbRows()
    {
        if ($this->dbRows === null) {
            $model = $this->modelFactory()->create($this->objType());

            $cols = [];
            $seriesOptions = $this->seriesOptions();
            foreach ($seriesOptions as $serieId => $serieOpts) {
                $cols[] = $serieOpts['function'] . ' AS ' . $serieId;
            }

            $sql = strtr(
                'SELECT
                    %func AS x, %cols
                FROM
                    %table
                WHERE
                    %func BETWEEN  :start_date AND :end_date
                GROUP BY
                    %func
                ORDER BY
                    %func ASC',
                [
                    '%table' => $model->source()->table(),
                    '%cols'  => implode(', ', $cols),
                    '%func'  => $this->categoryFunction(),
                ]
            );
            $result = $model->source()->dbQuery($sql, [
                'start_date' => $this->startDate()->format($this->dateFormat()),
                'end_date'   => $this->endDate()->format($this->dateFormat())
            ]);

            $this->dbRows = $result->fetchAll((PDO::FETCH_GROUP | PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC));
        }

        return $this->dbRows;
    }

    /**
     * Fill all rows with 0 value for all series, when it is unset.
     *
     * @param array $rows The row structure to fill.
     * @return array
     */
    protected function fillRows(array $rows)
    {
        $row = [];
        $seriesOptions = $this->seriesOptions();
        foreach ($seriesOptions as $serieId => $serieOpts) {
            $row[$serieId] = '0';
        }

        $starts = clone($this->startDate());
        $ends = $this->endDate();
        while ($starts < $ends) {
            $x = $starts->format($this->dateFormat());
            if (!isset($rows[$x])) {
                $rows[$x] = $row;
            }
            $starts->add($this->dateInterval());
        }
        ksort($rows);
        return $rows;
    }

    /**
     * @return array Categories structure.
     */
    public function categories()
    {
        $rows = $this->dbRows();
        $rows = $this->fillRows($rows);
        return array_keys($rows);
    }

    /**
     * @return array Series structure.
     */
    public function series()
    {
        $rows = $this->dbRows();
        $rows = $this->fillRows($rows);

        $series = [];
        $options = $this->seriesOptions();
        foreach ($options as $serieId => $serieOptions) {
            $series[] = [
                'name' => (string)$serieOptions['name'],
                'type' => (string)$serieOptions['type'],
                'data' => array_column($rows, $serieId)
            ];
        }

        return $series;
    }

    /**
     * @return string
     */
    abstract protected function objType();

    /**
     * @return array
     */
    abstract protected function seriesOptions();

    /**
     * @return string
     */
    abstract protected function categoryFunction();
}
