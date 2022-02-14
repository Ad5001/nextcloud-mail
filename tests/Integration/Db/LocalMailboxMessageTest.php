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

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Db\LocalMailboxMessage;
use OCP\AppFramework\Utility\ITimeFactory;
use PHPUnit\Framework\MockObject\MockObject;

class LocalMailboxMessageTest extends TestCase {

	/** @var ITimeFactory|MockObject  */
	private $timeFactory;

	protected function setUp(): void {
		$this->timeFactory = $this->createMock(ITimeFactory::class);
	}

	public function testGettersSetters(): void {
		$time = $this->timeFactory->getTime();
		$message = new LocalMailboxMessage();

		$message->setType(LocalMailboxMessage::OUTGOING);
		$message->setAccountId(1);
		$message->setAliasId(2);
		$message->setSendAt($time);
		$message->setSubject('subject');
		$message->setBody('message');
		$message->setHtml(true);
		$message->setInReplyToId(100);
		$message->setDraftId(99);

		$this->assertEquals(LocalMailboxMessage::OUTGOING, $message->getType());
		$this->assertEquals(1, $message->getAccountId());
		$this->assertEquals(2, $message->getAliasId());
		$this->assertEquals($time, $message->getSendAt());
		$this->assertEquals('subject', $message->getSubject());
		$this->assertEquals('message', $message->getBody());
		$this->assertTrue($message->isHtml());
		$this->assertEquals(100, $message->getInReplyToId());
		$this->assertEquals(99, $message->getDraftId());
		$this->assertNull($message->getAttachments());
		$this->assertNull($message->getRecipients());
	}
}
