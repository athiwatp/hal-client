<?php

namespace Jsor\HalClient\Internal;

use Jsor\HalClient\ClientInterface;
use Jsor\HalClient\Exception;
use Jsor\HalClient\Resource;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class ResourceFactory
{
    private $validContentTypes;

    public function __construct(array $validContentTypes)
    {
        $this->validContentTypes = $validContentTypes;
    }

    public function createResource(
        ClientInterface $client,
        RequestInterface $request,
        ResponseInterface $response,
        $ignoreInvalidContentType = false
    ) {
        if (204 === $response->getStatusCode()) {
            // No-Content response
            return new Resource($client);
        }

        $body = trim($this->fetchBody($client, $request, $response));

        if (201 === $response->getStatusCode() &&
            '' === $body &&
            $response->hasHeader('Location')) {
            // Created response with Location header
            return $client->request('GET', $response->getHeader('Location')[0]);
        }

        if (!$this->isValidContentType($response)) {
            return $this->handleInvalidContentType(
                $client,
                $request,
                $response,
                $ignoreInvalidContentType
            );
        }

        return $this->handleValidContentType(
            $client,
            $request,
            $response,
            $body
        );
    }

    private function isValidContentType(ResponseInterface $response)
    {
        $contentTypeHeaders = $response->getHeader('Content-Type');

        foreach ($this->validContentTypes as $validContentType) {
            if (in_array($validContentType, $contentTypeHeaders)) {
                return true;
            }
        }

        return false;
    }

    private function handleInvalidContentType(
        ClientInterface $client,
        RequestInterface $request,
        ResponseInterface $response,
        $ignoreInvalidContentType
    ) {
        if ($ignoreInvalidContentType) {
            return new Resource($client);
        }

        $types = $response->getHeader('Content-Type') ?: ['none'];

        throw new Exception\BadResponseException(
            sprintf(
                'Request did not return a valid content type. Returned content type: %s.',
                implode(', ', $types)
            ),
            $request,
            $response,
            new Resource($client)
        );
    }

    private function handleValidContentType(
        ClientInterface $client,
        RequestInterface $request,
        ResponseInterface $response,
        $body
    ) {
        if ('' === $body) {
            return new Resource($client);
        }

        $data = $this->decodeBody($client, $request, $response, $body);

        return Resource::fromArray($client, (array) $data);
    }

    private function fetchBody(
        ClientInterface $client,
        RequestInterface $request,
        ResponseInterface $response
    ) {
        try {
            return $response->getBody()->getContents();
        } catch (\Exception $e) {
            throw new Exception\BadResponseException(
                sprintf(
                    'Error getting response body: %s.',
                    $e->getMessage()
                ),
                $request,
                $response,
                new Resource($client),
                $e
            );
        }
    }

    private function decodeBody(
        ClientInterface $client,
        RequestInterface $request,
        ResponseInterface $response,
        $body
    ) {
        $data = json_decode($body, true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new Exception\BadResponseException(
                sprintf(
                    'JSON parse error: %s.',
                    json_last_error_msg()
                ),
                $request,
                $response,
                new Resource($client)
            );
        }

        return $data;
    }
}