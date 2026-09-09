<?php

namespace OCA\TickyCRM\Service;

use OCA\TickyCRM\DB\ClientMapper;
use OCA\TickyCRM\DB\ClientRelation;
use OCA\TickyCRM\DB\ClientRelationMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use DateTime;

class ClientRelationService {

    public const ALLOWED_TYPES = [
        'partner',
        'subsidiary',
        'parent',
        'reseller',
        'reseller_client',
        'competitor',
        'other',
    ];

    public function __construct(
        private ClientRelationMapper $mapper,
        private ClientMapper $clientMapper,
    ) {}

    /**
     * Returns all relations for the client identified by $uuid,
     * enriched with the related client's display data.
     */
    public function getRelationsForClient(string $uuid): array {
        $client = $this->clientMapper->findByUuid($uuid, false);
        return $this->mapper->findAllForClient($client->getId());
    }

    /**
     * Creates a new relation between two clients.
     *
     * @throws \InvalidArgumentException if the type is invalid, clients are identical,
     *                                   or the relation already exists.
     */
    public function addRelation(string $uuid, string $relatedUuid, string $relationType, ?string $notes): array {
        if ($uuid === $relatedUuid) {
            throw new \InvalidArgumentException('A client cannot be related to itself.');
        }

        if (!in_array($relationType, self::ALLOWED_TYPES, true)) {
            throw new \InvalidArgumentException('Invalid relation type: ' . $relationType);
        }

        $client        = $this->clientMapper->findByUuid($uuid, false);
        $relatedClient = $this->clientMapper->findByUuid($relatedUuid, false);

        if ($this->mapper->relationExists($client->getId(), $relatedClient->getId())) {
            throw new \RuntimeException('A relation between these clients already exists.');
        }

        $relation = new ClientRelation();
        $relation->setClientId($client->getId());
        $relation->setRelatedClientId($relatedClient->getId());
        $relation->setRelationType($relationType);
        $relation->setNotes($notes ?: null);
        $relation->setCreatedAt(new DateTime());

        $saved = $this->mapper->insert($relation);

        return array_merge($saved->jsonSerialize(), [
            'related_name'          => $relatedClient->getName(),
            'related_uuid'          => $relatedClient->getUuid(),
            'related_client_number' => $relatedClient->getClientNumber(),
            'related_status'        => $relatedClient->getStatus(),
            'related_type'          => $relatedClient->getType(),
            'is_reverse'            => false,
        ]);
    }

    /**
     * Deletes a relation by its id after verifying it belongs to the given client.
     *
     * @throws DoesNotExistException if the relation doesn't exist.
     * @throws \RuntimeException     if the relation does not belong to this client.
     */
    public function deleteRelation(string $uuid, int $relationId): void {
        $client   = $this->clientMapper->findByUuid($uuid, false);
        $relation = $this->mapper->findById($relationId);

        $belongsToClient =
            $relation->getClientId() === $client->getId()
            || $relation->getRelatedClientId() === $client->getId();

        if (!$belongsToClient) {
            throw new \RuntimeException('Relation does not belong to this client.');
        }

        $this->mapper->delete($relation);
    }
}
