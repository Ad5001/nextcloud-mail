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

namespace OCA\Mail\Migration;

use Closure;
use Doctrine\DBAL\Schema\Table;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version2000Date20220104144742 extends SimpleMigrationStep {

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$schema = $schemaClosure();

		$localMailboxTable = $schema->createTable('mail_local_mailbox');
		$localMailboxTable->addColumn('id', 'integer', [
			'autoincrement' => true,
			'notnull' => true,
			'length' => 4,
		]);
		$localMailboxTable->addColumn('type', 'integer', [
			'notnull' => true,
			'unsigned' => true,
			'length' => 1,
		]);
		$localMailboxTable->addColumn('account_id', 'integer', [
			'notnull' => true,
			'length' => 4,
		]);
		$localMailboxTable->addColumn('alias_id', 'integer', [
			'notnull' => false,
			'length' => 4,
		]);
		$localMailboxTable->addColumn('send_at', 'integer', [
			'notnull' => false,
			'length' => 4
		]);
		$localMailboxTable->addColumn('subject', 'text', [
			'notnull' => true,
			'length' => 255
		]);
		$localMailboxTable->addColumn('body', 'text', [
			'notnull' => true
		]);
		$localMailboxTable->addColumn('html', 'boolean', [
			'notnull' => false,
			'default' => false,
		]);
		$localMailboxTable->addColumn('in_reply_to_id', 'integer', [
			'notnull' => false,
			'length' => 4,
		]);
		$localMailboxTable->addColumn('draft_id', 'integer', [
			'notnull' => false,
			'length' => 4,
		]);
		$localMailboxTable->setPrimaryKey(['id']);

		/** @var Table $recipientsTable */
		$recipientsTable = $schema->getTable('mail_recipients');
		$recipientsTable->addColumn('local_message_id', 'integer', [
			'notnull' => false
		]);
		$recipientsTable->changeColumn('message_id', [
			'notnull' => false
		]);
		$recipientsTable->addForeignKeyConstraint($localMailboxTable, ['local_message_id'], ['id'],  ['onDelete' => 'CASCADE']);

		$attachmentsTable = $schema->getTable('mail_attachments');
		$attachmentsTable->addColumn('local_message_id', 'integer', [
			'notnull' => false
		]);

		// add FK contraint recipients
		$attachmentsTable->addForeignKeyConstraint($localMailboxTable, ['local_message_id'], ['id'],  ['onDelete' => 'CASCADE']);

		return $schema;
	}
}
