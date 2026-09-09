<?php

namespace OCA\TickyCRM\Controller;

use OCA\TickyCRM\Service\ProductService;
use OCP\AppFramework\ApiController;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

class ProductController extends ApiController {

    public function __construct(
        string $appName,
        IRequest $request,
        private ProductService $service,
    ) {
        parent::__construct($appName, $request);
    }

    #[NoAdminRequired]
    #[\OCP\AppFramework\Http\Attribute\NoCSRFRequired]
    public function index(): DataResponse {
        try {
            return new DataResponse($this->service->all());
        } catch (\Throwable $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }

    #[NoAdminRequired]
    public function show(string $uuid): DataResponse {
        try {
            return new DataResponse($this->service->find($uuid));
        } catch (DoesNotExistException) {
            return new DataResponse([], Http::STATUS_NOT_FOUND);
        }
    }

    #[NoAdminRequired]
    public function create(): DataResponse {
        try {
            $product = $this->service->create($this->request->getParams());
            return new DataResponse($product, Http::STATUS_CREATED);
        } catch (\Throwable $e) {
            $previous = $e->getPrevious();
            $errorMessage = $previous ? $previous->getMessage() : $e->getMessage();
            $errorCode    = $previous ? $previous->getCode()    : $e->getCode();

            if ($errorCode === 23000 || str_contains($errorMessage, '1062')) {
                return new DataResponse([
                    'error'   => 'duplicate_sku',
                    'message' => 'SKU already exists.',
                ], Http::STATUS_CONFLICT);
            }
            return new DataResponse(['error' => $errorMessage], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }

    #[NoAdminRequired]
    public function update(string $uuid): DataResponse {
        try {
            return new DataResponse($this->service->update($uuid, $this->request->getParams()));
        } catch (DoesNotExistException) {
            return new DataResponse(['message' => 'Product not found.'], Http::STATUS_NOT_FOUND);
        } catch (\Throwable $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }

    #[NoAdminRequired]
    public function delete(string $uuid): DataResponse {
        try {
            $this->service->delete($uuid);
            return new DataResponse(['success' => true]);
        } catch (DoesNotExistException) {
            return new DataResponse([], Http::STATUS_NOT_FOUND);
        }
    }
}
