<?php

declare(strict_types=1);

namespace ResilientLogger\Targets;

use ResilientLogger\Sources\AbstractLogSource;
use ResilientLogger\Targets\AbstractLogTarget;
use ResilientLogger\Utils\Helpers;
use Elastic\Elasticsearch\ClientBuilder;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\Exception\ClientResponseException;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

use \ResilientLogger\Sources\Types;

/**
 * @phpstan-import-type AuditLogDocument from Types
 */
class ElasticsearchLogTarget extends AbstractLogTarget {
  private const ES_STATUS_CREATED = "created";
  private static LoggerInterface $logger;

  private string $index;
  private Client $client;

  /**
   * @param array{
   *   es_username: string,
   *   es_password: string,
   *   es_index: string,
   *   es_url?: string,
   *   es_host?: string,
   *   es_port?: int,
   *   es_scheme?: string,
   *   required?: bool,
   * } $options
   */
  protected function __construct(array $options) {
      parent::__construct($options);
      
      list(
        'es_username' => $es_username,
        'es_password' => $es_password,
        'es_index' => $es_index,
        'es_url' => $es_url,
        'es_host' => $es_host,
        'es_port' => $es_port,
        'es_scheme' => $es_scheme,
      ) = $options;

      $es_host ??= 'localhost';
      $es_port ??= 9200;
      $es_scheme ??= 'https';

      if (!empty($es_url)) {
        if (strpos($es_url, "://") === false) {
          $es_url = "{$es_scheme}://{$es_url}";
        }

        $parsed = parse_url($es_url);
        $es_scheme = $parsed["scheme"];
        $es_host = $parsed["host"];
        $es_port = intval($parsed["port"] ?? $es_port);
      }

      $parsed_host = "{$es_scheme}://{$es_host}:{$es_port}";
      
      if (!isset(self::$logger)) {
        self::$logger = new NullLogger();
      }

      $this->index = $es_index;
      $this->client = ClientBuilder::create()
        ->setHosts([$parsed_host])
        ->setBasicAuthentication($es_username, $es_password)
        ->build();
  }

  public function submit(AbstractLogSource $entry): bool {
    $document = $entry->getDocument();
    $hash = Helpers::contentHash($document);

    try {
       $response = $this->client->index([
        'index' => $this->index,
        'id' => $hash,
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
        self::$logger->warning(
          "Skipping the document with key {$hash}, it's already submitted.",
          ["document" => $document],
        );

        return true;
      }

      /**
       * Non-conflict ClientResponseException, log it and keep going to avoid transaction rollbacks.
       */
      return $this->handleException($hash, $document, $e);
    } catch (\Exception $e) {
      /**
       * Unknown exception, log it and keep going to avoid transaction rollbacks.
       */
      return $this->handleException($hash, $document, $e);
    }

    return false;
  }

  /**
   * Logs the exception and return always false.
   * 
   * @param string $hash
   * @param AuditLogDocument $document
   * @param \Exception $e
   * @return false
   */
  private function handleException(string $hash, array $document, \Exception $e) {
    self::$logger->error("Entry with key {$hash} failed.", [
      "document" => $document, 
      "message" => $e->getMessage(), 
      "stack_trace" => $e->getTraceAsString()
    ]);

    return false;
  }

  public static function setLogger(LoggerInterface $logger) {
    self::$logger = $logger;
  }
}