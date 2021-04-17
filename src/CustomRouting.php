<?php declare(strict_types=1);

namespace ElasticScoutDriver;

/**
 * Implement this interface to enable custom elasticsearch routing functionality.
 * https://www.elastic.co/guide/en/elasticsearch/reference/current/mapping-routing-field.html
 */
interface CustomRouting
{
    public function getRoutingKey(): string;
}
