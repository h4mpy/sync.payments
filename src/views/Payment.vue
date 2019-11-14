<template>
	<div class="payment">
		<div class="payment__head" :data-payment="payment.id">
			<div class="payment__title">
				{{ isSberbank ? "Оплата через" : "" }} {{ payment.name }}
				<span
					v-show="editstate === 'ready' && isEditable"
					@click="editstate = 'editing'"
					class="payment__edit"
					><svg width="11" height="11" xmlns="http://www.w3.org/2000/svg">
						<g id="svg_1" fill-rule="evenodd" fill="none">
							<path
								id="svg_3"
								d="m8.562784,0.499l1.92862,1.92862l-6.91754,6.91616l-1.92862,-1.92862l6.91754,-6.91616zm-8.05455,9.72344c-0.01934,0.07322 0.00139,0.15059 0.05388,0.20447c0.05388,0.05388 0.13125,0.0746 0.20447,0.05388l2.28644,-0.61617l-1.92862,-1.92862l-0.61617,2.28644z"
								fill-rule="nonzero"
								fill="#525C69"
							/>
							<path
								id="svg_5"
								d="m22.88307,166.34617l-1.10511,-1.10512c-0.16073,-0.16069 -0.35171,-0.24105 -0.57278,-0.24105c-0.22078,0 -0.41191,0.08036 -0.57249,0.24105l-6.49955,6.49951l-2.64181,-2.64199c-0.16076,-0.16079 -0.35167,-0.24105 -0.5726,-0.24105c-0.22096,0 -0.4118,0.08026 -0.57259,0.24105l-1.10498,1.10498c-0.16073,0.16072 -0.24116,0.35164 -0.24116,0.5727c0,0.22096 0.08043,0.4118 0.24116,0.5726l4.31953,4.31953c0.16076,0.16083 0.35167,0.24105 0.5726,0.24105c0.22092,0 0.4118,-0.08018 0.57259,-0.24105l8.17709,-8.17705c0.16062,-0.16069 0.24116,-0.35164 0.24116,-0.5726c0,-0.22096 -0.08033,-0.41187 -0.24106,-0.57256z"
								fill-rule="nonzero"
								fill="#FFFFFF"
							/>
						</g></svg
				></span>
			</div>
			<div class="payment__actions"></div>
		</div>
		<transition name="fade" mode="out-in" v-on:after-leave="onElResize">
			<div
				v-if="editstate === 'delete' && isEditable"
				key="paydelete"
				class="payment__form"
			>
				<div class="form-group">
					Оплата удаляется безвозвратно. Вы подтверждаете удаление данной
					оплаты?
				</div>
				<button
					class="ui-btn ui-btn-danger-light"
					:class="{ 'ui-btn-clock': isPending }"
					@click="deletepayment"
				>
					Подтвердить</button
				><span class="ui-btn ui-btn-link" @click="editstate = 'ready'"
					>Отменить</span
				>
			</div>
			<div
				v-else-if="editstate !== 'ready' && isEditable"
				key="payedit"
				class="payment__form"
			>
				<div class="form-group">
					<label :for="'paymentSum' + payment.id"
						>Сумма к оплате (не более
						<span class="setlink" @click="setMax(maxPayment)">{{
							maxPayment
						}}</span>
						руб.)</label
					>
					<input
						type="number"
						name="editsum"
						:id="'paymentSum' + payment.id"
						class="form-control form-control_small"
						:disabled="isPending"
						:class="{ 'is-invalid': $v.editsum.$error }"
						v-model.number="$v.editsum.$model"
					/>
					руб.
				</div>
				<div
					class="form-group form-check"
					v-if="
						payment.type === 'cash' ||
							payment.type === 'cashless' ||
							payment.type === 'acquiring'
					"
				>
					<input
						class="form-check-input"
						name="paid"
						type="checkbox"
						:id="'paid' + payment.id"
						v-model="editpaid"
					/>
					<label class="form-check-label" :for="'paid' + payment.id">
						Оплачено
					</label>
				</div>
				<button
					type="submit"
					class="ui-btn ui-btn-primary"
					@click="editpayment"
					:class="{ 'ui-btn-clock': isPending }"
				>
					Сохранить</button
				><span class="ui-btn ui-btn-light-border" @click="editstate = 'delete'"
					>Удалить оплату</span
				><span class="ui-btn ui-btn-link" @click="editstate = 'ready'"
					>Отменить</span
				>
			</div>
			<div key="paytext" v-else>
				<div class="payment__details">
					<div class="payment__sum">{{ payment.sum }} руб.</div>
					<div class="payment__status">
						<span class="label label_error" v-if="!payment.paid"
							>Не оплачено</span
						>
						<span class="label label_success" v-if="payment.paid"
							>Оплачено</span
						>
					</div>
				</div>
				<div class="payment__additional" v-if="isSberbank">
					<div v-if="payment.paid">Оплачено {{ payment.paiddate }}</div>
					<div
						v-if="!payment.paid"
						class="payment__additional-item"
						:class="{ important: !payment.sended }"
					>
						{{
							payment.sended
								? "Ссылка на оплату отправлена " + payment.sended
								: "Ссылка на оплату не отправлена"
						}}
					</div>
					<div v-if="hasChecks" class="payment__additional-item">
						<template v-if="payment.check.length">
							<div
								v-for="(check, index) in payment.check"
								:key="index"
								class="payment__additional-item"
							>
								<a
									v-if="check.link"
									:href="check.link"
									target="_blank"
									class="link"
									>Чек №{{ check.id }}</a
								>
								<span class="sending" v-if="!check.link">
									<i>Чек печатается</i>
								</span>
							</div>
						</template>
					</div>
					<div class="payment__additional-item" v-if="!payment.paid">
						<transition name="fade" mode="out-in">
							<span key="confirm" class="confirm" v-if="state === 'confirm'">
								<i>Вы уверены?</i>
								<span class="pseudo" @click="sendlink(true)">Да</span>
								<span class="pseudo" @click="sendlink(false)">Нет</span>
							</span>
							<span
								key="sending"
								class="sending"
								v-else-if="state === 'sending'"
							>
								<i>Отправка</i>
							</span>
							<span key="ready" class="pseudo" @click="sendlink(true)" v-else>{{
								payment.sended ? "Отправить еще раз" : "Отправить"
							}}</span>
						</transition>
					</div>
				</div>
				<div class="payment__additional" v-if="!isSberbank">
					<div v-if="isCashless" class="payment__additional-item">
						Подробнее см. на вкладке "Счета"
					</div>
					<div v-if="isCash || isAcquiring" class="payment__additional-item">
						Подробнее см. на вкладке "Заказ на сайте"
					</div>

					<template v-if="hasChecks">
						<div
							v-for="(check, index) in payment.check"
							:key="index"
							class="payment__additional-item"
						>
							<a
								v-if="check.link"
								:href="check.link"
								target="_blank"
								class="link"
								>Чек №{{ check.id }}</a
							>
							<span class="sending" v-if="!check.link">
								<i>Чек печатается</i>
							</span>
						</div>
					</template>
				</div>
			</div>
		</transition>

		<div class="payment__errors" v-if="errors.length">
			<div
				v-for="(error, index) in errors"
				:key="index"
				class="ui-alert ui-alert-danger"
			>
				{{ error }}
			</div>
		</div>
	</div>
</template>

<script>
//import axios from "axios";
import Vue from "vue";
import { mapActions } from "vuex";
import { mapGetters } from "vuex";
import { required, between } from "vuelidate/lib/validators";

export default {
	data() {
		return {
			state: "ready",
			editstate: "ready",
			editsum: 0,
			editpaid: false,
			formstate: "ready",
			errors: [],
			currentHeight: 0
		};
	},
	name: "Payment",
	props: ["payment", "deal"],
	computed: {
		...mapGetters(["getUnpaid"]),
		isCashless() {
			return this.payment.type === "cashless";
		},
		isCash() {
			return this.payment.type === "cash";
		},
		isSberbank() {
			return this.payment.type === "sberbank";
		},
		isAcquiring() {
			return this.payment.type === "acquiring";
		},
		isEditable() {
			if (this.isSberbank) {
				if (!this.payment.paid) return true;
			} else {
				if (!this.payment.check) return true;
			}
			return false;
		},
		isPending() {
			return this.formstate === "pending";
		},
		maxPayment() {
			return this.getUnpaid + this.payment.sum;
		},
		hasChecks() {
			return (
				typeof this.payment.check !== "undefined" &&
				this.payment.check.length > 0
			);
		}
	},
	methods: {
		...mapActions(["setPaid", "setConnected"]),
		onElResize() {
			this.$emit("onElResize");
		},
		setMax(value) {
			this.editsum = value;
			this.$v.editsum.$touch();
		},
		sendlink(send) {
			this.errors = [];
			if (send) {
				if (this.payment.sended && this.state === "confirm") {
					this.state = "sending";
				} else if (this.payment.sended) {
					this.state = "confirm";
				} else {
					this.state = "sending";
				}
				if (this.state === "sending") {
					setTimeout(() => {
						this.setPaid({
							payment: this.payment.id,
							sended: "(дата отправки с сервера)"
						});
						this.state = "ready";
					}, 3000);
					/*
					axios
						.get("https://h4mpy-395f0.firebaseio.com/sync-payment.json", {
							params: {
								type: "sendpaymentmessage",
								id: this.payment.id
							}
						})
						.then(response => {
							if (response.status === 200) {
								if (typeof response.data.errors !== "undefined") {
									this.errors = response.data.errors;
								}
								if (typeof response.data.result !== "undefined") {
									this.setPaid({
										payment: this.payment.id,
										sended: response.data.result
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
							this.state = "ready";
						});

					 */
				}
			} else {
				this.state = "ready";
			}
		},
		deletepayment() {
			this.formstate = "pending";
			this.errors = [];
			setTimeout(() => {
				Vue.delete(this.$store.state.payments, this.payment.id);
				this.formstate = "ready";
			}, 3000);
			/*			let formData = new FormData();
			formData.append("type", "deletepayment");
			formData.append("id", this.payment.id);
			formData.append("deal", this.deal);
			axios({
				method: "post",
				url: "https://h4mpy-395f0.firebaseio.com/sync-payment.json",
				data: formData,
				headers: {
					"Content-Type": "multipart/form-data"
				}
			})
				.then(response => {
					if (response.status === 200) {
						if (typeof response.data.errors !== "undefined") {
							this.errors = response.data.errors;
							this.formstate = "ready";
						} else {
							this.$store.dispatch("load").then(() => {});
						}
					} else {
						this.setConnected(false);
						this.formstate = "ready";
					}
				})
				.catch(() => {
					this.setConnected(false);
					this.formstate = "ready";
				});*/
		},
		editpayment() {
			this.formstate = "pending";
			this.errors = [];
			setTimeout(() => {
				Vue.set(
					this.$store.state.payments[this.payment.id],
					"sum",
					this.editsum
				);
				if (!this.isSberbank) {
					Vue.set(
						this.$store.state.payments[this.payment.id],
						"paid",
						this.editpaid
					);
					if (
						this.payment.type === "cash" ||
						this.payment.type === "acquiring"
					) {
						let checks = [];
						checks.push({ id: "100" });
						Vue.set(
							this.$store.state.payments[this.payment.id],
							"check",
							checks
						);
						setTimeout(() => {
							Vue.set(
								this.$store.state.payments[this.payment.id].check[0],
								"link",
								"https://ya.ru"
							);
						}, 5000);
					}
				}
				this.formstate = this.editstate = "ready";
			}, 3000);
			/*			let formData = new FormData();
			formData.append("type", "editpayment");
			formData.append("id", this.payment.id);
			formData.append("deal", this.deal);
			formData.append("sum", this.editsum);
			if (!this.isSberbank) {
				formData.append("paid", this.editpaid);
			}
			axios({
				method: "post",
				url: "https://h4mpy-395f0.firebaseio.com/sync-payment.json",
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
								this.formstate = this.editstate = "ready";
							});
						}
					} else {
						this.setConnected(false);
						this.formstate = "ready";
					}
				})
				.catch(() => {
					this.setConnected(false);
					this.formstate = "ready";
				});*/
		}
	},
	validations() {
		return {
			editsum: {
				required,
				between: between(1, this.maxPayment)
			}
		};
	},
	mounted() {
		if (typeof this.payment.sum !== "undefined") {
			this.editsum = this.payment.sum;
		}
		if (typeof this.payment.paid !== "undefined") {
			this.editpaid = this.payment.paid;
		}
	}
};
</script>
