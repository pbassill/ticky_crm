<?php

namespace OCA\TickyCRM\DB;

use OCP\AppFramework\Db\QBMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<Product>
 */
class ProductMapper extends QBMapper {

    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'ticky_products', Product::class);
    }

    public function findAll(): array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->tableName)
            ->orderBy('name', 'ASC');

        return $this->findEntities($qb);
    }

    public function findByUuid(string $uuid): Product {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->tableName)
            ->where($qb->expr()->eq('uuid', $qb->createNamedParameter($uuid)));

        return $this->findEntity($qb);
    }
}
