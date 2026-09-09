<?php

namespace OCA\TickyCRM\DB;

use OCP\AppFramework\Db\Entity;
use JsonSerializable;

/**
 * @method string|null getUuid()
 * @method string|null getSku()
 * @method string|null getName()
 * @method string|null getDescription()
 * @method string|null getPrice()
 * @method string|null getCurrency()
 * @method string|null getUnit()
 * @method string|null getCategory()
 * @method string|null getStatus()
 * @method \DateTime|null getCreatedAt()
 * @method \DateTime|null getUpdatedAt()
 */
class Product extends Entity implements JsonSerializable {
    protected $uuid;
    protected $sku;
    protected $name;
    protected $description;
    protected $price;
    protected $currency;
    protected $unit;
    protected $category;
    protected $status;
    protected $createdAt;
    protected $updatedAt;

    public function __construct() {
        $this->addType('createdAt', 'datetime');
        $this->addType('updatedAt', 'datetime');
    }

    public function jsonSerialize(): array {
        return [
            'id'          => $this->getId(),
            'uuid'        => $this->getUuid(),
            'sku'         => $this->getSku(),
            'name'        => $this->getName(),
            'description' => $this->getDescription(),
            'price'       => $this->getPrice(),
            'currency'    => $this->getCurrency(),
            'unit'        => $this->getUnit(),
            'category'    => $this->getCategory(),
            'status'      => $this->getStatus(),
            'created_at'  => $this->getCreatedAt() ? $this->getCreatedAt()->format(\DateTime::ATOM) : null,
            'updated_at'  => $this->getUpdatedAt() ? $this->getUpdatedAt()->format(\DateTime::ATOM) : null,
        ];
    }
}
