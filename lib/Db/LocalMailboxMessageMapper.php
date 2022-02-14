<?php

declare(strict_types=1);

/**
 * @copyright 2022 Anna Larch <anna@nextcloud.com>
 *
 * @author 2022 Anna Larch <anna@nextcloud.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\Mail\Db;

use OCP\DB\Exception as DBException;
use function array_map;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<LocalMailboxMessage>
 */
class LocalMailboxMessageMapper extends QBMapper {
	/** @var LocalAttachmentMapper */
	private $attachmentMapper;

	/** @var RecipientMapper */
	private $recipientMapper;

	public function __construct(IDBConnection $db,
								LocalAttachmentMapper $attachmentMapper,
								RecipientMapper $recipientMapper) {
		parent::__construct($db, 'mail_local_mailbox');
		$this->recipientMapper = $recipientMapper;
		$this->attachmentMapper = $attachmentMapper;
	}

	/**
	 * @param string $userId
	 * @return LocalMailboxMessage[]
	 * @throws DBException
	 */
	public function getAllForUser(string $userId): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('m.*')
			->from('mail_accounts', 'a')
			->join('a', $this->getTableName(), 'm', $qb->expr()->eq('m.account_id', 'a.id'))
			->where(
				$qb->expr()->eq('a.user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR), IQueryBuilder::PARAM_STR),
				$qb->expr()->eq('m.type', $qb->createNamedParameter(LocalMailboxMessage::OUTGOING, IQueryBuilder::PARAM_INT), IQueryBuilder::PARAM_INT)
			);
		$rows = $qb->execute();

		$results = [];
		$ids = [];
		while (($row = $rows->fetch()) !== false) {
			$results[] = $this->mapRowToEntity($row);
			$ids[] = $row['id'];
		}
		$rows->closeCursor();

		$attachments = $this->attachmentMapper->findAllForLocalMailbox($ids, $userId);
		$recipients = $this->recipientMapper->findAllRecipients($ids, Recipient::MAILBOX_TYPE_OUTBOX);

		return array_map(static function($entity) use ($attachments, $recipients) {
			$entity->setAttachments(
				array_map(static function($attachment) {
					return LocalAttachment::fromRow(
						array_filter($attachment, static function ($key) {
							return $key !== 'local_message_id';
						}, ARRAY_FILTER_USE_KEY)
					);
				}, array_filter($attachments, static function ($attachment) use ($entity) {
					return $entity->getId() === $attachment['local_message_id'];
				}))
			);
			$entity->setRecipients(
				array_map(static function($recipient) {
					return Recipient::fromRow(($recipient));
				}, array_filter($recipients, static function ($recipient) use ($entity){
					return $entity->getId() === $recipient['message_id'];
				}))
			);
			return $entity;
		}, $results);
	}

	public function find(int $id, string $userId): LocalMailboxMessage {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->in('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT), IQueryBuilder::PARAM_INT)
			);
		$entity = $this->findEntity($qb);
		$entity->setAttachments($this->attachmentMapper->findForLocalMailboxMessage($id, $userId));
		$entity->setRecipients($this->recipientMapper->findRecipients($id, Recipient::MAILBOX_TYPE_OUTBOX));
		return $entity;
	}

	public function saveWithRelatedData(LocalMailboxMessage $message, array $to, array $cc, array $bcc, array $attachmentIds = []): void {
		$this->insert($message);
		// i hate this
		array_map(function ($recipient) use ($message) {
			$this->recipientMapper->createForLocalMailbox($message->getId(), Recipient::TYPE_TO, $recipient['label'] ?? $recipient['email'], $recipient['email']);
		}, $to);
		array_map(function ($recipient) use ($message) {
			$this->recipientMapper->createForLocalMailbox($message->getId(), Recipient::TYPE_CC, $recipient['label'] ?? $recipient['email'], $recipient['email']);
		}, $cc);
		array_map(function ($recipient) use ($message) {
			$this->recipientMapper->createForLocalMailbox($message->getId(), Recipient::TYPE_BCC, $recipient['label'] ?? $recipient['email'], $recipient['email']);
		}, $bcc);
		$this->attachmentMapper->linkAttachmentToMessage($message->getId(), $attachmentIds);
	}

	public function deleteWithRelated(LocalMailboxMessage $message): void {
		$this->attachmentMapper->deleteForLocalMailbox($message->getId());
		$this->recipientMapper->deleteForLocalMailbox($message->getId());
		$this->delete($message);
	}
}
