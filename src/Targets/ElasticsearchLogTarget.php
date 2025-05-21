<?php

declare(strict_types=1);

namespace ResilientLogger\Targets;

use ResilientLogger\Sources\AbstractLogSource;
use ResilientLogger\Targets\AbstractLogTarget;
use ResilientLogger\Utils\Helpers;
use Elastic\Elasticsearch\ClientBuilder;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\Exception\ClientResponseException;

class ElasticsearchLogTarget extends AbstractLogTarget {
  private const ES_STATUS_CREATED = "created";

  private string $index;
  private Client $client;

  /**
   * @param array{
   *   es_host: string,
   *   es_port: int,
   *   es_scheme: string,
   *   es_username: string,
   *   es_password: string,
   *   es_index: string,
   *   required: ?bool,
   * } $options
   */
  protected function __construct(array $options) {
      parent::__construct($options);
      
      $hostname = $options["es_host"] ?? "localhost";
      $port = $options["es_port"] ?? 9200;
      $scheme = $options["es_scheme"] ?? "https";
      $username = $options["es_username"];
      $password = $options["es_password"];
      $index = $options["es_index"];
      $host = "{$scheme}://{$hostname}:{$port}";

      $this->index = $index;
      $this->client = ClientBuilder::create()
        ->setHosts([$host])
        ->setBasicAuthentication($username, $password)
        ->build();
  }

  public function submit(AbstractLogSource $entry): bool {
    try {
      $document = Helpers::createTargetDocument($entry);
      $response = $this->client->index([
        'index' => $this->index,
        'id' => Helpers::contentHash($document),
        'body' => $document,
        'op_type' => "create",
      ]);

      $result  = $response['result'];

      if ($result == self::ES_STATUS_CREATED) {
        return true;
      }
    } catch (ClientResponseException $e) {
      /**
        * The document key used to store log entry is the hash of the contents.
        * If we receive conflict error, it means that the given entry is already
        * sent to the Elasticsearch.
        */
      if ($e->getResponse()->getStatusCode() == 409) {
        return true;
      }

      throw $e;
    }

    return false;
  }
}