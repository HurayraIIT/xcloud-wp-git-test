<?php

/*
 * This file is part of the NxProGA\Monolog package.
 *
 * (c) Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace NxProGA\Monolog\Handler;

use NxProGA\Monolog\Logger;
use NxProGA\Monolog\Formatter\NormalizerFormatter;
use Doctrine\CouchDB\CouchDBClient;

/**
 * CouchDB handler for Doctrine CouchDB ODM
 *
 * @author Markus Bachmann <markus.bachmann@bachi.biz>
 */
class DoctrineCouchDBHandler extends AbstractProcessingHandler
{
    private $client;

    public function __construct(CouchDBClient $client, $level = Logger::DEBUG, $bubble = true)
    {
        $this->client = $client;
        parent::__construct($level, $bubble);
    }

    /**
     * {@inheritDoc}
     */
    protected function write(array $record)
    {
        $this->client->postDocument($record['formatted']);
    }

    protected function getDefaultFormatter()
    {
        return new NormalizerFormatter;
    }
}
