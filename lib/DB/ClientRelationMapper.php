<?php

namespace OCA\TickyCRM\DB;

use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<ClientRelation>
 */
class ClientRelationMapper extends QBMapper {

    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'ticky_client_relations', ClientRelation::class);
    }

    /**
     * Returns all relations for a given client (both directions), enriched
     * with the related client's name, uuid and client_number.
     */
    public function findAllForClient(int $clientId): array {
        $qb = $this->db->getQueryBuilder();

        // Relations where this client is the source
        $qb->select(
            'r.id',
            'r.client_id',
            'r.related_client_id',
            'r.relation_type',
            'r.notes',
            'r.created_at',
            'c.name AS related_name',
            'c.uuid AS related_uuid',
            'c.client_number AS related_client_number',
            'c.status AS related_status',
            'c.type AS related_type'
        )
            ->from($this->tableName, 'r')
            ->innerJoin('r', 'ticky_clients', 'c', $qb->expr()->eq('r.related_client_id', 'c.id'))
            ->where($qb->expr()->eq('r.client_id', $qb->createNamedParameter($clientId, IQueryBuilder::PARAM_INT)))
            ->orderBy('r.created_at', 'ASC');

        $result = $qb->executeQuery();
        $rows = [];
        while ($row = $result->fetch()) {
            $rows[] = $this->rowToArray($row, $clientId, false);
        }
        $result->closeCursor();

        // Relations where this client is the target (reverse)
        $qb2 = $this->db->getQueryBuilder();
        $qb2->select(
            'r.id',
            'r.client_id',
            'r.related_client_id',
            'r.relation_type',
            'r.notes',
            'r.created_at',
            'c.name AS related_name',
            'c.uuid AS related_uuid',
            'c.client_number AS related_client_number',
            'c.status AS related_status',
            'c.type AS related_type'
        )
            ->from($this->tableName, 'r')
            ->innerJoin('r', 'ticky_clients', 'c', $qb2->expr()->eq('r.client_id', 'c.id'))
            ->where($qb2->expr()->eq('r.related_client_id', $qb2->createNamedParameter($clientId, IQueryBuilder::PARAM_INT)))
            ->orderBy('r.created_at', 'ASC');

        $result2 = $qb2->executeQuery();
        while ($row = $result2->fetch()) {
            $rows[] = $this->rowToArray($row, $clientId, true);
        }
        $result2->closeCursor();

        return $rows;
    }

    /**
     * Checks whether a relation between two clients already exists (in either direction).
     */
    public function relationExists(int $clientId, int $relatedClientId): bool {
        $qb = $this->db->getQueryBuilder();
        $qb->select($qb->func()->count('id', 'cnt'))
            ->from($this->tableName)
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->andX(
                        $qb->expr()->eq('client_id', $qb->createNamedParameter($clientId, IQueryBuilder::PARAM_INT)),
                        $qb->expr()->eq('related_client_id', $qb->createNamedParameter($relatedClientId, IQueryBuilder::PARAM_INT))
                    ),
                    $qb->expr()->andX(
                        $qb->expr()->eq('client_id', $qb->createNamedParameter($relatedClientId, IQueryBuilder::PARAM_INT)),
                        $qb->expr()->eq('related_client_id', $qb->createNamedParameter($clientId, IQueryBuilder::PARAM_INT))
                    )
                )
            );

        $result = $qb->executeQuery();
        $count = (int)$result->fetchOne();
        $result->closeCursor();

        return $count > 0;
    }

    /**
     * Finds a single relation by its id.
     */
    public function findById(int $id): ClientRelation {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->tableName)
            ->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)));

        return $this->findEntity($qb);
    }

    // -------------------------------------------------------------------------

    private function rowToArray(array $row, int $perspectiveClientId, bool $isReverse): array {
        return [
            'id'                     => (int)$row['id'],
            'client_id'              => (int)$row['client_id'],
            'related_client_id'      => (int)$row['related_client_id'],
            'relation_type'          => $row['relation_type'],
            'notes'                  => $row['notes'],
            'created_at'             => $row['created_at'],
            'is_reverse'             => $isReverse,
            'related_name'           => $row['related_name'],
            'related_uuid'           => $row['related_uuid'],
            'related_client_number'  => $row['related_client_number'],
            'related_status'         => $row['related_status'],
            'related_type'           => $row['related_type'],
        ];
    }
}
