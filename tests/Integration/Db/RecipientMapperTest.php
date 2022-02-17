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
use OCA\Mail\Db\Recipient;
use OCA\Mail\Db\RecipientMapper;
use OCA\Mail\Tests\Integration\Framework\ImapTestAccount;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IDBConnection;
use PHPUnit\Framework\MockObject\MockObject;

class RecipientMapperTest extends TestCase {
	use DatabaseTransaction;
	use ImapTestAccount;

	/** @var IDBConnection */
	private $db;

	/** @var RecipientMapper */
	private $mapper;

	/** @var ITimeFactory| MockObject */
	private $timeFactory;

	/** @var Recipient */
	private $inboxRecipient;

	/** @var Recipient */
	private $outboxRecipient;

	protected function setUp(): void {
		parent::setUp();

		$this->db = \OC::$server->getDatabaseConnection();
		$this->mapper = new RecipientMapper(
			$this->db
		);

		$qb = $this->db->getQueryBuilder();

		$delete = $qb->delete($this->mapper->getTableName());
		$delete->execute();

		$this->outboxRecipient = new Recipient();
		$this->outboxRecipient->setMessageId(1);
		$this->outboxRecipient->setEmail('doc@stardew-clinic.com');
		$this->outboxRecipient->setType(Recipient::TYPE_TO);
		$this->outboxRecipient->setMailboxType(Recipient::MAILBOX_TYPE_LOCAL);
		$this->outboxRecipient->setLabel('Dr. Harvey');
		$this->mapper->insert($this->outboxRecipient);

		$this->inboxRecipient = new Recipient();
		$this->inboxRecipient->setMessageId(1);
		$this->inboxRecipient->setEmail('wizard@stardewvalley.com');
		$this->inboxRecipient->setType(Recipient::TYPE_TO);
		$this->inboxRecipient->setMailboxType(Recipient::MAILBOX_TYPE_IMAP);
		$this->inboxRecipient->setLabel('M. Rasmodius');
		$this->mapper->insert($this->inboxRecipient);

		$inboxRecipientTwo = new Recipient();
		$inboxRecipientTwo->setMessageId(2);
		$inboxRecipientTwo->setEmail('pierre@stardewvalley.com');
		$inboxRecipientTwo->setType(Recipient::TYPE_CC);
		$inboxRecipientTwo->setMailboxType(Recipient::MAILBOX_TYPE_IMAP);
		$inboxRecipientTwo->setLabel("Pierre's General Store");
		$this->mapper->insert($inboxRecipientTwo);
	}

	public function testFindRecipientsInbox(): void {
		$result = $this->mapper->findByMessageId(1);
		$this->assertCount(1, $result);
		/** @var Recipient $recipient */
		$recipient = $result[0];
		$this->assertEquals(1, $recipient->getMessageId());
		$this->assertEquals('wizard@stardewvalley.com', $recipient->getEmail());
		$this->assertEquals(Recipient::TYPE_TO, $recipient->getType());
		$this->assertEquals(Recipient::MAILBOX_TYPE_IMAP, $recipient->getMailboxType());
		$this->assertEquals('M. Rasmodius', $recipient->getLabel());
	}

	/**
	 * @depends testFindRecipientsInbox
	 */
	public function testFindRecipientsOutbox(): void {
		$result = $this->mapper->findByMessageId(1, Recipient::MAILBOX_TYPE_LOCAL);
		$this->assertCount(1, $result);
		/** @var Recipient $recipient */
		$recipient = $result[0];
		$this->assertEquals(1, $recipient->getMessageId());
		$this->assertEquals('doc@stardew-clinic.com', $recipient->getEmail());
		$this->assertEquals(Recipient::TYPE_TO, $recipient->getType());
		$this->assertEquals(Recipient::MAILBOX_TYPE_LOCAL, $recipient->getMailboxType());
		$this->assertEquals('Dr. Harvey', $recipient->getLabel());
	}

	/**
	 * @depends testFindRecipientsOutbox
	 */
	public function testFindAllRecipientsOutbox(): void {
		$result = $this->mapper->findByMessageIds([1,2,789], Recipient::MAILBOX_TYPE_LOCAL);
		$this->assertCount(1, $result);
		/** @var Recipient $recipient */
		$recipient = $result[0];
		$this->assertEquals(1, $recipient->getMessageId());
		$this->assertEquals('doc@stardew-clinic.com', $recipient->getEmail());
		$this->assertEquals(Recipient::TYPE_TO, $recipient->getType());
		$this->assertEquals(Recipient::MAILBOX_TYPE_LOCAL, $recipient->getMailboxType());
		$this->assertEquals('Dr. Harvey', $recipient->getLabel());
	}

	/**
	 * @depends testFindAllRecipientsOutbox
	 */
	public function testFindAllRecipientsInbox(): void {
		$result = $this->mapper->findByMessageIds([1,2,57842]);
		$this->assertCount(2, $result);
		foreach ($result as $r) {
			$this->assertEquals(Recipient::MAILBOX_TYPE_IMAP, $r->getMailboxType());
		}
	}

	/**
	 * @depends testFindAllRecipientsInbox
	 */
	public function testFindAllRecipientsEmpty(): void {
		$result = $this->mapper->findByMessageIds([12,57842], Recipient::MAILBOX_TYPE_LOCAL);
		$this->assertEmpty($result);

		$result = $this->mapper->findByMessageIds([12,57842]);
		$this->assertEmpty($result);
	}

	/**
	 * @depends testFindAllRecipientsEmpty
	 */
	public function testDeleteForLocalMailbox(): void {
		$this->mapper->deleteForLocalMailbox($this->inboxRecipient->getMessageId());
		$result = $this->mapper->findByMessageId($this->inboxRecipient->getMessageId(), Recipient::MAILBOX_TYPE_LOCAL);
		$this->assertEmpty($result);
	}

	/**
	 * @depends testDeleteForLocalMailbox
	 */
	public function testSaveRecipients(): void {
		$this->mapper->saveRecipients(3, [['label' => 'Penny', 'email' => 'penny@stardewvalleylibrary.edu']], Recipient::TYPE_FROM, Recipient::MAILBOX_TYPE_LOCAL);

		$results = $this->mapper->findByMessageId(3, Recipient::MAILBOX_TYPE_LOCAL);
		$this->assertCount(1, $results);

		/** @var Recipient $entity */
		$entity = $results[0];
		$this->assertEquals(1, $entity->getMessageId());
		$this->assertEquals(Recipient::TYPE_FROM, $entity->getType());
		$this->assertEquals(Recipient::MAILBOX_TYPE_LOCAL, $entity->getMailboxType());
		$this->assertEquals('Penny', $entity->getLabel());
		$this->assertEquals('penny@stardewvalleylibrary.edu', $entity->getEmail());
	}
}
