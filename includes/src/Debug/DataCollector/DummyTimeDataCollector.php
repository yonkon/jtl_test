<?php declare(strict_types=1);

namespace JTL\Debug\DataCollector;

use DebugBar\DataCollector\TimeDataCollector;

/**
 * Class DummyTimeDataCollector
 * @package JTL\Debug\DataCollector
 */
class DummyTimeDataCollector extends TimeDataCollector
{
    /**
     * @inheritdoc
     */
    public function startMeasure($name, $label = null, $collector = null)
    {
    }

    /**
     * @inheritdoc
     */
    public function stopMeasure($name, $params = [])
    {
    }
}
