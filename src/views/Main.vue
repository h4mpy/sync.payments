<template>
	<div class="add-payment" v-on:transitionend.passive="onElResize">
		<p>Демо для сделки стоимостью 30 000 руб. Данные сохраняются локально.</p>
		<p>
			<span v-show="connected" class="pseudo" @click="setConnected(false)"
				>Имитировать отключение сервера данных (с откатом изменений)</span
			>
		</p>
		<div class="connection-problem" v-if="!connected">
			<div class="info-widget">
				<span class="info-widget__image"></span>
				<span class="info-widget__text">
					<span class="info-widget__title">Ошибка соединения с сайтом</span>
					<span class="info-widget__description">Подключаемся...</span>
				</span>
			</div>
		</div>
		<div class="payments__holder" v-if="showPayments">
			<Payment
				v-for="payment in payments"
				:key="payment.id"
				:payment="payment"
				:deal="deal"
				v-show="connected"
			></Payment>
		</div>
		<div
			class="add-payment__actions"
			v-if="isWriteable && getUnpaid === 0 && getSum === 0"
		>
			Добавление оплат доступно при сумме сделки больше нуля
		</div>
		<div class="add-payment__actions" v-if="isWriteable" v-show="getUnpaid > 0">
			<div class="add-payment__form" v-show="active">
				<form @submit.prevent="submit" method="post">
					<div class="form-group">
						<label for="addType">Способ оплаты</label>
						<select
							name="type"
							id="addType"
							class="form-control"
							v-model="type"
							:disabled="isPending"
						>
							<option value="sberbank">
								Оплата через Сбербанк.Эквайринг
							</option>
							<option value="cashless">
								Безналичная оплата от юридического лица
							</option>
							<option value="cash">
								Оплата наличными
							</option>
							<option value="acquiring">
								Оплата картой через терминал
							</option>
						</select>
					</div>
					<div class="form-group">
						<label for="addSum"
							>Сумма к оплате (осталось оплатить по сделке
							<span class="setlink" @click="setSum(getUnpaid)">{{
								getUnpaid
							}}</span>
							руб.)</label
						>
						<input
							type="number"
							name="sum"
							id="addSum"
							class="form-control form-control_small"
							:disabled="isPending"
							:class="{ 'is-invalid': $v.sum.$error }"
							v-model.number="$v.sum.$model"
						/>
						руб.
					</div>
					<div class="form-group" v-if="!$v.sum.between">
						<div class="ui-alert ui-alert-danger">
							Оплата не должна быть больше оставшейся суммы к оплате -
							{{ getUnpaid }} руб.
						</div>
					</div>
					<div class="form-group form-check" v-if="type === 'sberbank'">
						<input
							class="form-check-input"
							name="sendpayment"
							type="checkbox"
							value="Y"
							id="sendpayment"
							v-model="sendpayment"
						/>
						<label class="form-check-label" for="sendpayment">
							Отправить клиенту ссылку на оплату
						</label>
					</div>
					<div
						class="form-group form-check"
						v-if="
							type === 'cash' || type === 'cashless' || type === 'acquiring'
						"
					>
						<input
							class="form-check-input"
							name="paid"
							type="checkbox"
							value="Y"
							id="paid"
							v-model="paid"
						/>
						<label class="form-check-label" for="paid">
							Оплачено
						</label>
					</div>
					<div class="form-group payment__errors" v-if="errors.length">
						<div
							v-for="(error, index) in errors"
							:key="index"
							class="ui-alert ui-alert-danger"
						>
							{{ error }}
						</div>
					</div>

					<button
						type="submit"
						class="ui-btn ui-btn-primary"
						:class="{ 'ui-btn-clock': isPending }"
					>
						Отправить</button
					><span class="ui-btn ui-btn-link" @click="changeActive(false)"
						>Отменить</span
					>
				</form>
			</div>
			<span
				class="ui-btn ui-btn-default"
				v-if="!active"
				v-show="connected"
				@click="changeActive(true)"
				>Добавить оплату</span
			>
		</div>
	</div>
</template>

<script>
//import axios from "axios";
import Vue from "vue";
import Vuelidate from "vuelidate";
import { mapGetters } from "vuex";
import { mapState } from "vuex";
import { mapActions } from "vuex";
import { required, between } from "vuelidate/lib/validators";
import Payment from "@/views/Payment.vue";
//import smoothReflow from "vue-smooth-reflow";
Vue.use(Vuelidate);
export default {
	//mixins: [smoothReflow],
	data() {
		return {
			deal: 100,
			active: false,
			type: "sberbank",
			sum: "",
			status: "OK",
			timer: false,
			//Интервал обновления данных в обычном режиме
			//updateIntervalDefault: 60,
			updateIntervalDefault: 6000,
			//Интервал обновления данных при отключении сервера
			updateIntervalEmergency: 10,
			sendpayment: true,
			paid: false,
			errors: [],
			currentHeight: 0
		};
	},
	//props: ["deal"],
	name: "Main",
	components: {
		Payment
	},
	computed: {
		...mapState(["payments", "loaded", "connected"]),
		...mapGetters([
			"getUnpaid",
			"isReadable",
			"isWriteable",
			"getSum",
			"getNewId"
		]),
		isPending() {
			return this.status === "PENDING";
		},
		showPayments() {
			return Object.keys(this.payments).length > 0 && this.isReadable;
		}
	},
	mounted() {
		if (typeof this.deal !== "undefined" && this.deal !== "") {
			this.$store.dispatch("load", {
				deal: this.deal
			});
			this.timer = setInterval(() => {
				this.$store.dispatch("load");
			}, parseInt(this.updateIntervalDefault) * 1000);
		}
		/*		this.$smoothReflow({
			property: ["height"],
			transition: "height 0s ease"
		});*/
		this.onElResize();
	},
	methods: {
		...mapActions(["setPaid", "setConnected", "addPayment"]),
		onElResize() {
			if (window.updateParent && typeof window.updateParent === "function") {
				window.updateParent(); //Функция обновляет размеры фрейма в Б24
			}
		},
		setSum(value) {
			this.sum = value;
			this.$v.sum.$touch();
		},
		changeActive(value) {
			this.active = value;
			this.onElResize();
		},
		submit() {
			this.errors = [];
			this.$v.$touch();
			if (this.$v.$invalid) {
				this.status = "ERROR";
			} else {
				this.status = "PENDING";
				//demo
				setTimeout(() => {
					let newid = this.getNewId,
						names = {
							sberbank: "Сбербанк.Эквайринг",
							cashless: "Безналичная оплата от юридического лица",
							cash: "Оплата наличными",
							acquiring: "Оплата картой через терминал"
						},
						adding = {
							id: newid,
							name: names[this.type],
							type: this.type,
							paid: this.paid,
							sum: this.sum,
							paiddate: false,
							sended: false
						};
					if (this.sendpayment && this.type === "sberbank") {
						adding["sended"] = "(дата отправки с сервера)";
						adding["paid"] = false;
					}
					if (this.paid) {
						adding["paiddate"] = "(дата с сервера)";
						if (this.type === "cash" || this.type === "acquiring") {
							adding["check"] = [];
							adding["check"].push({ id: "100" });
						}
					}
					//this.addPayment(adding);
					Vue.set(this.$store.state.payments, newid, adding);
					if (this.paid) {
						if (this.type === "cash" || this.type === "acquiring") {
							setTimeout(() => {
								Vue.set(
									this.$store.state.payments[newid].check[0],
									"link",
									"https://ya.ru"
								);
							}, 5000);
						}
					}
					this.status = "OK";
					this.active = false;
				}, 3000);
				/*
				let formData = new FormData();
				formData.append("type", "addpayment");
				formData.append("payment", this.type);
				formData.append("sum", this.sum);
				formData.append("sendpayment", this.sendpayment);
				formData.append("paid", this.paid);
				formData.append("deal", this.deal);
				axios({
					method: "post",
					url: "https://...",
					data: formData,
					headers: {
						"Content-Type": "multipart/form-data"
					}
				})
					.then(response => {
						if (response.status === 200) {
							if (typeof response.data.errors !== "undefined") {
								this.errors = response.data.errors;
							} else {
								this.$store.dispatch("load").then(() => {
									this.status = "OK";
									this.active = false;
									this.onElResize();
								});
							}
						} else {
							this.setConnected(false);
						}
					})
					.catch(() => {
						this.setConnected(false);
					})
					.finally(() => {
						//this.status = "OK";
					});
*/
			}
		}
	},
	validations() {
		return {
			sum: {
				required,
				between: between(1, this.getUnpaid)
			}
		};
	},
	watch: {
		connected: function(newState) {
			if (this.timer) {
				window.clearInterval(this.timer);
			}
			this.timer = setInterval(
				() => {
					this.$store.dispatch("load");
				},
				newState === false
					? parseInt(this.updateIntervalEmergency) * 1000
					: parseInt(this.updateIntervalDefault) * 1000
			);
		}
	}
};
</script>
