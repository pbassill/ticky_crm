<?php

namespace OCA\TickyCRM\Service;

use OCA\TickyCRM\DB\Product;
use OCA\TickyCRM\DB\ProductMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use DateTime;
use Exception;

class ProductService {

    public function __construct(
        private ProductMapper $mapper,
    ) {}

    public function all(): array {
        return $this->mapper->findAll();
    }

    public function find(string $uuid): Product {
        return $this->mapper->findByUuid($uuid);
    }

    public function create(array $data): Product {
        $product = new Product();
        $product->setUuid(bin2hex(random_bytes(16)));
        $product->setSku($data['sku']);
        $product->setName($data['name']);
        $product->setDescription($data['description'] ?? null);
        $product->setPrice(isset($data['price']) && $data['price'] !== '' ? $data['price'] : null);
        $product->setCurrency($data['currency'] ?? 'EUR');
        $product->setUnit($data['unit'] ?? null);
        $product->setCategory($data['category'] ?? null);
        $product->setStatus($data['status'] ?? 'active');
        $product->setCreatedAt(new DateTime());
        $product->setUpdatedAt(new DateTime());

        return $this->mapper->insert($product);
    }

    public function update(string $uuid, array $data): Product {
        $product = $this->mapper->findByUuid($uuid);

        if (isset($data['sku']))         $product->setSku($data['sku']);
        if (isset($data['name']))        $product->setName($data['name']);
        if (array_key_exists('description', $data)) $product->setDescription($data['description']);
        if (array_key_exists('price', $data)) {
            $product->setPrice($data['price'] !== '' ? $data['price'] : null);
        }
        if (isset($data['currency']))    $product->setCurrency($data['currency']);
        if (array_key_exists('unit', $data))     $product->setUnit($data['unit']);
        if (array_key_exists('category', $data)) $product->setCategory($data['category']);
        if (isset($data['status']))      $product->setStatus($data['status']);
        $product->setUpdatedAt(new DateTime());

        return $this->mapper->update($product);
    }

    public function delete(string $uuid): void {
        $this->mapper->delete($this->mapper->findByUuid($uuid));
    }
}
