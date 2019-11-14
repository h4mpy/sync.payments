<template>
	<div class="payments">
		<div class="loading" v-if="!loaded">Загрузка...</div>
		<div class="connection-problem" v-if="!connected">
			<div class="info-widget">
				<span class="info-widget__image"></span>
				<span class="info-widget__text">
					<span class="info-widget__title">Ошибка соединения с сайтом</span>
					<span class="info-widget__description">Подключаемся...</span>
				</span>
			</div>
		</div>
		<div class="payments__holder" v-if="isReadable">
			<Payment
				:order="getOrder"
				v-for="payment in payments"
				:key="payment.id"
				:payment="payment"
				v-show="connected"
			></Payment>
		</div>
	</div>
</template>

<script>
// @ is an alias to /src
import Payment from "@/views/Payment.vue";
import { mapGetters } from "vuex";
import { mapState } from "vuex";
export default {
	data() {
		return {
			timer: false,
			updateIntervalDefault: 60,
			updateIntervalEmergency: 10
		};
	},
	props: ["order"],
	name: "order",
	components: {
		Payment
	},
	computed: {
		...mapState(["payments", "loaded", "connected"]),
		...mapGetters(["getOrder", "isReadable", "isWriteable"])
	},
	watch: {
		getOrder: function(newOrder) {
			this.$store.dispatch("load", {
				order: newOrder
			});
		},
		connected: function(newState) {
			if (this.timer) {
				window.clearInterval(this.timer);
			}
			this.timer = setInterval(
				() => {
					this.$store.dispatch("load", { order: this.order });
				},
				newState === false
					? parseInt(this.updateIntervalEmergency) * 1000
					: parseInt(this.updateIntervalDefault) * 1000
			);
		}
	},
	mounted() {
		this.$store.dispatch("load", {
			order: this.order
		});
		this.timer = setInterval(() => {
			this.$store.dispatch("load", {
				order: this.order
			});
		}, parseInt(this.updateIntervalDefault) * 1000);
	}
};
</script>
