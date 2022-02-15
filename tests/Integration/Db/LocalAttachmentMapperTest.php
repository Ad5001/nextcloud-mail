<?php

declare(strict_types=1);

/**
 * @copyright 2022 Anna Larch <anna.larch@gmx.net>
 *
 * @author 2022 Anna Larch <anna.larch@gmx.net>
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

namespace OCA\Mail\Tests\Integration\Db;

use ChristophWurst\Nextcloud\Testing\DatabaseTransaction;
use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Db\LocalAttachmentMapper;
use OCA\Mail\Db\LocalMailboxMessage;
use OCA\Mail\Db\LocalMailboxMessageMapper;
use OCA\Mail\Db\RecipientMapper;
use OCA\Mail\Tests\Integration\Framework\ImapTestAccount;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IDBConnection;
use PHPUnit\Framework\MockObject\MockObject;

class LocalMailboxMessageMapperTest extends TestCase {
	use DatabaseTransaction;
	use ImapTestAccount;

	/** @var IDBConnection */
	private $db;

	/** @var LocalMailboxMessageMapper */
	private $mapper;

	/** @var ITimeFactory| MockObject */
	private $timeFactory;

	/** @var LocalMailboxMessage */
	private $entity;

	/** @var \OCA\Mail\Db\MailAccount */
	private $acct;

	protected function setUp(): void {
		parent::setUp();

		$this->db = \OC::$server->getDatabaseConnection();
		$recipientMapper = new RecipientMapper(
			$this->db
		);
		$this->mapper = new LocalMailboxMessageMapper(
			$this->db,
			$this->createMock(LocalAttachmentMapper::class),
			$recipientMapper
		);

		$qb = $this->db->getQueryBuilder();
		$delete = $qb->delete($this->mapper->getTableName());
		$delete->execute();

		$this->acct = $this->createTestAccount();

		$message = new LocalMailboxMessage();
		$message->setType(LocalMailboxMessage::TYPE_OUTGOING);
		$message->setAccountId($this->acct->getId());
		$message->setAliasId(2);
		$message->setSendAt(123);
		$message->setSubject('subject');
		$message->setBody('message');
		$message->setHtml(true);
		$message->setInReplyToId(100);
		$message->setDraftId(99);
		$this->entity = $this->mapper->insert($message);
	}

	public function testFindAllForUser(): void {
		$result = $this->mapper->getAllForUser($this->getTestAccountUserId());

		$this->assertCount(1, $result);
		$row = $result[0];
		$this->assertEquals(LocalMailboxMessage::TYPE_OUTGOING, $row->getType());
		$this->assertEquals(2, $row->getAliasId());
		$this->assertEquals($this->acct->getId(), $row->getAccountId());
		$this->assertEquals('subject', $row->getSubject());
		$this->assertEquals('message', $row->getBody());
		$this->assertEquals(100, $row->getInReplyToId());
		$this->assertEquals(99, $row->getDraftId());
		$this->assertTrue($row->isHtml());
		$this->assertEmpty($row->getAttachments());
		$this->assertEmpty($row->getRecipients());
	}

	/**
	 * @depends testFindAllForUser
	 */
	public function testFindById(): void {
		$row = $this->mapper->findById($this->entity->getId(), $this->acct->getUserId());

		$this->assertEquals(LocalMailboxMessage::TYPE_OUTGOING, $row->getType());
		$this->assertEquals(2, $row->getAliasId());
		$this->assertEquals($this->acct->getId(), $row->getAccountId());
		$this->assertEquals('subject', $row->getSubject());
		$this->assertEquals('message', $row->getBody());
		$this->assertEquals(100, $row->getInReplyToId());
		$this->assertEquals(99, $row->getDraftId());
		$this->assertTrue($row->isHtml());
		$this->assertEmpty($row->getAttachments());
		$this->assertEmpty($row->getRecipients());
	}

	public function testFindByIdNotFound(): void {
		$this->expectException(DoesNotExistException::class);
		$this->mapper->findById(1337, $this->acct->getUserId());
	}

	/**
	 * @depends testFindById
	 */
	public function testDeleteWithRelated(): void {
		$this->mapper->deleteWithRelated($this->entity);

		$result = $this->mapper->getAllForUser($this->getTestAccountUserId());

		$this->assertEmpty($result);
	}

	public function testSaveWithRelatedData(): void {
		// cleanup
		$qb = $this->db->getQueryBuilder();
		$delete = $qb->delete($this->mapper->getTableName());
		$delete->execute();

		$message = new LocalMailboxMessage();
		$message->setType(LocalMailboxMessage::TYPE_OUTGOING);
		$message->setAccountId($this->acct->getId());
		$message->setAliasId(3);
		$message->setSendAt(3);
		$message->setSubject('savedWithRelated');
		$message->setBody('message');
		$message->setHtml(true);
		$message->setInReplyToId(1010);
		$message->setDraftId(999);
		$to = [['label' => 'M. Rasmodeus', 'email' => 'wizard@stardew-valley.com']];

		$this->mapper->saveWithRelatedData($message, $to, [], []);

		$results = $this->mapper->getAllForUser($this->acct->getUserId());
		$row = $results[0];
		$this->assertEquals(LocalMailboxMessage::TYPE_OUTGOING, $row->getType());
		$this->assertEquals(3, $row->getAliasId());
		$this->assertEquals($this->acct->getId(), $row->getAccountId());
		$this->assertEquals('savedWithRelated', $row->getSubject());
		$this->assertEquals('message', $row->getBody());
		$this->assertEquals(1010, $row->getInReplyToId());
		$this->assertEquals(999, $row->getDraftId());
		$this->assertTrue($row->isHtml());
		$this->assertEmpty($row->getAttachments());
		$this->assertCount(1, $row->getRecipients());
	}
}
