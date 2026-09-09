<?php

namespace OCA\TickyCRM\DB;

use OCP\AppFramework\Db\Entity;
use DateTime;

/**
 * @method int getClientId()
 * @method void setClientId(int $clientId)
 * @method int getRelatedClientId()
 * @method void setRelatedClientId(int $relatedClientId)
 * @method string getRelationType()
 * @method void setRelationType(string $relationType)
 * @method string|null getNotes()
 * @method void setNotes(string|null $notes)
 * @method DateTime|null getCreatedAt()
 * @method void setCreatedAt(DateTime|null $createdAt)
 */
class ClientRelation extends Entity implements \JsonSerializable {

    protected $clientId;
    protected $relatedClientId;
    protected $relationType;
    protected $notes;
    protected $createdAt;

    public function __construct() {
        $this->addType('clientId', 'integer');
        $this->addType('relatedClientId', 'integer');
        $this->addType('createdAt', 'datetime');
    }

    public function jsonSerialize(): array {
        return [
            'id'                => $this->getId(),
            'client_id'         => $this->getClientId(),
            'related_client_id' => $this->getRelatedClientId(),
            'relation_type'     => $this->getRelationType(),
            'notes'             => $this->getNotes(),
            'created_at'        => $this->getCreatedAt()
                ? $this->getCreatedAt()->format(\DateTime::ATOM)
                : null,
        ];
    }
}
