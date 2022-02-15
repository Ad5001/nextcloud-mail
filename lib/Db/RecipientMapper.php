<?php

declare(strict_types=1);

/**
 * Mail App
 *
 * @copyright 2022 Anna Larch <anna.larch@gmx.net>
 *
 * @author Anna Larch <anna.larch@gmx.net>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Mail\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<Recipient>
 */
class RecipientMapper extends QBMapper {
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'mail_recipients');
	}

	/**
	 * @returns Recipient[]
	 */
	public function findByMessageId(int $messageId, int $mailboxType = Recipient::MAILBOX_TYPE_INBOX): array {
		$qb = $this->db->getQueryBuilder();

		$query = $qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->eq('message_id', $qb->createNamedParameter($messageId, IQueryBuilder::PARAM_INT)),
				$qb->expr()->eq('mailbox_type', $qb->createNamedParameter($mailboxType, IQueryBuilder::PARAM_INT))
			);

		return $this->findEntities($query);
	}

	/**
	 *  @return Recipient[]
	 */
	public function findByMessageIds(array $messageIds, int $mailboxType = Recipient::MAILBOX_TYPE_INBOX): array {
		$qb = $this->db->getQueryBuilder();

		$query = $qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->in('message_id', $qb->createNamedParameter($messageIds, IQueryBuilder::PARAM_INT_ARRAY), IQueryBuilder::PARAM_INT_ARRAY),
				$qb->expr()->eq('mailbox_type', $qb->createNamedParameter($mailboxType, IQueryBuilder::PARAM_INT))
			);

		return $this->findEntities($query);
	}

	public function deleteForLocalMailbox(int $messageId): void {
		$qb = $this->db->getQueryBuilder();

		$qb->delete($this->getTableName())
			->where(
				$qb->expr()->eq('message_id', $qb->createNamedParameter($messageId, IQueryBuilder::PARAM_INT)),
				$qb->expr()->eq('mailbox_type', $qb->createNamedParameter(Recipient::MAILBOX_TYPE_OUTBOX, IQueryBuilder::PARAM_INT))
			);
		$qb->execute();
	}

	public function saveRecipients(int $messageId, array $recipients, int $type, int $mailboxType = Recipient::MAILBOX_TYPE_INBOX): void {
		if (empty($recipients)) {
			return;
		}

		$qb = $this->db->getQueryBuilder();
		$qb->insert($this->getTableName());
		$qb->setValue('message_id', $qb->createParameter('message_id'));
		$qb->setValue('type', $qb->createParameter('type'));
		$qb->setValue('label', $qb->createParameter('label'));
		$qb->setValue('email', $qb->createParameter('email'));
		$qb->setValue('mailbox_type', $qb->createParameter('mailbox_type'));

		foreach ($recipients as $recipient) {
			$qb->setParameter('message_id', $messageId, IQueryBuilder::PARAM_INT);
			$qb->setParameter('type', $type, IQueryBuilder::PARAM_INT);
			$qb->setParameter('label', $recipient['label'] ?? $recipient['email'], IQueryBuilder::PARAM_STR);
			$qb->setParameter('email', $recipient['email'], IQueryBuilder::PARAM_STR);
			$qb->setParameter('mailbox_type', $mailboxType, IQueryBuilder::PARAM_INT);
			$qb->execute();
		}
	}
}
