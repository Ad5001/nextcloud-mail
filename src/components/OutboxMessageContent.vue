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
	<div class="outbox-message-content">
		<div class="outbox-message-content__header">
			<div class="outbox-message-content__header__fields">
				<h2 :title="message.subject">
					{{ message.subject }}
				</h2>
				<div>
					<!-- TODO: make it reactive like in Thread.vue -->
					<RecipientBubble
						v-for="participant in participants"
						:key="participant.email"
						:email="participant.email"
						:label="participant.label" />
				</div>
			</div>
		</div>

		<div class="outbox-message-content__box">
			<div class="outbox-message-content__box__header">
				<Avatar
					:display-name="avatarDisplayName"
					:email="avatarEmail" />

				<div class="outbox-message-content__box__header__right">
					<Moment class="timestamp" :timestamp="message.sendAt" />
					<button class="primary">
						{{ t('mail', 'Undo') }}
					</button>
				</div>
			</div>
			<div class="outbox-message-content__box__body">
				<div
					v-if="message.isHtml"
					v-html="message.text" />
				<div v-else>
					{{ message.text }}
				</div>
			</div>
		</div>
	</div>
</template>

<script>
import Avatar from './Avatar'
import OutboxAvatarMixin from '../mixins/OutboxAvatarMixin'
import RecipientBubble from './RecipientBubble'
import { prop, uniqBy } from 'ramda'
import Moment from './Moment'

export default {
	name: 'OutboxMessageContent',
	components: {
		Avatar,
		RecipientBubble,
		Moment,
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
		participants() {
			const fromAccount = this.$store.getters.getAccount(this.message.accountId)
			const from = {
				label: fromAccount.name,
				email: fromAccount.emailAddress,
			}
			const to = this.message.to ?? []
			const cc = this.message.cc ?? []
			return uniqBy(prop('email'), [from, ...to, ...cc])
		},
	},
}
</script>

<style lang="scss" scoped>
.outbox-message-content {
	&__header {
		display: flex;
		flex-direction: row;
		justify-content: space-between;
		align-items: center;
		padding: 30px 0;
		// somehow ios doesn't care about this !important rule
		// so we have to manually set left/right padding to children
		// for 100% to be used
		box-sizing: content-box !important;
		height: 44px;
		width: 100%;

		z-index: 100;
		position: fixed; // ie fallback
		position: -webkit-sticky; // ios/safari fallback
		position: sticky;
		top: 0;
		background: -webkit-linear-gradient(var(--color-main-background), var(--color-main-background) 80%, rgba(255,255,255,0));
		background: -o-linear-gradient(var(--color-main-background), var(--color-main-background)  80%, rgba(255,255,255,0));
		background: -moz-linear-gradient(var(--color-main-background), var(--color-main-background)  80%, rgba(255,255,255,0));
		background: linear-gradient(var(--color-main-background), var(--color-main-background)  80%, rgba(255,255,255,0));

		&__fields {
			// initial width
			width: 0;
			padding-left: 60px;
			// grow and try to fill 100%
			flex: 1 1 auto;
			h2,
			p {
				white-space: nowrap;
				overflow: hidden;
				text-overflow: ellipsis;
				padding-bottom: 7px;
				margin-bottom: 0;
			}

			.transparency {
				opacity: 0.6;
				a {
					font-weight: bold;
				}
			}
		}
	}

	&__box {
		display: flex;
		flex-direction: column;
		box-shadow: 0 0 10px var(--color-box-shadow);
		border-radius: 16px;
		margin-left: 10px;
		margin-right: 10px;
		background-color: var(--color-main-background);
		padding-bottom: 0;
		margin-bottom: 10px;

		&__header {
			position: relative;
			display: flex;
			padding: 10px;
			border-radius: var(--border-radius);

			&:hover {
				background-color: var(--color-background-hover);
				border-radius: 16px;
			}

			&__right {
				display: flex;
				flex-direction: row;
				align-items: center;
				justify-content: flex-end;
				margin-left: 10px;
				height: 44px;
				width: 100%;

				.app-content-list-item-menu {
					margin-left: 4px;
				}

				.timestamp {
					margin-right: 10px;
					font-size: small;
					white-space: nowrap;
					margin-bottom: 0;
				}
			}
		}

		&__body {
			display: flex;
			flex-direction: column;
			margin: 2px 38px 60px 48px;
			height: 100%;
			background-color: #FFFFFF;
		}
	}
}

</style>
