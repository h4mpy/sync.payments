import Vue from "vue";
import Router from "vue-router";
import Order from "./views/Order.vue";
import Main from "./views/Main.vue";

Vue.use(Router);

export default new Router({
	routes: [
		{
			path: "/order/:order",
			name: "order",
			component: Order,
			props: true
		},
		{
			path: "/:deal",
			name: "main",
			component: Main,
			props: true
		}
	]
});
