import Vue from "vue";
import Vuex from "vuex";
import axios from "axios";

Vue.use(Vuex);

export default new Vuex.Store({
	state: {
		payments: {},
		deal: [],
		loaded: false,
		connected: true,
		access: "N"
	},
	getters: {
		getSum(state) {
			if (typeof state.deal.OPPORTUNITY !== "undefined") {
				return parseFloat(state.deal.OPPORTUNITY);
			}
			return 0;
		},
		getUnpaid(state, getters) {
			let sum = getters.getSum;
			for (var k in state.payments) {
				if (state.payments.hasOwnProperty(k)) {
					sum = sum - state.payments[k].sum;
				}
			}
			return sum > 0 ? sum : 0;
		},
		isReadable(state) {
			return state.access === "W" || state.access === "R";
		},
		isWriteable(state) {
			return state.access === "W";
		},
		getNewId(state) {
			//demo
			return (
				Math.round(
					Object.keys(state.payments)[Object.keys(state.payments).length - 1]
				) + 1
			);
		}
	},
	mutations: {
		setPayments(state, payments) {
			state.payments = payments;
		},
		setConnected(state, type) {
			state.connected = type;
		},
		setLoaded(state, type) {
			state.loaded = type;
		},
		setAccess(state, access) {
			state.access = access;
		},
		setDeal(state, deal) {
			state.deal = deal;
		},
		setSended(state, payload) {
			state.payments[payload.payment].sended = payload.sended;
		}
	},
	actions: {
		load({ commit, state }, payload) {
			let params = {
				JSON: "Y"
			};
			if (typeof payload !== "undefined") {
				if (typeof payload.deal !== "undefined" && parseInt(payload.deal) > 0) {
					params["DEAL"] = payload.deal;
				}
			} else {
				if (typeof state.deal.ID !== "undefined" && state.deal.ID !== "") {
					params["DEAL"] = state.deal.ID;
				}
			}
			return axios
				.get("https://h4mpy-395f0.firebaseio.com/sync-payment.json", {
					params: params
				})
				.then(function(response) {
					if (response.status === 200) {
						commit("setConnected", true);
						if (typeof response.data.payments !== "undefined") {
							commit("setPayments", response.data.payments);
						} else {
							commit("setPayments", {});
						}
						if (typeof response.data.access !== "undefined") {
							commit("setAccess", response.data.access);
						} else {
							commit("setAccess", "N");
						}
						if (typeof response.data.deal !== "undefined") {
							commit("setDeal", response.data.deal);
						} else {
							commit("setDeal", []);
						}
					} else {
						commit("setConnected", false);
					}
				})
				.catch(function() {
					commit("setConnected", false);
				})
				.finally(function() {
					commit("setLoaded", true);
				});
		},
		setPaid({ commit }, payload) {
			if (typeof payload !== "undefined") {
				if (
					typeof payload.payment !== "undefined" &&
					typeof payload.sended !== "undefined"
				) {
					commit("setSended", payload);
				}
			}
		},
		setConnected({ commit }, payload) {
			commit("setConnected", payload);
		}
	}
});
