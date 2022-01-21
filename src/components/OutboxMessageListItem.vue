<!--
  - @copyright Copyright (c) 2022 Richard Steinmetz <richard@steinmetz.cloud>
  -
  - @author Richard Steinmetz <richard@steinmetz.cloud>
  -
  - @license GNU AGPL version 3 or any later version
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program. If not, see <http://www.gnu.org/licenses/>.
  -
  -->

<template>
	<ListItem
		class="outbox-message"
		:class="{ selected }"
		:to="to"
		:title="title">
		<template #icon>
			<div
				class="account-color"
				:style="{'background-color': accountColor}" />
			<Avatar :display-name="avatarDisplayName" :email="avatarEmail" />
		</template>
		<template #subtitle>
			{{ message.subject }}
		</template>
		<template #actions />
	</ListItem>
</template>

<script>
import ListItem from '@nextcloud/vue/dist/Components/ListItem'
import Avatar from './Avatar'
import { calculateAccountColor } from '../util/AccountColor'
import OutboxAvatarMixin from '../mixins/OutboxAvatarMixin'

export default {
	name: 'OutboxMessageListItem',
	components: {
		ListItem,
		Avatar,
	},
	mixins: [
		OutboxAvatarMixin,
	],
	props: {
		message: {
			type: Object,
			required: true,
		},
	},
	computed: {
		to() {
			return {
				name: 'outbox',
				params: {
					messageId: this.message.id,
				},
			}
		},
		selected() {
			return this.$route.params.messageId === this.message.id
		},
		accountColor() {
			const account = this.$store.getters.getAccount(this.message.accountId)
			return calculateAccountColor(account?.emailAddress ?? '')
		},
		title() {
			return 'Due in 30 seconds'
		},
	},
}
</script>

<style lang="scss" scoped>
.outbox-message {
	&.active {
		background-color: var(--color-background-dark);
		border-radius: 16px;
	}

	.account-color {
		position: absolute;
		left: 0;
		width: 2px;
		height: 69px;
		z-index: 1;
	}

}
</style>
